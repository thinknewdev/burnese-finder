#!/usr/bin/env python3
"""
Health and Litter scraper for grading breeding quality
Focuses on data needed to evaluate the best dogs/breeders
"""

import os
import time
import logging
import pandas as pd
from typing import Dict, List
from tqdm import tqdm
import re
from datetime import datetime

from session_manager import SessionManager
from parsers import parse_search_results_table
import config

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)


class HealthLitterScraper:
    """Scraper focused on health data and litter information"""

    def __init__(self):
        self.session = SessionManager()
        os.makedirs(config.OUTPUT_DIR, exist_ok=True)

    def _rate_limit(self):
        time.sleep(config.REQUEST_DELAY_SECONDS)

    def scrape_litters_comprehensive(self, start_year: int = 1990, end_year: int = 2026):
        """
        Scrape all litters to build lineage data

        This gives us:
        - Sire/Dam pairings
        - Breeder production history
        - Birth year trends
        """
        all_litters = []

        for year in tqdm(range(start_year, end_year + 1), desc="Scraping litters by year"):
            soup = self.session.get_page(config.LITTER_SEARCH_URL)
            self._rate_limit()

            form_data = {
                'ctl00$MainContent$ddl_Year': str(year),
                'ctl00$MainContent$btn_SubmitSearch': 'Submit Search'
            }

            soup = self.session.post_form(config.LITTER_SEARCH_URL, form_data)
            results = parse_search_results_table(soup)

            for r in results:
                r['birth_year'] = year

            all_litters.extend(results)
            self._rate_limit()

            # Save intermediate results every 5 years
            if year % 5 == 0:
                df = pd.DataFrame(all_litters)
                df.to_csv(os.path.join(config.OUTPUT_DIR, f'litters_through_{year}.csv'), index=False)

        # Save final
        df = pd.DataFrame(all_litters)
        df.to_csv(config.LITTERS_CSV, index=False)
        logger.info(f"Saved {len(all_litters)} litters")
        return df

    def fetch_litter_details(self, litter_id: str) -> Dict:
        """Fetch full details for a litter including all puppies"""
        url = f"{config.BASE_URL}/DB/Litter_Detail?LID={litter_id}"

        try:
            soup = self.session.get_page(url)
            data = {'bg_litter_id': litter_id}

            # Parse table data
            for row in soup.find_all('tr'):
                cells = row.find_all(['td', 'th'])
                if len(cells) >= 2:
                    label = cells[0].get_text(strip=True).lower()
                    label = re.sub(r'[:\s]+', '_', label).strip('_')
                    value = cells[1].get_text(strip=True)

                    link = cells[1].find('a')
                    if link:
                        data[f'{label}_link'] = link.get('href')
                        # Extract IDs from links
                        did_match = re.search(r'DID=(\d+)', link.get('href', ''))
                        if did_match:
                            data[f'{label}_dog_id'] = did_match.group(1)
                        pid_match = re.search(r'PID=(\d+)', link.get('href', ''))
                        if pid_match:
                            data[f'{label}_person_id'] = pid_match.group(1)

                    if label and value:
                        data[label] = value

            # Find puppies list
            puppies = []
            for link in soup.find_all('a', href=re.compile(r'Dog_Detail\?DID=', re.I)):
                name = link.get_text(strip=True)
                href = link.get('href')
                did_match = re.search(r'DID=(\d+)', href)
                if did_match and name:
                    puppies.append({
                        'name': name,
                        'dog_id': did_match.group(1),
                        'link': href
                    })

            # Deduplicate (sire/dam might be listed too)
            seen = set()
            unique_puppies = []
            for p in puppies:
                if p['dog_id'] not in seen:
                    seen.add(p['dog_id'])
                    unique_puppies.append(p)

            data['puppies'] = unique_puppies
            data['puppy_count'] = len(unique_puppies)

            return data

        except Exception as e:
            logger.error(f"Error fetching litter {litter_id}: {e}")
            return {'bg_litter_id': litter_id, 'error': str(e)}

    def fetch_dog_health_data(self, dog_id: str) -> Dict:
        """
        Fetch comprehensive health data for a dog

        Key health metrics for Bernese:
        - Hip dysplasia (OFA/PennHIP)
        - Elbow dysplasia
        - Eye certifications (CERF)
        - Heart (cardiac) clearances
        - Degenerative Myelopathy (DM)
        - Von Willebrand Disease (vWD)
        - Histiocytosis (cancer prevalence)
        - Longevity (age at death)
        """
        url = f"{config.BASE_URL}/DB/Dog_Detail?DID={dog_id}"

        try:
            soup = self.session.get_page(url)
            data = {'bg_dog_id': dog_id}

            # Get all text to search for patterns
            page_text = soup.get_text()

            # Parse structured data
            for row in soup.find_all('tr'):
                cells = row.find_all(['td', 'th'])
                if len(cells) >= 2:
                    label = cells[0].get_text(strip=True).lower()
                    label = re.sub(r'[:\s]+', '_', label).strip('_')
                    value = cells[1].get_text(strip=True)

                    if label and value:
                        data[label] = value

                        link = cells[1].find('a')
                        if link:
                            data[f'{label}_link'] = link.get('href')

            # Extract specific health fields with common variations
            health_fields = {
                'hip': ['hip', 'hips', 'ofa_hip', 'pennhip'],
                'elbow': ['elbow', 'elbows', 'ofa_elbow'],
                'eye': ['eye', 'eyes', 'cerf', 'eye_cert', 'ofa_eye'],
                'heart': ['heart', 'cardiac', 'ofa_heart', 'echo'],
                'thyroid': ['thyroid', 'ofa_thyroid'],
                'dm': ['dm', 'degenerative_myelopathy', 'dm_status'],
                'vwd': ['vwd', 'von_willebrand'],
            }

            for canonical, variants in health_fields.items():
                if canonical not in data:
                    for v in variants:
                        if v in data:
                            data[f'health_{canonical}'] = data[v]
                            break

            # Calculate age/longevity if possible
            birth_date = data.get('birth_date') or data.get('whelped') or data.get('dob')
            death_date = data.get('death_date') or data.get('died') or data.get('date_of_death')

            if birth_date and death_date:
                try:
                    # Try various date formats
                    for fmt in ['%m/%d/%Y', '%Y-%m-%d', '%d-%m-%Y', '%B %d, %Y']:
                        try:
                            bd = datetime.strptime(birth_date.strip(), fmt)
                            dd = datetime.strptime(death_date.strip(), fmt)
                            age_days = (dd - bd).days
                            data['age_at_death_years'] = round(age_days / 365.25, 2)
                            data['age_at_death_months'] = round(age_days / 30.44, 1)
                            break
                        except:
                            continue
                except:
                    pass

            # Look for cause of death
            cod_match = re.search(r'(cause of death|cod|died of)[:\s]*([^\n,]+)', page_text, re.I)
            if cod_match:
                data['cause_of_death'] = cod_match.group(2).strip()

            # Look for cancer/histiocytosis mentions
            if re.search(r'cancer|histiocyt|tumor|malignan', page_text, re.I):
                data['cancer_mentioned'] = True

            return data

        except Exception as e:
            logger.error(f"Error fetching dog health {dog_id}: {e}")
            return {'bg_dog_id': dog_id, 'error': str(e)}

    def build_health_database(self, dog_ids: List[str] = None, max_dogs: int = None):
        """
        Build comprehensive health database for dogs

        If dog_ids not provided, extracts from existing CSV files
        """
        if dog_ids is None:
            dog_ids = set()

            # Get from litters (puppies, sires, dams)
            litters_file = config.LITTERS_CSV
            if os.path.exists(litters_file):
                df = pd.read_csv(litters_file)
                for col in df.columns:
                    if 'link' in col.lower():
                        for val in df[col].dropna():
                            match = re.search(r'DID=(\d+)', str(val))
                            if match:
                                dog_ids.add(match.group(1))

            # Get from people details (dogs bred/owned)
            people_file = os.path.join(config.OUTPUT_DIR, 'people_details.csv')
            if os.path.exists(people_file):
                df = pd.read_csv(people_file)
                for col in df.columns:
                    for val in df[col].dropna():
                        matches = re.findall(r'DID=(\d+)', str(val))
                        dog_ids.update(matches)

            dog_ids = list(dog_ids)

        if max_dogs and len(dog_ids) > max_dogs:
            dog_ids = dog_ids[:max_dogs]

        logger.info(f"Fetching health data for {len(dog_ids)} dogs")

        all_health = []
        for did in tqdm(dog_ids, desc="Fetching health data"):
            health = self.fetch_dog_health_data(did)
            if health:
                all_health.append(health)
            self._rate_limit()

        # Save
        output_file = os.path.join(config.OUTPUT_DIR, 'dogs_health.csv')
        df = pd.DataFrame(all_health)
        df.to_csv(output_file, index=False)
        logger.info(f"Saved health data for {len(all_health)} dogs to {output_file}")

        return df


def main():
    import argparse

    parser = argparse.ArgumentParser(description="Scrape health and litter data")
    parser.add_argument('--mode', choices=['litters', 'health', 'both'], default='both')
    parser.add_argument('--start-year', type=int, default=1990)
    parser.add_argument('--end-year', type=int, default=2026)
    parser.add_argument('--max-dogs', type=int, help='Max dogs for health data')

    args = parser.parse_args()

    scraper = HealthLitterScraper()

    if args.mode in ['litters', 'both']:
        logger.info("Scraping litters...")
        scraper.scrape_litters_comprehensive(args.start_year, args.end_year)

    if args.mode in ['health', 'both']:
        logger.info("Building health database...")
        scraper.build_health_database(max_dogs=args.max_dogs)


if __name__ == "__main__":
    main()
