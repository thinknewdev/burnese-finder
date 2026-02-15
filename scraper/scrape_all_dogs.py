#!/usr/bin/env python3
"""
Comprehensive dog scraper - searches by kennel name from ALL_BREEDERS.csv
This gets all dogs linked to known kennels.
"""

import os
import time
import pandas as pd
from tqdm import tqdm
import logging

from scraper import BernerGardeScraper
import config

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)


def scrape_all_dogs():
    scraper = BernerGardeScraper()

    # Load breeders
    breeders_file = os.path.join(config.OUTPUT_DIR, 'ALL_BREEDERS.csv')
    if not os.path.exists(breeders_file):
        breeders_file = os.path.join(config.OUTPUT_DIR, 'breeders.csv')

    breeders = pd.read_csv(breeders_file)
    logger.info(f"Loaded {len(breeders)} breeders")

    # Get unique kennel names (non-empty, at least 3 chars)
    kennels = breeders['kennel_name'].dropna().unique()
    kennels = [k for k in kennels if len(str(k)) >= 3]
    logger.info(f"Found {len(kennels)} kennels to search")

    all_dogs = []
    seen_ids = set()

    for kennel in tqdm(kennels, desc="Searching kennels"):
        try:
            dogs = scraper.scrape_dogs_by_kennel(str(kennel))
            for d in dogs:
                dog_id = d.get('dog_id') or d.get('bg_dog_id')
                if dog_id and dog_id not in seen_ids:
                    d['source_kennel'] = kennel
                    all_dogs.append(d)
                    seen_ids.add(dog_id)
        except Exception as e:
            logger.warning(f"Error searching kennel {kennel}: {e}")

        # Save periodically
        if len(all_dogs) % 500 == 0 and len(all_dogs) > 0:
            df = pd.DataFrame(all_dogs)
            df.to_csv(os.path.join(config.OUTPUT_DIR, 'dogs_from_kennels.csv'), index=False)
            logger.info(f"Saved checkpoint: {len(all_dogs)} dogs")

    # Final save
    if all_dogs:
        df = pd.DataFrame(all_dogs)
        df.to_csv(os.path.join(config.OUTPUT_DIR, 'dogs_from_kennels.csv'), index=False)
        logger.info(f"DONE: Saved {len(all_dogs)} unique dogs")
    else:
        logger.warning("No dogs found")

    return all_dogs


if __name__ == "__main__":
    scrape_all_dogs()
