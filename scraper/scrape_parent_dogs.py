#!/usr/bin/env python3
"""
Scrape dog details for all parent dogs from recent litters that aren't in our database yet.
"""
import sys
import os
import pandas as pd
import logging

# Add parent directory to path to import scraper modules
sys.path.insert(0, os.path.dirname(__file__))

from scrape_dog_details import DogDetailScraper

logging.basicConfig(
    level=logging.INFO,
    format='%(levelname)s:%(name)s:%(message)s'
)

def main():
    # Load the recent litters data
    litters_file = 'output/recent_litters_details.csv'
    if not os.path.exists(litters_file):
        logging.error(f"Litters file not found: {litters_file}")
        return 1

    logging.info("Loading recent litters data...")
    litters_df = pd.read_csv(litters_file)

    # Load existing dogs
    dogs_file = 'output/ALL_DOGS_MERGED.csv'
    if not os.path.exists(dogs_file):
        dogs_file = 'output/ALL_DOGS.csv'

    logging.info("Loading existing dogs...")
    existing_dogs = pd.read_csv(dogs_file)
    existing_dog_ids = set(existing_dogs['bg_dog_id'].dropna().astype(str).str.replace(r'\.0$', '', regex=True))
    logging.info(f"Found {len(existing_dog_ids)} existing dogs")

    # Get all parent dog IDs from recent litters
    sire_ids = litters_df['sire_dog_id'].dropna().astype(str).str.replace(r'\.0$', '', regex=True)
    dam_ids = litters_df['dam_dog_id'].dropna().astype(str).str.replace(r'\.0$', '', regex=True)
    all_parent_ids = set(sire_ids) | set(dam_ids)
    all_parent_ids.discard('None')
    all_parent_ids.discard('')

    logging.info(f"Found {len(all_parent_ids)} unique parent dog IDs in recent litters")

    # Find missing dog IDs
    missing_dog_ids = sorted([int(float(dog_id)) for dog_id in all_parent_ids - existing_dog_ids])
    logging.info(f"Need to scrape {len(missing_dog_ids)} missing parent dogs")

    if not missing_dog_ids:
        logging.info("No missing dogs to scrape!")
        return 0

    # Initialize scraper
    scraper = DogDetailScraper()

    # Output file
    output_file = 'output/parent_dogs_details.csv'
    checkpoint_file = 'output/parent_dogs_checkpoint.csv'

    # Check for existing checkpoint
    scraped_ids = set()
    if os.path.exists(checkpoint_file):
        logging.info(f"Found checkpoint file, loading previous progress...")
        checkpoint_df = pd.read_csv(checkpoint_file)
        scraped_ids = set(checkpoint_df['bg_dog_id'].astype(str))
        logging.info(f"Already scraped {len(scraped_ids)} dogs")

    # Filter out already scraped
    remaining_ids = [dog_id for dog_id in missing_dog_ids if str(dog_id) not in scraped_ids]
    logging.info(f"Scraping {len(remaining_ids)} remaining dogs")

    # Scrape the missing dogs
    dogs_data = []
    for i, dog_id in enumerate(remaining_ids):
        if i % 10 == 0:
            logging.info(f"Progress: {i}/{len(remaining_ids)}")

        try:
            dog_details = scraper.fetch_dog_details(dog_id)
            if dog_details:
                dogs_data.append(dog_details)

                # Checkpoint every 50 dogs
                if len(dogs_data) % 50 == 0:
                    df = pd.DataFrame(dogs_data)
                    if os.path.exists(checkpoint_file):
                        existing = pd.read_csv(checkpoint_file)
                        df = pd.concat([existing, df], ignore_index=True)
                    df.to_csv(checkpoint_file, index=False)
                    logging.info(f"✓ Checkpoint saved: {len(df)} total dogs")
                    dogs_data = []  # Clear to avoid duplicates
        except Exception as e:
            logging.error(f"Error scraping dog {dog_id}: {e}")
            continue

    # Save final results
    if dogs_data or os.path.exists(checkpoint_file):
        if dogs_data:
            df = pd.DataFrame(dogs_data)
            if os.path.exists(checkpoint_file):
                existing = pd.read_csv(checkpoint_file)
                df = pd.concat([existing, df], ignore_index=True)
        else:
            df = pd.read_csv(checkpoint_file)

        df.to_csv(output_file, index=False)
        logging.info(f"✓ Saved {len(df)} parent dogs to {output_file}")

        # Clean up checkpoint
        if os.path.exists(checkpoint_file):
            os.remove(checkpoint_file)

    logging.info("✅ DONE: Parent dogs scraped")
    return 0

if __name__ == '__main__':
    sys.exit(main())
