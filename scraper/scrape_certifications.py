#!/usr/bin/env python3
"""
Scrape health certification data for active breeding dogs from BernerGarde.

The certifications section on each dog's detail page has a table with columns:
  Cert/Test By | Tested | Cert # / Report # | Findings | Test Date

This script focuses on dogs in our active breeding search (alive with recent litters).
Output: output/health_certifications.csv
"""

import os
import sys
import re
import time
import logging
import pandas as pd
from typing import Dict, List, Optional

sys.path.insert(0, os.path.dirname(__file__))
from session_manager import SessionManager
import config

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

OUTPUT_FILE = os.path.join(config.OUTPUT_DIR, 'health_certifications.csv')


def parse_certifications(soup) -> Dict:
    """
    Parse the certifications table from a BernerGarde dog detail page.

    Returns a dict with canonical health fields:
      hip_rating, elbow_rating, heart_status, eye_status, dm_status, dna_status
    """
    certs = []

    # Find all tables on the page and look for one with health-related headers
    for table in soup.find_all('table'):
        headers = [th.get_text(strip=True).lower() for th in table.find_all('th')]
        if not headers:
            # Try first row td as headers
            first_row = table.find('tr')
            if first_row:
                headers = [td.get_text(strip=True).lower() for td in first_row.find_all('td')]

        # Identify the certifications table by looking for "findings" or "cert" in headers
        if any('finding' in h or 'cert' in h or 'tested' in h for h in headers):
            rows = table.find_all('tr')
            # Skip header row(s)
            data_rows = [r for r in rows if r.find('td')]
            for row in data_rows:
                cells = [td.get_text(strip=True) for td in row.find_all('td')]
                if len(cells) >= 4:
                    # Columns: Cert/Test By (org) | Tested (test type) | Cert # / Report # | Findings | Test Date
                    org = cells[0]
                    test_type = cells[1]
                    cert_number = cells[2] if len(cells) > 2 else ''
                    findings = cells[3] if len(cells) > 3 else ''
                    test_date = cells[4] if len(cells) > 4 else ''

                    if test_type and findings and test_type.lower() not in ['tested', '']:
                        certs.append({
                            'test_type': test_type,
                            'org': org,
                            'cert_number': cert_number,
                            'findings': findings,
                            'test_date': test_date,
                        })
            break  # Found the right table

    if not certs:
        return {}

    return _map_certifications_to_fields(certs)


def _map_certifications_to_fields(certs: List[Dict]) -> Dict:
    """
    Map raw certification rows to canonical health field names.
    When multiple results exist for the same test, keep the most informative one.
    """
    result = {}

    # Collect all results per category
    hips = []
    elbows = []
    hearts = []
    eyes = []
    dm_results = []
    dna_results = []

    for cert in certs:
        test = cert['test_type'].lower()
        findings = cert['findings'].strip()
        cert_num = cert.get('cert_number', '').strip()
        test_date = cert.get('test_date', '').strip()

        if not findings or findings.lower() in ['', 'n/a', 'none', '-']:
            continue

        # Build display value with cert number if available
        display = findings
        if cert_num and cert_num not in ['', 'n/a', '-']:
            display = f"{findings} ({cert_num})"

        if 'hip' in test:
            hips.append({'display': display, 'findings': findings, 'date': test_date})
        elif 'elbow' in test:
            elbows.append({'display': display, 'findings': findings, 'date': test_date})
        elif 'heart' in test or 'cardiac' in test:
            hearts.append({'display': display, 'findings': findings, 'date': test_date})
        elif 'eye' in test or 'cerf' in test or 'caer' in test:
            eyes.append({'display': display, 'findings': findings, 'date': test_date})
        elif 'dm' in test or 'degenerative' in test or 'myelopathy' in test or 'sod1' in test:
            dm_results.append({'display': display, 'findings': findings, 'date': test_date})
        elif 'dna' in test or 'parentage' in test:
            dna_results.append({'display': display, 'findings': findings, 'date': test_date})

    # For hips/elbows: prefer OFA grade ratings (Excellent > Good > Fair > etc.)
    hip_priority = ['excellent', 'good', 'fair', 'borderline', 'mild', 'moderate', 'severe']
    if hips:
        hips.sort(key=lambda x: next((i for i, g in enumerate(hip_priority) if g in x['findings'].lower()), 99))
        result['hip_rating'] = hips[0]['display']

    if elbows:
        result['elbow_rating'] = elbows[0]['display']

    # For heart/eye: most recent or first result
    if hearts:
        result['heart_status'] = hearts[-1]['display']  # Use most recent (last in list)

    if eyes:
        result['eye_status'] = eyes[-1]['display']  # Use most recent

    # For DM: priority is Affected > Carrier > Clear (show worst case)
    if dm_results:
        dm_priority = ['affected', 'carrier', 'clear', 'parentage']
        dm_results.sort(key=lambda x: next((i for i, g in enumerate(dm_priority) if g in x['findings'].lower()), 99))
        # Combine multiple DM results
        dm_displays = [r['display'] for r in dm_results]
        result['dm_status'] = ' | '.join(dm_displays)

    if dna_results:
        result['dna_status'] = dna_results[0]['display']

    return result


