#!/usr/bin/env python3
"""
Scrape litter detail pages to get sire/dam dog IDs and puppy info.
This allows linking litters to breeders through the dogs.
"""

import os
import re
import time
import logging
import pandas as pd
from tqdm import tqdm
from typing import Dict, Optional

from session_manager import SessionManager
import config

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class LitterDetailScraper:
    """Scrape individual litter detail pages for full data including dog IDs"""

    def __init__(self):
        self.session = SessionManager()
        os.makedirs(config.OUTPUT_DIR, exist_ok=True)
        self._init_session()

    def _init_session(self):
        """Initialize session"""
        logger.info("Initializing session...")
        self.session.get_page(f"{config.BASE_URL}/DB/Litter_Search")

    def _rate_limit(self):
        time.sleep(config.REQUEST_DELAY_SECONDS)

    def fetch_litter_details(self, litter_id: str) -> Optional[Dict]:
        """Fetch complete details for a litter including sire/dam dog IDs"""
        url = f"{config.BASE_URL}/DB/Litter_Detail?LitterID={litter_id}"

        try:
            soup = self.session.get_page(url)
            if not soup:
                return None

            data = {'bg_litter_id': str(litter_id)}

            # Extract all gridlbl fields
            for label_span in soup.find_all('span', class_='gridlbl'):
                label = label_span.get_text(strip=True).rstrip(':')
                if not label:
                    continue

                value_span = label_span.find_next_sibling('span')
                if value_span:
                    value = value_span.get_text(strip=True)
                    if value and value.lower() not in ['n/a', 'none', '-', '']:
                        field_name = label.lower().replace(' ', '_').replace('#', '')
                        data[field_name] = value

                        # Extract dog IDs from links
                        link = value_span.find('a')
                        if link and link.get('href'):
                            href = link.get('href')
                            if 'DogID=' in href:
                                match = re.search(r'DogID=(\d+)', href)
                                if match:
                                    data[f'{field_name}_dog_id'] = match.group(1)
                            elif 'PID=' in href:
                                match = re.search(r'PID=(\d+)', href)
                                if match:
                                    data[f'{field_name}_person_id'] = match.group(1)

            # Find all dog links on the page (puppies, sire, dam)
            puppy_ids = []
            puppy_names = []
            for link in soup.find_all('a', href=re.compile(r'DogID=')):
                dog_name = link.get_text(strip=True)
                match = re.search(r'DogID=(\d+)', link.get('href', ''))
                if match:
                    dog_id = match.group(1)
                    # Skip sire/dam (already captured)
                    if dog_id not in [data.get('sire_dog_id'), data.get('dam_dog_id')]:
                        if dog_id not in puppy_ids:  # Avoid duplicates
                            puppy_ids.append(dog_id)
                            puppy_names.append(dog_name)

            if puppy_ids:
                data['puppy_count'] = len(puppy_ids)
                data['puppy_ids'] = '|'.join(puppy_ids)
                data['puppy_names'] = '|'.join(puppy_names[:20])  # Limit for CSV

            return data

        except Exception as e:
            logger.warning(f"Error fetching litter {litter_id}: {e}")
            return {'bg_litter_id': str(litter_id), 'error': str(e)}

    def scrape_all_litters(self, checkpoint_every: int = 100):
        """Scrape details for all litters"""

        litters_file = os.path.join(config.OUTPUT_DIR, 'litters.csv')
        if not os.path.exists(litters_file):
            logger.error("No litters file found")
            return

        df = pd.read_csv(litters_file)
        logger.info(f"Loaded {len(df)} litters")

        # Get unique litter IDs
        litter_ids = df['litter_id'].dropna().astype(str).str.replace('.0', '', regex=False).unique().tolist()
        logger.info(f"Found {len(litter_ids)} unique litter IDs")

        # Check for existing progress
        output_file = os.path.join(config.OUTPUT_DIR, 'litters_details.csv')
        scraped_ids = set()
        if os.path.exists(output_file):
            existing = pd.read_csv(output_file)
            if 'bg_litter_id' in existing.columns:
                scraped_ids = set(existing['bg_litter_id'].dropna().astype(str))
            logger.info(f"Resuming from {len(scraped_ids)} already scraped litters")

        # Filter out already scraped
        litter_ids = [l for l in litter_ids if l not in scraped_ids]
        logger.info(f"Scraping {len(litter_ids)} remaining litters")

        all_details = []
        for i, litter_id in enumerate(tqdm(litter_ids, desc="Fetching litter details")):
            details = self.fetch_litter_details(litter_id)
            if details:
                all_details.append(details)

            self._rate_limit()

            # Checkpoint save
            if (i + 1) % checkpoint_every == 0 and all_details:
                self._save_checkpoint(all_details, output_file, scraped_ids)

        # Final save
        if all_details:
            self._save_checkpoint(all_details, output_file, scraped_ids)

        logger.info(f"DONE: Scraped {len(all_details)} litter details")

    def _save_checkpoint(self, new_details: list, output_file: str, scraped_ids: set):
        """Save checkpoint, merging with existing data"""
        new_df = pd.DataFrame(new_details)

        if os.path.exists(output_file):
            existing = pd.read_csv(output_file)
            combined = pd.concat([existing, new_df], ignore_index=True)
            combined = combined.drop_duplicates(subset=['bg_litter_id'], keep='last')
        else:
            combined = new_df

        combined.to_csv(output_file, index=False)
        logger.info(f"Checkpoint saved: {len(combined)} total litters")


if __name__ == "__main__":
    scraper = LitterDetailScraper()
    scraper.scrape_all_litters()
