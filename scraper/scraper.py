"""
Main scraper class for Bernergarde database
"""

import os
import time
import logging
import pandas as pd
from typing import Dict, List, Optional, Generator
import hashlib
from tqdm import tqdm
from urllib.parse import urljoin, urlparse, parse_qs

from session_manager import SessionManager
from parsers import (
    parse_search_results_table,
    parse_dog_detail,
    parse_person_detail,
    parse_litter_detail,
    extract_pagination_info,
    extract_dog_images,
    extract_all_page_images
)
import config

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class BernerGardeScraper:
    """Main scraper for Bernergarde database"""

    def __init__(self):
        self.session = SessionManager()
        self.scraped_ids = {
            'dogs': set(),
            'people': set(),
            'litters': set()
        }
        self.downloaded_images = set()
        os.makedirs(config.OUTPUT_DIR, exist_ok=True)
        os.makedirs(config.IMAGES_DIR, exist_ok=True)

    def _rate_limit(self):
        """Apply rate limiting between requests"""
        time.sleep(config.REQUEST_DELAY_SECONDS)

    def _make_absolute_url(self, url: str) -> str:
        """Convert relative URL to absolute"""
        if url.startswith('http'):
            return url
        return urljoin(config.BASE_URL, url)

    def _extract_id_from_url(self, url: str) -> Optional[str]:
        """Extract ID parameter from URL"""
        parsed = urlparse(url)
        params = parse_qs(parsed.query)
        return params.get('id', [None])[0]

    def download_image(self, image_url: str, dog_id: str = None, index: int = 0) -> Optional[str]:
        """
        Download an image and save it locally

        Args:
            image_url: URL of the image
            dog_id: Optional dog ID for naming
            index: Image index if multiple images

        Returns:
            Local file path if successful, None otherwise
        """
        if not config.DOWNLOAD_IMAGES:
            return None

        if image_url in self.downloaded_images:
            return None

        try:
            # Make URL absolute
            full_url = self._make_absolute_url(image_url)

            # Determine file extension
            parsed = urlparse(full_url)
            path = parsed.path.lower()
            if '.jpg' in path or '.jpeg' in path:
                ext = '.jpg'
            elif '.png' in path:
                ext = '.png'
            elif '.gif' in path:
                ext = '.gif'
            elif '.webp' in path:
                ext = '.webp'
            else:
                ext = '.jpg'  # Default

            # Create filename
            if dog_id:
                filename = f"dog_{dog_id}_{index}{ext}"
            else:
                # Use hash of URL for unknown images
                url_hash = hashlib.md5(full_url.encode()).hexdigest()[:12]
                filename = f"image_{url_hash}{ext}"

            filepath = os.path.join(config.IMAGES_DIR, filename)

            # Skip if already exists
            if os.path.exists(filepath):
                self.downloaded_images.add(image_url)
                return filepath

            # Download image
            response = self.session.session.get(
                full_url,
                timeout=config.IMAGE_TIMEOUT_SECONDS,
                stream=True
            )
            response.raise_for_status()

            # Verify it's an image
            content_type = response.headers.get('content-type', '')
            if 'image' not in content_type.lower():
                logger.debug(f"Not an image: {full_url}")
                return None

            # Save image
            with open(filepath, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)

            self.downloaded_images.add(image_url)
            logger.info(f"Downloaded image: {filename}")
            return filepath

        except Exception as e:
            logger.warning(f"Failed to download image {image_url}: {e}")
            return None

    def download_dog_images(self, soup, dog_id: str) -> List[str]:
        """
        Download all images for a dog from its detail page

        Args:
            soup: BeautifulSoup object of dog detail page
            dog_id: Dog's BG ID

        Returns:
            List of local file paths
        """
        if not config.DOWNLOAD_IMAGES:
            return []

        images = extract_dog_images(soup)
        local_paths = []

        for i, img_info in enumerate(images):
            self._rate_limit()
            path = self.download_image(img_info['url'], dog_id, i)
            if path:
                local_paths.append(path)

        return local_paths

    def scrape_people_by_state(self, states: List[str] = None) -> List[Dict]:
        """
        Scrape all people/breeders by iterating through states

        Args:
            states: List of state codes to search. Defaults to US states.

        Returns:
            List of person dictionaries
        """
        if states is None:
            states = config.US_STATES + config.CANADIAN_PROVINCES

        all_people = []

        logger.info(f"Starting people scrape across {len(states)} states/provinces")

        # First, initialize the search page to get form fields
        soup = self.session.get_page(config.PEOPLE_SEARCH_URL)

        for state in tqdm(states, desc="Scraping by state"):
            try:
                people = self._search_people_by_state(state)
                all_people.extend(people)
                self._rate_limit()
            except Exception as e:
                logger.error(f"Error scraping state {state}: {e}")
                continue

        logger.info(f"Scraped {len(all_people)} total people records")
        return all_people

    def scrape_people_by_name(self, letters: str = None) -> List[Dict]:
        """
        Scrape people by iterating through last name starting letters

        Args:
            letters: String of letters to iterate. Defaults to A-Z.

        Returns:
            List of person dictionaries
        """
        if letters is None:
            letters = config.ALPHABET

        all_people = []

        logger.info(f"Starting people scrape by last name letters")

        # Initialize search page
        soup = self.session.get_page(config.PEOPLE_SEARCH_URL)

        for letter in tqdm(list(letters), desc="Scraping by letter"):
            try:
                people = self._search_people_by_letter(letter)
                all_people.extend(people)
                self._rate_limit()
            except Exception as e:
                logger.error(f"Error scraping letter {letter}: {e}")
                continue

        logger.info(f"Scraped {len(all_people)} total people records")
        return all_people

    def _search_people_by_state(self, state: str) -> List[Dict]:
        """Search people by state/province"""
        # Re-get search page to refresh viewstate
        soup = self.session.get_page(config.PEOPLE_SEARCH_URL)
        self._rate_limit()

        # Build form data with actual field names from the website
        # Note: State values have trailing spaces (e.g., "CO  ")
        state_value = f"{state:<4}"  # Pad to 4 chars with spaces
        form_data = {
            'ctl00$MainContent$ddl_StateProv': state_value,
            'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
        }

        soup = self.session.post_form(config.PEOPLE_SEARCH_URL, form_data)
        results = parse_search_results_table(soup)

        # Handle pagination
        all_results = list(results)
        page_info = extract_pagination_info(soup)

        while page_info['has_next']:
            self._rate_limit()
            try:
                soup = self.session.get_page(
                    self._make_absolute_url(page_info['next_page_link'])
                )
                new_results = parse_search_results_table(soup)
                all_results.extend(new_results)
                page_info = extract_pagination_info(soup)
            except Exception as e:
                logger.warning(f"Error fetching next page: {e}")
                break

        return all_results

    def _search_people_by_letter(self, letter: str) -> List[Dict]:
        """Search people by last name starting letter"""
        # Re-get search page to refresh viewstate
        soup = self.session.get_page(config.PEOPLE_SEARCH_URL)
        self._rate_limit()

        # Build form data with actual field names
        form_data = {
            'ctl00$MainContent$tb_LastName': letter,
            'ctl00$MainContent$rbl_LN_BeginContain': 'B',  # B = Begins With
            'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
        }

        soup = self.session.post_form(config.PEOPLE_SEARCH_URL, form_data)
        results = parse_search_results_table(soup)

        # Handle pagination
        all_results = list(results)
        page_info = extract_pagination_info(soup)

        while page_info['has_next']:
            self._rate_limit()
            try:
                soup = self.session.get_page(
                    self._make_absolute_url(page_info['next_page_link'])
                )
                new_results = parse_search_results_table(soup)
                all_results.extend(new_results)
                page_info = extract_pagination_info(soup)
            except Exception as e:
                logger.warning(f"Error fetching next page: {e}")
                break

        return all_results

    def scrape_dogs_by_breeder(self, breeder_name: str) -> List[Dict]:
        """
        Scrape all dogs by a specific breeder

        Args:
            breeder_name: Breeder's last name to search

        Returns:
            List of dog dictionaries
        """
        soup = self.session.get_page(config.DOG_SEARCH_URL)
        self._rate_limit()

        form_data = {
            'ctl00$MainContent$tb_Breeder': breeder_name,
            'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
        }

        soup = self.session.post_form(config.DOG_SEARCH_URL, form_data)
        results = parse_search_results_table(soup)

        # Handle pagination
        all_results = list(results)
        page_info = extract_pagination_info(soup)

        while page_info['has_next']:
            self._rate_limit()
            try:
                soup = self.session.get_page(
                    self._make_absolute_url(page_info['next_page_link'])
                )
                new_results = parse_search_results_table(soup)
                all_results.extend(new_results)
                page_info = extract_pagination_info(soup)
            except Exception as e:
                logger.warning(f"Error fetching next page: {e}")
                break

        return all_results

    def scrape_dogs_by_kennel(self, kennel_name: str) -> List[Dict]:
        """
        Scrape all dogs from a specific kennel

        Args:
            kennel_name: Kennel name to search

        Returns:
            List of dog dictionaries
        """
        soup = self.session.get_page(config.DOG_SEARCH_URL)
        self._rate_limit()

        form_data = {
            'ctl00$MainContent$tb_Kennel': kennel_name,
            'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
        }

        soup = self.session.post_form(config.DOG_SEARCH_URL, form_data)
        results = parse_search_results_table(soup)

        all_results = list(results)
        page_info = extract_pagination_info(soup)

        while page_info['has_next']:
            self._rate_limit()
            try:
                soup = self.session.get_page(
                    self._make_absolute_url(page_info['next_page_link'])
                )
                new_results = parse_search_results_table(soup)
                all_results.extend(new_results)
                page_info = extract_pagination_info(soup)
            except Exception as e:
                logger.warning(f"Error fetching next page: {e}")
                break

        return all_results

    def scrape_litters_by_year(self, year: int) -> List[Dict]:
        """
        Scrape all litters from a specific year

        Args:
            year: Birth year to search

        Returns:
            List of litter dictionaries
        """
        soup = self.session.get_page(config.LITTER_SEARCH_URL)
        self._rate_limit()

        form_data = {
            'ctl00$MainContent$ddl_Year': str(year),
            'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
        }

        soup = self.session.post_form(config.LITTER_SEARCH_URL, form_data)
        results = parse_search_results_table(soup)

        all_results = list(results)
        page_info = extract_pagination_info(soup)

        while page_info['has_next']:
            self._rate_limit()
            try:
                soup = self.session.get_page(
                    self._make_absolute_url(page_info['next_page_link'])
                )
                new_results = parse_search_results_table(soup)
                all_results.extend(new_results)
                page_info = extract_pagination_info(soup)
            except Exception as e:
                logger.warning(f"Error fetching next page: {e}")
                break

        return all_results

    def scrape_litters_by_breeder(self, breeder_name: str) -> List[Dict]:
        """
        Scrape all litters by a specific breeder

        Args:
            breeder_name: Breeder's last name

        Returns:
            List of litter dictionaries
        """
        soup = self.session.get_page(config.LITTER_SEARCH_URL)
        self._rate_limit()

        form_data = {
            'ctl00$MainContent$tb_BreederName': breeder_name,
            'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
        }

        soup = self.session.post_form(config.LITTER_SEARCH_URL, form_data)
        results = parse_search_results_table(soup)

        all_results = list(results)
        page_info = extract_pagination_info(soup)

        while page_info['has_next']:
            self._rate_limit()
            try:
                soup = self.session.get_page(
                    self._make_absolute_url(page_info['next_page_link'])
                )
                new_results = parse_search_results_table(soup)
                all_results.extend(new_results)
                page_info = extract_pagination_info(soup)
            except Exception as e:
                logger.warning(f"Error fetching next page: {e}")
                break

        return all_results

    def get_person_details(self, person_url: str) -> Dict:
        """
        Fetch detailed information for a specific person

        Args:
            person_url: URL to person detail page

        Returns:
            Dictionary of person details
        """
        person_id = self._extract_id_from_url(person_url)
        if person_id and person_id in self.scraped_ids['people']:
            logger.debug(f"Skipping already scraped person {person_id}")
            return {}

        url = self._make_absolute_url(person_url)
        soup = self.session.get_page(url)
        data = parse_person_detail(soup)

        if person_id:
            data['bg_person_id'] = person_id
            self.scraped_ids['people'].add(person_id)

        return data

    def get_dog_details(self, dog_url: str, download_images: bool = True) -> Dict:
        """
        Fetch detailed information for a specific dog

        Args:
            dog_url: URL to dog detail page
            download_images: Whether to download dog images

        Returns:
            Dictionary of dog details
        """
        dog_id = self._extract_id_from_url(dog_url)
        if dog_id and dog_id in self.scraped_ids['dogs']:
            logger.debug(f"Skipping already scraped dog {dog_id}")
            return {}

        url = self._make_absolute_url(dog_url)
        soup = self.session.get_page(url)
        data = parse_dog_detail(soup)

        if dog_id:
            data['bg_dog_id'] = dog_id
            self.scraped_ids['dogs'].add(dog_id)

            # Download images
            if download_images and config.DOWNLOAD_IMAGES:
                image_paths = self.download_dog_images(soup, dog_id)
                if image_paths:
                    data['image_paths'] = ';'.join(image_paths)
                    data['image_count'] = len(image_paths)

                # Also store original image URLs
                images = extract_dog_images(soup)
                if images:
                    data['image_urls'] = ';'.join([img['url'] for img in images])

        return data

    def get_litter_details(self, litter_url: str) -> Dict:
        """
        Fetch detailed information for a specific litter

        Args:
            litter_url: URL to litter detail page

        Returns:
            Dictionary of litter details
        """
        litter_id = self._extract_id_from_url(litter_url)
        if litter_id and litter_id in self.scraped_ids['litters']:
            logger.debug(f"Skipping already scraped litter {litter_id}")
            return {}

        url = self._make_absolute_url(litter_url)
        soup = self.session.get_page(url)
        data = parse_litter_detail(soup)

        if litter_id:
            data['bg_litter_id'] = litter_id
            self.scraped_ids['litters'].add(litter_id)

        return data

    def save_to_csv(self, data: List[Dict], filename: str):
        """
        Save scraped data to CSV file

        Args:
            data: List of dictionaries to save
            filename: Output filename
        """
        if not data:
            logger.warning(f"No data to save to {filename}")
            return

        df = pd.DataFrame(data)
        filepath = os.path.join(config.OUTPUT_DIR, filename)

        # Append if file exists
        if os.path.exists(filepath):
            existing_df = pd.read_csv(filepath)
            df = pd.concat([existing_df, df], ignore_index=True)
            df = df.drop_duplicates()

        df.to_csv(filepath, index=False)
        logger.info(f"Saved {len(df)} records to {filepath}")

    def comprehensive_breeder_scrape(self) -> pd.DataFrame:
        """
        Perform comprehensive scrape of all breeders

        This method:
        1. Scrapes people by iterating through all US states and Canadian provinces
        2. Also scrapes by last name letters for broader coverage
        3. Deduplicates results
        4. Saves to CSV

        Returns:
            DataFrame of all breeder data
        """
        all_people = []

        # Method 1: By geographic location
        logger.info("Phase 1: Scraping by geographic location")
        geo_people = self.scrape_people_by_state()
        all_people.extend(geo_people)
        self.save_to_csv(geo_people, "people_by_location.csv")

        # Method 2: By last name
        logger.info("Phase 2: Scraping by last name")
        name_people = self.scrape_people_by_name()
        all_people.extend(name_people)
        self.save_to_csv(name_people, "people_by_name.csv")

        # Deduplicate and save final
        df = pd.DataFrame(all_people)
        if not df.empty:
            df = df.drop_duplicates()
            df.to_csv(config.PEOPLE_CSV, index=False)
            logger.info(f"Final deduplicated count: {len(df)} people")

        return df