def fetch_dog_certifications(session: SessionManager, dog_id: str) -> Optional[Dict]:
    """Fetch and parse certifications for a single dog."""
    url = f"{config.BASE_URL}/DB/Dog_Detail?DogID={dog_id}"
    try:
        soup = session.get_page(url)
        if not soup:
            return None

        data = {'bg_dog_id': str(dog_id)}
        cert_data = parse_certifications(soup)
        data.update(cert_data)
        data['has_certifications'] = len(cert_data) > 0
        return data

    except Exception as e:
        logger.warning(f"Error fetching dog {dog_id}: {e}")
        return {'bg_dog_id': str(dog_id), 'has_certifications': False}


def load_target_dog_ids() -> List[str]:
    """
    Load dog IDs to scrape. Prioritizes active breeding dogs,
    then falls back to all dogs in the merged CSV.
    """
    dog_ids = set()

    # Primary: recent litters (most likely to have certifications)
    litters_file = os.path.join(config.OUTPUT_DIR, 'recent_litters_details.csv')
    if os.path.exists(litters_file):
        df = pd.read_csv(litters_file)
        for col in ['sire_dog_id', 'dam_dog_id']:
            if col in df.columns:
                ids = df[col].dropna().astype(str).str.replace(r'\.0$', '', regex=True)
                dog_ids.update(ids[ids.str.isdigit()])
        logger.info(f"Loaded {len(dog_ids)} dog IDs from recent litters")

    # Also include all dogs from merged CSV
    dogs_file = os.path.join(config.OUTPUT_DIR, 'ALL_DOGS_MERGED.csv')
    if os.path.exists(dogs_file):
        df = pd.read_csv(dogs_file)
        if 'bg_dog_id' in df.columns:
            ids = df['bg_dog_id'].dropna().astype(str).str.replace(r'\.0$', '', regex=True)
            dog_ids.update(ids[ids.str.isdigit()])
        logger.info(f"Total dog IDs after adding merged CSV: {len(dog_ids)}")

    dog_ids.discard('')
    dog_ids.discard('None')
    dog_ids.discard('nan')
    return sorted(dog_ids, key=lambda x: int(x) if x.isdigit() else 0, reverse=True)  # Newest first


def main():
    import argparse
    parser = argparse.ArgumentParser(description='Scrape health certifications from BernerGarde')
    parser.add_argument('--limit', type=int, help='Max number of dogs to scrape (for testing)')
    parser.add_argument('--active-only', action='store_true',
                        help='Only scrape active breeding dogs (from recent_litters_details.csv)')
    args = parser.parse_args()

    os.makedirs(config.OUTPUT_DIR, exist_ok=True)

    # Load dog IDs
    dog_ids = load_target_dog_ids()

    if args.active_only:
        # Only use IDs from recent litters
        litters_file = os.path.join(config.OUTPUT_DIR, 'recent_litters_details.csv')
        if os.path.exists(litters_file):
            df = pd.read_csv(litters_file)
            active_ids = set()
            for col in ['sire_dog_id', 'dam_dog_id']:
                if col in df.columns:
                    ids = df[col].dropna().astype(str).str.replace(r'\.0$', '', regex=True)
                    active_ids.update(ids[ids.str.isdigit()])
            dog_ids = [d for d in dog_ids if d in active_ids]
            logger.info(f"Active-only mode: {len(dog_ids)} dogs")

    if args.limit:
        dog_ids = dog_ids[:args.limit]
        logger.info(f"Limited to {args.limit} dogs")

    # Load already-scraped IDs to allow resuming
    scraped_ids = set()
    if os.path.exists(OUTPUT_FILE):
        existing = pd.read_csv(OUTPUT_FILE)
        if 'bg_dog_id' in existing.columns:
            scraped_ids = set(existing['bg_dog_id'].astype(str))
        logger.info(f"Resuming: {len(scraped_ids)} already scraped")

    remaining = [d for d in dog_ids if d not in scraped_ids]
    logger.info(f"Scraping certifications for {len(remaining)} dogs...")

    session = SessionManager()
    # Initialize session
    session.get_page(f"{config.BASE_URL}/DB/Dog_Search")
    time.sleep(1)

    results = []
    found_count = 0

    for i, dog_id in enumerate(remaining):
        if i % 25 == 0:
            logger.info(f"Progress: {i}/{len(remaining)} | Found certifications: {found_count}")

        data = fetch_dog_certifications(session, dog_id)
        if data:
            results.append(data)
            if data.get('has_certifications'):
                found_count += 1

        time.sleep(config.REQUEST_DELAY_SECONDS)

        # Checkpoint every 100 dogs
        if len(results) % 100 == 0:
            _save(results, scraped_ids)
            results = []

    # Final save
    if results:
        _save(results, scraped_ids)

    logger.info(f"Done. Dogs with certifications: {found_count}/{len(remaining)}")


def _save(new_results: list, scraped_ids: set):
    """Append new results to the output CSV."""
    if not new_results:
        return

    new_df = pd.DataFrame(new_results)

    if os.path.exists(OUTPUT_FILE):
        existing = pd.read_csv(OUTPUT_FILE)
        combined = pd.concat([existing, new_df], ignore_index=True)
        combined = combined.drop_duplicates(subset=['bg_dog_id'], keep='last')
    else:
        combined = new_df

    combined.to_csv(OUTPUT_FILE, index=False)
    logger.info(f"Saved {len(combined)} records to {OUTPUT_FILE}")


if __name__ == '__main__':
    main()
