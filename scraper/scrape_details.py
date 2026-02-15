#!/usr/bin/env python3
"""
Detailed scraper - fetches full detail pages for people, dogs, and litters
This gets ALL available data points including images
"""

import os
import time
import logging
import pandas as pd
from typing import Dict, List, Optional
from tqdm import tqdm
from urllib.parse import urljoin
import re

from session_manager import SessionManager
from parsers import (
    parse_dog_detail,
    parse_person_detail,
    extract_dog_images,
    extract_all_page_images
)
import config

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class DetailScraper:
    """Scraper for fetching full detail pages"""

    def __init__(self):
        self.session = SessionManager()
        self.scraped_people = set()
        self.scraped_dogs = set()
        os.makedirs(config.OUTPUT_DIR, exist_ok=True)
        os.makedirs(config.IMAGES_DIR, exist_ok=True)

    def _rate_limit(self):
        time.sleep(config.REQUEST_DELAY_SECONDS)

    def _make_absolute_url(self, url: str) -> str:
        if url.startswith('http'):
            return url
        return urljoin(config.BASE_URL + '/DB/', url)

    def fetch_person_details(self, person_id: str) -> Dict:
        """
        Fetch all details for a person/breeder

        Returns dict with all available fields:
        - Name, contact info
        - Kennel name
        - Address (city, state, country)
        - Dogs bred, owned
        - Litters
        """
        if person_id in self.scraped_people:
            return {}

        url = f"{config.BASE_URL}/DB/People_Detail?PID={person_id}"

        try:
            soup = self.session.get_page(url)
            data = {'bg_person_id': person_id}

            # Parse all data from page
            # Look for specific field patterns on the page

            # Find all table rows with label/value pairs
            for row in soup.find_all('tr'):
                cells = row.find_all(['td', 'th'])
                if len(cells) >= 2:
                    label = cells[0].get_text(strip=True).lower()
                    label = re.sub(r'[:\s]+', '_', label).strip('_')
                    value = cells[1].get_text(strip=True)

                    # Check for links
                    link = cells[1].find('a')
                    if link:
                        data[f'{label}_link'] = link.get('href')

                    if label and value:
                        data[label] = value

            # Look for specific fields by common patterns
            self._extract_field(soup, data, 'name', ['Name', 'Full Name'])
            self._extract_field(soup, data, 'first_name', ['First Name', 'First'])
            self._extract_field(soup, data, 'last_name', ['Last Name', 'Last', 'Surname'])
            self._extract_field(soup, data, 'kennel_name', ['Kennel', 'Kennel Name'])
            self._extract_field(soup, data, 'address', ['Address', 'Street'])
            self._extract_field(soup, data, 'city', ['City'])
            self._extract_field(soup, data, 'state', ['State', 'State/Province', 'Province'])
            self._extract_field(soup, data, 'country', ['Country'])
            self._extract_field(soup, data, 'zip', ['Zip', 'Postal Code', 'ZIP Code'])
            self._extract_field(soup, data, 'phone', ['Phone', 'Telephone', 'Tel'])
            self._extract_field(soup, data, 'email', ['Email', 'E-mail', 'E-Mail'])
            self._extract_field(soup, data, 'website', ['Website', 'Web', 'URL'])
            self._extract_field(soup, data, 'member', ['Member', 'Membership'])

            # Find dogs bred by this person
            dogs_bred = []
            bred_section = soup.find(string=re.compile(r'bred|breeder', re.I))
            if bred_section:
                parent = bred_section.find_parent()
                if parent:
                    for link in parent.find_all_next('a', href=re.compile(r'Dog_Detail', re.I))[:50]:
                        dogs_bred.append({
                            'name': link.get_text(strip=True),
                            'link': link.get('href')
                        })

            if dogs_bred:
                data['dogs_bred'] = dogs_bred
                data['dogs_bred_count'] = len(dogs_bred)

            # Find dogs owned by this person
            dogs_owned = []
            owned_section = soup.find(string=re.compile(r'owner|owned', re.I))
            if owned_section:
                parent = owned_section.find_parent()
                if parent:
                    for link in parent.find_all_next('a', href=re.compile(r'Dog_Detail', re.I))[:50]:
                        dogs_owned.append({
                            'name': link.get_text(strip=True),
                            'link': link.get('href')
                        })

            if dogs_owned:
                data['dogs_owned'] = dogs_owned
                data['dogs_owned_count'] = len(dogs_owned)

            self.scraped_people.add(person_id)
            return data

        except Exception as e:
            logger.error(f"Error fetching person {person_id}: {e}")
            return {'bg_person_id': person_id, 'error': str(e)}

    def fetch_dog_details(self, dog_id: str, download_images: bool = True) -> Dict:
        """
        Fetch all details for a dog including images

        Returns dict with all available fields:
        - Registered name, call name
        - Registration numbers
        - Pedigree (sire, dam, grandparents)
        - Health clearances
        - Breeder, owner info
        - Titles, awards
        - Images
        """
        if dog_id in self.scraped_dogs:
            return {}

        url = f"{config.BASE_URL}/DB/Dog_Detail?DID={dog_id}"

        try:
            soup = self.session.get_page(url)
            data = {'bg_dog_id': dog_id}

            # Parse all table data
            for row in soup.find_all('tr'):
                cells = row.find_all(['td', 'th'])
                if len(cells) >= 2:
                    label = cells[0].get_text(strip=True).lower()
                    label = re.sub(r'[:\s]+', '_', label).strip('_')
                    value = cells[1].get_text(strip=True)

                    link = cells[1].find('a')
                    if link:
                        data[f'{label}_link'] = link.get('href')

                    if label and value:
                        data[label] = value

            # Extract specific fields
            self._extract_field(soup, data, 'registered_name', ['Registered Name', 'Name', 'Reg Name'])
            self._extract_field(soup, data, 'call_name', ['Call Name', 'Pet Name'])
            self._extract_field(soup, data, 'sex', ['Sex', 'Gender'])
            self._extract_field(soup, data, 'color', ['Color', 'Colour'])
            self._extract_field(soup, data, 'birth_date', ['Birth Date', 'Date of Birth', 'DOB', 'Whelped'])
            self._extract_field(soup, data, 'death_date', ['Death Date', 'Date of Death', 'Died'])
            self._extract_field(soup, data, 'registration_number', ['Registration', 'Reg #', 'Reg Number', 'AKC'])
            self._extract_field(soup, data, 'dna_number', ['DNA', 'DNA #', 'DNA Registration'])
            self._extract_field(soup, data, 'microchip', ['Microchip', 'Chip'])
            self._extract_field(soup, data, 'titles', ['Titles', 'Title', 'Awards'])

            # Pedigree info
            self._extract_field(soup, data, 'sire', ['Sire', 'Father'])
            self._extract_field(soup, data, 'dam', ['Dam', 'Mother'])
            self._extract_field(soup, data, 'sire_sire', ['Sire\'s Sire', 'Paternal Grandsire'])
            self._extract_field(soup, data, 'sire_dam', ['Sire\'s Dam', 'Paternal Granddam'])
            self._extract_field(soup, data, 'dam_sire', ['Dam\'s Sire', 'Maternal Grandsire'])
            self._extract_field(soup, data, 'dam_dam', ['Dam\'s Dam', 'Maternal Granddam'])

            # Health clearances
            self._extract_field(soup, data, 'hip', ['Hip', 'Hips', 'OFA Hip', 'Hip Rating'])
            self._extract_field(soup, data, 'elbow', ['Elbow', 'Elbows', 'OFA Elbow'])
            self._extract_field(soup, data, 'eye', ['Eye', 'Eyes', 'CERF', 'Eye Cert'])
            self._extract_field(soup, data, 'heart', ['Heart', 'Cardiac', 'OFA Heart'])
            self._extract_field(soup, data, 'thyroid', ['Thyroid'])
            self._extract_field(soup, data, 'vwd', ['vWD', 'Von Willebrand'])
            self._extract_field(soup, data, 'degenerative_myelopathy', ['DM', 'Degenerative Myelopathy'])

            # Breeder/Owner
            self._extract_field(soup, data, 'breeder', ['Breeder', 'Bred By'])
            self._extract_field(soup, data, 'owner', ['Owner', 'Owned By'])
            self._extract_field(soup, data, 'co_owner', ['Co-Owner', 'Co Owner'])

            # Kennel
            self._extract_field(soup, data, 'kennel', ['Kennel', 'Kennel Name'])

            # Download images
            if download_images:
                images = extract_dog_images(soup)
                if not images:
                    # Try getting all page images
                    images = extract_all_page_images(soup, min_size=100)

                image_paths = []
                image_urls = []

                for i, img in enumerate(images):
                    img_url = img.get('url', '')
                    if img_url:
                        image_urls.append(img_url)
                        path = self._download_image(img_url, dog_id, i)
                        if path:
                            image_paths.append(path)

                if image_urls:
                    data['image_urls'] = ';'.join(image_urls)
                if image_paths:
                    data['image_paths'] = ';'.join(image_paths)
                    data['image_count'] = len(image_paths)

            self.scraped_dogs.add(dog_id)
            return data

        except Exception as e:
            logger.error(f"Error fetching dog {dog_id}: {e}")
            return {'bg_dog_id': dog_id, 'error': str(e)}

    def _extract_field(self, soup, data: dict, key: str, labels: list):
        """Extract a field by looking for various label patterns"""
        if key in data:
            return

        for label in labels:
            # Try finding by text content
            elem = soup.find(string=re.compile(f'^{re.escape(label)}', re.I))
            if elem:
                parent = elem.find_parent()
                if parent:
                    # Look in next sibling or same parent
                    next_elem = parent.find_next_sibling()
                    if next_elem:
                        value = next_elem.get_text(strip=True)
                        if value:
                            data[key] = value
                            link = next_elem.find('a')
                            if link:
                                data[f'{key}_link'] = link.get('href')
                            return

    def _download_image(self, image_url: str, dog_id: str, index: int) -> Optional[str]:
        """Download an image and save locally"""
        try:
            full_url = self._make_absolute_url(image_url)

            # Determine extension
            if '.jpg' in full_url.lower() or '.jpeg' in full_url.lower():
                ext = '.jpg'
            elif '.png' in full_url.lower():
                ext = '.png'
            elif '.gif' in full_url.lower():
                ext = '.gif'
            else:
                ext = '.jpg'

            filename = f"dog_{dog_id}_{index}{ext}"
            filepath = os.path.join(config.IMAGES_DIR, filename)

            if os.path.exists(filepath):
                return filepath

            response = self.session.session.get(full_url, timeout=30, stream=True)
            response.raise_for_status()

            content_type = response.headers.get('content-type', '')
            if 'image' not in content_type.lower():
                return None

            with open(filepath, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)

            logger.info(f"Downloaded: {filename}")
            return filepath

        except Exception as e:
            logger.debug(f"Failed to download {image_url}: {e}")
            return None

    def scrape_all_person_details(self, input_csv: str = None):
        """
        Fetch full details for all people from a CSV of search results
        """
        if input_csv is None:
            # Try to find people CSV files
            possible_files = [
                config.BREEDERS_CSV,
                config.PEOPLE_CSV,
                os.path.join(config.OUTPUT_DIR, 'breeders_by_location.csv'),
                os.path.join(config.OUTPUT_DIR, 'test_people.csv'),
            ]
            for f in possible_files:
                if os.path.exists(f):
                    input_csv = f
                    break

        if not input_csv or not os.path.exists(input_csv):
            logger.error("No people CSV found")
            return

        df = pd.read_csv(input_csv)
        logger.info(f"Loading {len(df)} people from {input_csv}")

        # Extract person IDs from links
        person_ids = []
        for col in df.columns:
            if 'link' in col.lower():
                for val in df[col].dropna():
                    match = re.search(r'PID=(\d+)', str(val))
                    if match:
                        person_ids.append(match.group(1))

        # Also try people_id column
        if 'people_id' in df.columns:
            person_ids.extend(df['people_id'].dropna().astype(str).tolist())

        person_ids = list(set(person_ids))
        logger.info(f"Found {len(person_ids)} unique person IDs")

        all_details = []
        for pid in tqdm(person_ids, desc="Fetching person details"):
            details = self.fetch_person_details(pid)
            if details:
                all_details.append(details)
            self._rate_limit()

        # Save results
        output_file = os.path.join(config.OUTPUT_DIR, 'people_details.csv')
        df_details = pd.DataFrame(all_details)
        df_details.to_csv(output_file, index=False)
        logger.info(f"Saved {len(all_details)} person details to {output_file}")

        return df_details

    def scrape_all_dog_details(self, input_csv: str = None, max_dogs: int = None):
        """
        Fetch full details for all dogs from search results or person details
        """
        dog_ids = set()

        # Get dog IDs from people details (dogs bred/owned)
        people_details = os.path.join(config.OUTPUT_DIR, 'people_details.csv')
        if os.path.exists(people_details):
            df = pd.read_csv(people_details)
            for col in df.columns:
                if 'dogs' in col.lower() and 'link' not in col.lower():
                    for val in df[col].dropna():
                        # Parse list of dicts
                        try:
                            import ast
                            dogs_list = ast.literal_eval(str(val))
                            for d in dogs_list:
                                if 'link' in d:
                                    match = re.search(r'DID=(\d+)', d['link'])
                                    if match:
                                        dog_ids.add(match.group(1))
                        except:
                            pass

        # Also load from dog search results if available
        dogs_csv = config.DOGS_CSV
        if os.path.exists(dogs_csv):
            df = pd.read_csv(dogs_csv)
            for col in df.columns:
                if 'link' in col.lower():
                    for val in df[col].dropna():
                        match = re.search(r'DID=(\d+)', str(val))
                        if match:
                            dog_ids.add(match.group(1))

        dog_ids = list(dog_ids)
        if max_dogs:
            dog_ids = dog_ids[:max_dogs]

        logger.info(f"Fetching details for {len(dog_ids)} dogs")

        all_details = []
        for did in tqdm(dog_ids, desc="Fetching dog details"):
            details = self.fetch_dog_details(did, download_images=True)
            if details:
                all_details.append(details)
            self._rate_limit()

        # Save results
        output_file = os.path.join(config.OUTPUT_DIR, 'dogs_details.csv')
        df_details = pd.DataFrame(all_details)
        df_details.to_csv(output_file, index=False)
        logger.info(f"Saved {len(all_details)} dog details to {output_file}")

        return df_details


def main():
    import argparse

    parser = argparse.ArgumentParser(description="Fetch detailed data from Bernergarde")
    parser.add_argument('--mode', choices=['people', 'dogs', 'both'], default='both',
                        help='What to scrape details for')
    parser.add_argument('--max-dogs', type=int, help='Maximum dogs to fetch')
    parser.add_argument('--input', help='Input CSV file')

    args = parser.parse_args()

    scraper = DetailScraper()

    if args.mode in ['people', 'both']:
        logger.info("Fetching person/breeder details...")
        scraper.scrape_all_person_details(args.input)

    if args.mode in ['dogs', 'both']:
        logger.info("Fetching dog details...")
        scraper.scrape_all_dog_details(max_dogs=args.max_dogs)


if __name__ == "__main__":
    main()
