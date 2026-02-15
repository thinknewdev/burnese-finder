#!/usr/bin/env python3
"""
Main entry point for running the Bernergarde scraper

Usage:
    python run_scraper.py --mode people        # Scrape all people/breeders
    python run_scraper.py --mode dogs          # Scrape dogs (requires breeder data first)
    python run_scraper.py --mode litters       # Scrape litters by year range
    python run_scraper.py --mode full          # Full comprehensive scrape
    python run_scraper.py --mode test          # Quick test with limited data
"""

import argparse
import logging
import sys
from datetime import datetime

from scraper import BernerGardeScraper
import config

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(f'scraper_{datetime.now().strftime("%Y%m%d_%H%M%S")}.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


def scrape_people(scraper: BernerGardeScraper):
    """Scrape all people/breeders"""
    logger.info("Starting people/breeder scrape")

    # Scrape by state/province
    people = scraper.scrape_people_by_state()
    scraper.save_to_csv(people, "breeders_by_location.csv")

    # Also scrape by name for completeness
    people_by_name = scraper.scrape_people_by_name()
    scraper.save_to_csv(people_by_name, "breeders_by_name.csv")

    # Combine and deduplicate
    import pandas as pd
    all_people = people + people_by_name
    df = pd.DataFrame(all_people)
    if not df.empty:
        df = df.drop_duplicates()
        df.to_csv(config.BREEDERS_CSV, index=False)
        logger.info(f"Total unique breeders: {len(df)}")

    return df


def scrape_dogs(scraper: BernerGardeScraper):
    """Scrape dogs using breeder names from previously scraped data"""
    import pandas as pd

    # Load breeder data
    try:
        breeders = pd.read_csv(config.BREEDERS_CSV)
    except FileNotFoundError:
        logger.error("Breeders CSV not found. Run people scrape first.")
        return None

    all_dogs = []

    # Get unique breeder names
    if 'last_name' in breeders.columns:
        breeder_names = breeders['last_name'].dropna().unique()
    elif 'name' in breeders.columns:
        # Try to extract last name
        breeder_names = breeders['name'].dropna().str.split().str[-1].unique()
    else:
        logger.warning("Could not find name column in breeders data")
        breeder_names = []

    logger.info(f"Scraping dogs for {len(breeder_names)} breeders")

    for name in breeder_names:
        try:
            dogs = scraper.scrape_dogs_by_breeder(name)
            all_dogs.extend(dogs)
            scraper._rate_limit()
        except Exception as e:
            logger.error(f"Error scraping dogs for breeder {name}: {e}")

    scraper.save_to_csv(all_dogs, "dogs.csv")
    return pd.DataFrame(all_dogs)


def scrape_litters(scraper: BernerGardeScraper, start_year: int = 2000, end_year: int = 2026):
    """Scrape litters by year range"""
    import pandas as pd

    all_litters = []

    for year in range(start_year, end_year + 1):
        try:
            logger.info(f"Scraping litters from {year}")
            litters = scraper.scrape_litters_by_year(year)
            all_litters.extend(litters)
            scraper.save_to_csv(litters, f"litters_{year}.csv")
            scraper._rate_limit()
        except Exception as e:
            logger.error(f"Error scraping litters for year {year}: {e}")

    # Combine all years
    df = pd.DataFrame(all_litters)
    if not df.empty:
        df.to_csv(config.LITTERS_CSV, index=False)
        logger.info(f"Total litters scraped: {len(df)}")

    return df


def test_scraper(scraper: BernerGardeScraper):
    """Quick test with limited data"""
    logger.info("Running test scrape with limited data")

    # Test with just a few states
    test_states = ["CO", "CA", "NY"]
    people = scraper.scrape_people_by_state(test_states)
    scraper.save_to_csv(people, "test_people.csv")
    logger.info(f"Test scraped {len(people)} people from {test_states}")

    # Test with a few letters
    people_letters = scraper.scrape_people_by_name("ABC")
    scraper.save_to_csv(people_letters, "test_people_letters.csv")
    logger.info(f"Test scraped {len(people_letters)} people by letters A, B, C")

    return people


def full_scrape(scraper: BernerGardeScraper):
    """Run full comprehensive scrape"""
    logger.info("Starting full comprehensive scrape")

    # Phase 1: People/Breeders
    logger.info("=" * 50)
    logger.info("PHASE 1: Scraping People/Breeders")
    logger.info("=" * 50)
    scrape_people(scraper)

    # Phase 2: Dogs
    logger.info("=" * 50)
    logger.info("PHASE 2: Scraping Dogs")
    logger.info("=" * 50)
    scrape_dogs(scraper)

    # Phase 3: Litters
    logger.info("=" * 50)
    logger.info("PHASE 3: Scraping Litters")
    logger.info("=" * 50)
    scrape_litters(scraper)

    logger.info("Full scrape completed!")


def main():
    parser = argparse.ArgumentParser(description="Bernergarde Database Scraper")
    parser.add_argument(
        '--mode',
        choices=['people', 'dogs', 'litters', 'full', 'test'],
        default='test',
        help='Scraping mode'
    )
    parser.add_argument(
        '--start-year',
        type=int,
        default=2000,
        help='Start year for litter scraping'
    )
    parser.add_argument(
        '--end-year',
        type=int,
        default=2026,
        help='End year for litter scraping'
    )

    args = parser.parse_args()

    scraper = BernerGardeScraper()

    try:
        if args.mode == 'people':
            scrape_people(scraper)
        elif args.mode == 'dogs':
            scrape_dogs(scraper)
        elif args.mode == 'litters':
            scrape_litters(scraper, args.start_year, args.end_year)
        elif args.mode == 'full':
            full_scrape(scraper)
        elif args.mode == 'test':
            test_scraper(scraper)
    except KeyboardInterrupt:
        logger.info("Scraping interrupted by user")
        sys.exit(1)
    except Exception as e:
        logger.error(f"Scraping failed: {e}")
        raise


if __name__ == "__main__":
    main()
