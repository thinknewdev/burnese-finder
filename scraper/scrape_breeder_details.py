#!/usr/bin/env python3
"""
Scrape full detail pages for all breeders in ALL_BREEDERS.csv
Gets complete contact info, dogs bred, etc.
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


class BreederDetailScraper:
    """Scrape individual breeder detail pages for full data"""

    def __init__(self):
        self.session = SessionManager()
        os.makedirs(config.OUTPUT_DIR, exist_ok=True)
        self._init_session()

    def _init_session(self):
        """Initialize session by visiting search page first"""
        logger.info("Initializing session...")
        self.session.get_page(f"{config.BASE_URL}/DB/People_Search")

    def _rate_limit(self):
        time.sleep(config.REQUEST_DELAY_SECONDS)

    def _extract_all_fields(self, soup) -> Dict:
        """Extract all fields from the page using gridlbl/datatext pattern"""
        data = {}

        # Find all gridlbl spans (labels)
        for label_span in soup.find_all('span', class_='gridlbl'):
            label = label_span.get_text(strip=True).rstrip(':')
            if not label:
                continue

            # Find the next sibling with value
            value_span = label_span.find_next_sibling('span')
            if value_span:
                value = value_span.get_text(strip=True)
                if value and value.lower() not in ['n/a', 'none', '-', '']:
                    # Normalize field name
                    field_name = label.lower().replace(' ', '_').replace('#', '').replace("'", '')
                    data[field_name] = value

                    # Check for links (for IDs)
                    link = value_span.find('a')
                    if link and link.get('href'):
                        href = link.get('href')
                        if 'DogID=' in href:
                            match = re.search(r'DogID=(\d+)', href)
                            if match:
                                data[f'{field_name}_id'] = match.group(1)
                        elif 'PID=' in href:
                            match = re.search(r'PID=(\d+)', href)
                            if match:
                                data[f'{field_name}_id'] = match.group(1)

        return data

    def fetch_breeder_details(self, person_id: str) -> Optional[Dict]:
        """Fetch complete details for a breeder from their detail page"""
        url = f"{config.BASE_URL}/DB/People_Detail?PID={person_id}"

        try:
            soup = self.session.get_page(url)
            if not soup:
                return None

            # Check if we got redirected to homepage
            if soup.title and 'redirect' in soup.title.text.lower():
                return None

            data = {'bg_person_id': str(person_id)}

            # Extract all fields using gridlbl pattern
            extracted = self._extract_all_fields(soup)
            data.update(extracted)

            # Also try to find dogs bred/owned in tables
            for table in soup.find_all('table'):
                # Look for dog links
                for link in table.find_all('a', href=re.compile(r'DogID=')):
                    dog_name = link.get_text(strip=True)
                    match = re.search(r'DogID=(\d+)', link.get('href', ''))
                    if match and dog_name:
                        if 'dogs_bred' not in data:
                            data['dogs_bred'] = []
                        data['dogs_bred'].append({
                            'name': dog_name,
                            'id': match.group(1)
                        })

            # Convert dogs_bred list to string for CSV
            if 'dogs_bred' in data and isinstance(data['dogs_bred'], list):
                data['dogs_bred_count'] = len(data['dogs_bred'])
                data['dogs_bred_ids'] = '|'.join([d['id'] for d in data['dogs_bred'][:20]])
                data['dogs_bred_names'] = '|'.join([d['name'] for d in data['dogs_bred'][:20]])
                del data['dogs_bred']

            return data

        except Exception as e:
            logger.warning(f"Error fetching breeder {person_id}: {e}")
            return {'bg_person_id': str(person_id), 'error': str(e)}

    def scrape_all_breeders(self, checkpoint_every: int = 100):
        """Scrape details for all breeders in ALL_BREEDERS.csv"""

        # Load existing breeder IDs
        breeders_file = os.path.join(config.OUTPUT_DIR, 'ALL_BREEDERS.csv')
        if not os.path.exists(breeders_file):
            breeders_file = os.path.join(config.OUTPUT_DIR, 'breeders.csv')

        if not os.path.exists(breeders_file):
            logger.error("No breeders file found")
            return

        df = pd.read_csv(breeders_file)
        logger.info(f"Loaded {len(df)} breeders from {breeders_file}")

        # Get unique breeder IDs
        breeder_ids = []
        for col in ['breeder_id', 'bg_person_id', 'person_id']:
            if col in df.columns:
                for val in df[col].dropna():
                    bid = str(val).replace('.0', '')
                    if bid.isdigit():
                        breeder_ids.append(bid)

        breeder_ids = list(set(breeder_ids))
        logger.info(f"Found {len(breeder_ids)} unique breeder IDs to scrape")

        # Check for existing progress
        output_file = os.path.join(config.OUTPUT_DIR, 'breeders_details.csv')
        scraped_ids = set()
        if os.path.exists(output_file):
            existing = pd.read_csv(output_file)
            if 'bg_person_id' in existing.columns:
                scraped_ids = set(existing['bg_person_id'].dropna().astype(str))
            logger.info(f"Resuming from {len(scraped_ids)} already scraped breeders")

        # Filter out already scraped
        breeder_ids = [b for b in breeder_ids if b not in scraped_ids]
        logger.info(f"Scraping {len(breeder_ids)} remaining breeders")

        all_details = []
        for i, breeder_id in enumerate(tqdm(breeder_ids, desc="Fetching breeder details")):
            details = self.fetch_breeder_details(breeder_id)
            if details:
                all_details.append(details)

            self._rate_limit()

            # Checkpoint save
            if (i + 1) % checkpoint_every == 0 and all_details:
                self._save_checkpoint(all_details, output_file, scraped_ids)

        # Final save
        if all_details:
            self._save_checkpoint(all_details, output_file, scraped_ids)

        logger.info(f"DONE: Scraped {len(all_details)} breeder details")

    def _save_checkpoint(self, new_details: list, output_file: str, scraped_ids: set):
        """Save checkpoint, merging with existing data"""
        new_df = pd.DataFrame(new_details)

        if os.path.exists(output_file):
            existing = pd.read_csv(output_file)
            combined = pd.concat([existing, new_df], ignore_index=True)
            combined = combined.drop_duplicates(subset=['bg_person_id'], keep='last')
        else:
            combined = new_df

        combined.to_csv(output_file, index=False)
        logger.info(f"Checkpoint saved: {len(combined)} total breeders")


if __name__ == "__main__":
    scraper = BreederDetailScraper()
    scraper.scrape_all_breeders()
