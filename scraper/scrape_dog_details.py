#!/usr/bin/env python3
"""
Scrape full detail pages for all dogs in ALL_DOGS.csv
Gets complete health clearances, pedigree, images, etc.
"""

import os
import re
import time
import logging
import pandas as pd
from tqdm import tqdm
from typing import Dict, Optional
from urllib.parse import urljoin

from session_manager import SessionManager
import config

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class DogDetailScraper:
    """Scrape individual dog detail pages for full data"""

    def __init__(self):
        self.session = SessionManager()
        os.makedirs(config.OUTPUT_DIR, exist_ok=True)
        os.makedirs(config.IMAGES_DIR, exist_ok=True)
        self._init_session()

    def _init_session(self):
        """Initialize session by visiting search page first"""
        logger.info("Initializing session...")
        self.session.get_page(f"{config.BASE_URL}/DB/Dog_Search")

    def _rate_limit(self):
        time.sleep(config.REQUEST_DELAY_SECONDS)

    def _download_image(self, image_url: str, dog_id: str, index: int = 0) -> Optional[str]:
        """Download an image and return the local path"""
        try:
            if not image_url:
                return None

            # Make absolute URL
            if image_url.startswith('/'):
                full_url = config.BASE_URL + image_url
            elif image_url.startswith('http'):
                full_url = image_url
            else:
                full_url = config.BASE_URL + '/' + image_url

            # Determine extension
            if '.png' in full_url.lower():
                ext = '.png'
            elif '.gif' in full_url.lower():
                ext = '.gif'
            elif '.webp' in full_url.lower():
                ext = '.webp'
            else:
                ext = '.jpg'

            filename = f"dog_{dog_id}_{index}{ext}"
            filepath = os.path.join(config.IMAGES_DIR, filename)

            # Skip if already downloaded
            if os.path.exists(filepath):
                return filepath

            # Download
            response = self.session.session.get(full_url, timeout=30, stream=True)
            response.raise_for_status()

            # Verify it's an image
            content_type = response.headers.get('content-type', '')
            if 'image' not in content_type.lower():
                return None

            with open(filepath, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)

            # Check if it's a placeholder image (8288 bytes = "No Photo Available")
            file_size = os.path.getsize(filepath)
            if file_size == 8288:
                os.remove(filepath)
                logger.debug(f"Removed placeholder image: {filename}")
                return None

            logger.debug(f"Downloaded: {filename} ({file_size} bytes)")
            return filepath

        except Exception as e:
            logger.debug(f"Failed to download {image_url}: {e}")
            return None

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
                        elif 'LitterID=' in href:
                            match = re.search(r'LitterID=(\d+)', href)
                            if match:
                                data[f'{field_name}_id'] = match.group(1)

        # Also get dogname class (registered name)
        dogname = soup.find('span', class_='dogname')
        if dogname:
            data['registered_name'] = dogname.get_text(strip=True)

        return data

    def fetch_dog_details(self, dog_id: str) -> Optional[Dict]:
        """Fetch complete details for a dog from its detail page"""
        # URL uses DogID parameter, not DID
        url = f"{config.BASE_URL}/DB/Dog_Detail?DogID={dog_id}"

        try:
            soup = self.session.get_page(url)
            if not soup:
                return None

            # Check if we got redirected to homepage
            if soup.title and 'redirect' in soup.title.text.lower():
                return None

            data = {'bg_dog_id': str(dog_id)}

            # Extract all fields using the new method
            extracted = self._extract_all_fields(soup)
            data.update(extracted)

            # Get age if we have birth/death dates
            if 'birth_date' in data:
                try:
                    from datetime import datetime
                    birth = pd.to_datetime(data['birth_date'])
                    if 'death_date' in data:
                        death = pd.to_datetime(data['death_date'])
                        age = (death - birth).days / 365.25
                    else:
                        age = (datetime.now() - birth).days / 365.25
                    data['age_years'] = round(age, 1)
                except:
                    pass

            # Find and download images
            image_urls = []
            image_paths = []

            for img in soup.find_all('img'):
                src = img.get('src', '')
                if src and ('dog' in src.lower() or 'photo' in src.lower() or 'BG' in src) and 'logo' not in src.lower() and 'blank' not in src.lower():
                    if src.startswith('/'):
                        full_url = config.BASE_URL + '/DB' + src
                    elif src.startswith('http'):
                        full_url = src
                    else:
                        # Relative path - images are under /DB/
                        full_url = config.BASE_URL + '/DB/' + src
                    image_urls.append(full_url)

            # Download images (max 5)
            for i, url in enumerate(image_urls[:5]):
                local_path = self._download_image(url, dog_id, i)
                if local_path:
                    image_paths.append(local_path)

            if image_urls:
                data['image_urls'] = '|'.join(image_urls[:5])
            if image_paths:
                data['image_paths'] = '|'.join(image_paths)
                data['primary_image'] = image_paths[0]

            return data

        except Exception as e:
            logger.warning(f"Error fetching dog {dog_id}: {e}")
            return {'bg_dog_id': str(dog_id), 'error': str(e)}

    def scrape_all_dogs(self, checkpoint_every: int = 100):
        """Scrape details for all dogs in ALL_DOGS.csv"""

        # Load existing dog IDs
        dogs_file = os.path.join(config.OUTPUT_DIR, 'ALL_DOGS.csv')
        if not os.path.exists(dogs_file):
            dogs_file = os.path.join(config.OUTPUT_DIR, 'dogs_from_kennels.csv')

        if not os.path.exists(dogs_file):
            logger.error("No dogs file found")
            return

        df = pd.read_csv(dogs_file)
        logger.info(f"Loaded {len(df)} dogs from {dogs_file}")

        # Get unique dog IDs
        dog_ids = []
        for col in ['dog_id', 'bg_dog_id']:
            if col in df.columns:
                for val in df[col].dropna():
                    # Clean ID (remove .0 from float conversion)
                    dog_id = str(val).replace('.0', '')
                    if dog_id.isdigit():
                        dog_ids.append(dog_id)

        dog_ids = list(set(dog_ids))
        logger.info(f"Found {len(dog_ids)} unique dog IDs to scrape")

        # Check for existing progress
        output_file = os.path.join(config.OUTPUT_DIR, 'dogs_details.csv')
        scraped_ids = set()
        if os.path.exists(output_file):
            existing = pd.read_csv(output_file)
            if 'bg_dog_id' in existing.columns:
                scraped_ids = set(existing['bg_dog_id'].dropna().astype(str))
            logger.info(f"Resuming from {len(scraped_ids)} already scraped dogs")

        # Filter out already scraped
        dog_ids = [d for d in dog_ids if d not in scraped_ids]
        logger.info(f"Scraping {len(dog_ids)} remaining dogs")

        all_details = []
        for i, dog_id in enumerate(tqdm(dog_ids, desc="Fetching dog details")):
            details = self.fetch_dog_details(dog_id)
            if details:
                all_details.append(details)

            self._rate_limit()

            # Checkpoint save
            if (i + 1) % checkpoint_every == 0 and all_details:
                self._save_checkpoint(all_details, output_file, scraped_ids)

        # Final save
        if all_details:
            self._save_checkpoint(all_details, output_file, scraped_ids)

        logger.info(f"DONE: Scraped {len(all_details)} dog details")

    def _save_checkpoint(self, new_details: list, output_file: str, scraped_ids: set):
        """Save checkpoint, merging with existing data"""
        new_df = pd.DataFrame(new_details)

        if os.path.exists(output_file):
            existing = pd.read_csv(output_file)
            combined = pd.concat([existing, new_df], ignore_index=True)
            combined = combined.drop_duplicates(subset=['bg_dog_id'], keep='last')
        else:
            combined = new_df

        combined.to_csv(output_file, index=False)
        logger.info(f"Checkpoint saved: {len(combined)} total dogs")


if __name__ == "__main__":
    scraper = DogDetailScraper()
    scraper.scrape_all_dogs()
