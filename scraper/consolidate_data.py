#!/usr/bin/env python3
"""
Consolidate all scraped data into two clean spreadsheets:
1. ALL_BREEDERS.csv - One row per breeder with all details
2. ALL_DOGS.csv - One row per dog with all details

This is the final output for analysis.
"""

import os
import pandas as pd
import re
from typing import Dict, List, Optional
import logging
from datetime import datetime
import glob

import config

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


def load_all_csvs(pattern: str) -> pd.DataFrame:
    """Load and combine all CSVs matching pattern"""
    files = glob.glob(os.path.join(config.OUTPUT_DIR, pattern))
    dfs = []
    for f in files:
        try:
            df = pd.read_csv(f)
            dfs.append(df)
            logger.info(f"Loaded {len(df)} rows from {os.path.basename(f)}")
        except Exception as e:
            logger.warning(f"Error loading {f}: {e}")

    if dfs:
        return pd.concat(dfs, ignore_index=True)
    return pd.DataFrame()


def extract_id_from_link(link: str) -> Optional[str]:
    """Extract ID from a link like 'People_Detail?PID=12345'"""
    if pd.isna(link):
        return None
    match = re.search(r'(?:PID|DID|LID)=(\d+)', str(link))
    return match.group(1) if match else None


def consolidate_breeders() -> pd.DataFrame:
    """
    Create one comprehensive breeder datasheet with columns:
    - breeder_id
    - name (full name)
    - first_name
    - last_name
    - kennel_name
    - city
    - state
    - country
    - email
    - phone
    - website
    - dogs_bred_count
    - dogs_owned_count
    - litters_count
    - years_active
    - profile_url
    """
    logger.info("Consolidating breeder data...")

    # Load all people data
    people_basic = load_all_csvs("breeders*.csv")
    people_basic = pd.concat([people_basic, load_all_csvs("people*.csv")], ignore_index=True)

    people_details = pd.DataFrame()
    details_file = os.path.join(config.OUTPUT_DIR, "people_details.csv")
    if os.path.exists(details_file):
        people_details = pd.read_csv(details_file)
        logger.info(f"Loaded {len(people_details)} detailed records")

    # Create master breeder dict keyed by ID
    breeders = {}

    # Process basic data first
    for _, row in people_basic.iterrows():
        # Get breeder ID
        bid = str(row.get('people_id', ''))
        if not bid:
            link = row.get('people_id_link', '') or row.get('name_link', '')
            bid = extract_id_from_link(link)

        if not bid:
            continue

        if bid not in breeders:
            breeders[bid] = {
                'breeder_id': bid,
                'name': '',
                'first_name': '',
                'last_name': '',
                'kennel_name': '',
                'city': '',
                'state': '',
                'country': '',
                'email': '',
                'phone': '',
                'website': '',
                'dogs_bred_count': 0,
                'dogs_owned_count': 0,
                'litters_count': 0,
                'profile_url': f"https://bernergarde.org/DB/People_Detail?PID={bid}"
            }

        # Fill in data
        b = breeders[bid]

        # Name
        name = str(row.get('name', '')).replace('\xa0', ' ').strip()
        if name and not b['name']:
            b['name'] = name
            # Try to parse first/last
            if ',' in name:
                parts = name.split(',')
                b['last_name'] = parts[0].strip()
                b['first_name'] = parts[1].strip() if len(parts) > 1 else ''

        # Location
        city_state = str(row.get('city___state', '') or row.get('city_state', '')).replace('\xa0', ' ')
        if city_state and ',' in city_state:
            parts = city_state.rsplit(',', 1)
            if not b['city']:
                b['city'] = parts[0].strip()
            if not b['state'] and len(parts) > 1:
                b['state'] = parts[1].strip()

        country = str(row.get('country', '')).replace('\xa0', ' ').strip()
        if country and not b['country']:
            b['country'] = country

        kennel = str(row.get('kennel_name', '') or row.get('kennel', '')).replace('\xa0', ' ').strip()
        if kennel and not b['kennel_name']:
            b['kennel_name'] = kennel

    # Enrich with detailed data
    for _, row in people_details.iterrows():
        bid = str(row.get('bg_person_id', ''))
        if not bid or bid not in breeders:
            continue

        b = breeders[bid]

        # Fill missing fields
        for field in ['first_name', 'last_name', 'kennel_name', 'city', 'state', 'country',
                      'email', 'phone', 'website']:
            if not b[field]:
                val = str(row.get(field, '')).replace('\xa0', ' ').strip()
                if val and val.lower() not in ['nan', 'none', '']:
                    b[field] = val

        # Count dogs bred/owned
        dogs_bred = row.get('dogs_bred_count', 0)
        dogs_owned = row.get('dogs_owned_count', 0)
        if pd.notna(dogs_bred):
            b['dogs_bred_count'] = max(b['dogs_bred_count'], int(dogs_bred))
        if pd.notna(dogs_owned):
            b['dogs_owned_count'] = max(b['dogs_owned_count'], int(dogs_owned))

    # Count litters per breeder from litters data
    litters_df = load_all_csvs("litters*.csv")
    if len(litters_df) > 0:
        for _, row in litters_df.iterrows():
            breeder_link = str(row.get('breeder_link', '') or row.get('breeder_name_link', ''))
            bid = extract_id_from_link(breeder_link)
            if bid and bid in breeders:
                breeders[bid]['litters_count'] += 1

    # Convert to DataFrame
    df = pd.DataFrame(list(breeders.values()))

    # Clean up empty strings
    df = df.replace('', pd.NA)
    df = df.replace('nan', pd.NA)

    # Sort by name
    df = df.sort_values(['last_name', 'first_name'])

    # Reorder columns
    columns = ['breeder_id', 'name', 'first_name', 'last_name', 'kennel_name',
               'city', 'state', 'country', 'email', 'phone', 'website',
               'dogs_bred_count', 'dogs_owned_count', 'litters_count', 'profile_url']
    df = df[[c for c in columns if c in df.columns]]

    output_path = os.path.join(config.OUTPUT_DIR, 'ALL_BREEDERS.csv')
    df.to_csv(output_path, index=False)
    logger.info(f"Saved {len(df)} breeders to {output_path}")

    return df


def consolidate_dogs() -> pd.DataFrame:
    """
    Create one comprehensive dog datasheet with columns:
    - dog_id
    - registered_name
    - call_name
    - sex
    - color
    - birth_date
    - death_date
    - age_at_death_years
    - registration_number
    - dna_number
    - microchip
    - sire_name
    - sire_id
    - dam_name
    - dam_id
    - breeder_name
    - breeder_id
    - kennel_name
    - owner_name
    - owner_id
    - hip_rating
    - elbow_rating
    - heart_rating
    - eye_rating
    - dm_status
    - titles
    - image_url
    - image_path
    - profile_url
    """
    logger.info("Consolidating dog data...")

    # Load all dog data
    dogs_basic = load_all_csvs("dogs*.csv")
    dogs_details = pd.DataFrame()
    dogs_health = pd.DataFrame()

    details_file = os.path.join(config.OUTPUT_DIR, "dogs_details.csv")
    if os.path.exists(details_file):
        dogs_details = pd.read_csv(details_file)
        logger.info(f"Loaded {len(dogs_details)} detailed dog records")

    health_file = os.path.join(config.OUTPUT_DIR, "dogs_health.csv")
    if os.path.exists(health_file):
        dogs_health = pd.read_csv(health_file)
        logger.info(f"Loaded {len(dogs_health)} dog health records")

    # Create master dog dict
    dogs = {}

    def process_dog_row(row, source='basic'):
        did = str(row.get('bg_dog_id', '') or row.get('dog_id', ''))
        if not did:
            link = row.get('dog_id_link', '') or row.get('name_link', '')
            did = extract_id_from_link(link)

        if not did:
            return

        if did not in dogs:
            dogs[did] = {
                'dog_id': did,
                'registered_name': '',
                'call_name': '',
                'sex': '',
                'color': '',
                'birth_date': '',
                'death_date': '',
                'age_at_death_years': None,
                'registration_number': '',
                'dna_number': '',
                'microchip': '',
                'sire_name': '',
                'sire_id': '',
                'dam_name': '',
                'dam_id': '',
                'breeder_name': '',
                'breeder_id': '',
                'kennel_name': '',
                'owner_name': '',
                'owner_id': '',
                'hip_rating': '',
                'elbow_rating': '',
                'heart_rating': '',
                'eye_rating': '',
                'dm_status': '',
                'titles': '',
                'image_url': '',
                'image_path': '',
                'profile_url': f"https://bernergarde.org/DB/Dog_Detail?DID={did}"
            }

        d = dogs[did]

        # Map various field names to canonical names
        field_mappings = {
            'registered_name': ['registered_name', 'name', 'reg_name'],
            'call_name': ['call_name', 'callname'],
            'sex': ['sex', 'gender'],
            'color': ['color', 'colour'],
            'birth_date': ['birth_date', 'birthdate', 'dob', 'whelped', 'whelp_date'],
            'death_date': ['death_date', 'died', 'date_of_death'],
            'registration_number': ['registration_number', 'registration', 'reg_number', 'akc'],
            'dna_number': ['dna_number', 'dna', 'dna_registration'],
            'microchip': ['microchip', 'chip'],
            'sire_name': ['sire_name', 'sire'],
            'dam_name': ['dam_name', 'dam'],
            'breeder_name': ['breeder_name', 'breeder', 'bred_by'],
            'kennel_name': ['kennel_name', 'kennel'],
            'owner_name': ['owner_name', 'owner', 'owned_by'],
            'titles': ['titles', 'title', 'awards'],
            'hip_rating': ['hip_rating', 'hip', 'hips', 'ofa_hip', 'health_hip'],
            'elbow_rating': ['elbow_rating', 'elbow', 'elbows', 'ofa_elbow', 'health_elbow'],
            'heart_rating': ['heart_rating', 'heart', 'cardiac', 'ofa_heart', 'health_heart'],
            'eye_rating': ['eye_rating', 'eye', 'eyes', 'cerf', 'ofa_eye', 'health_eye'],
            'dm_status': ['dm_status', 'dm', 'degenerative_myelopathy', 'health_dm'],
            'image_url': ['image_url', 'image_urls'],
            'image_path': ['image_path', 'image_paths'],
        }

        for canonical, variants in field_mappings.items():
            if d[canonical]:
                continue
            for v in variants:
                if v in row.index:
                    val = str(row[v]).replace('\xa0', ' ').strip()
                    if val and val.lower() not in ['nan', 'none', '']:
                        d[canonical] = val
                        break

        # Extract IDs from links
        for parent in ['sire', 'dam', 'breeder', 'owner']:
            if not d[f'{parent}_id']:
                link = row.get(f'{parent}_link', '')
                pid = extract_id_from_link(str(link))
                if pid:
                    d[f'{parent}_id'] = pid

        # Age at death
        if not d['age_at_death_years']:
            age = row.get('age_at_death_years', '')
            if pd.notna(age):
                d['age_at_death_years'] = age

    # Process all sources
    for _, row in dogs_basic.iterrows():
        process_dog_row(row, 'basic')

    for _, row in dogs_details.iterrows():
        process_dog_row(row, 'details')

    for _, row in dogs_health.iterrows():
        process_dog_row(row, 'health')

    # Also extract dogs from litter data
    litters_df = load_all_csvs("litters*.csv")
    for _, row in litters_df.iterrows():
        # Sire
        sire_link = str(row.get('sire_link', ''))
        sire_id = extract_id_from_link(sire_link)
        if sire_id and sire_id not in dogs:
            dogs[sire_id] = {
                'dog_id': sire_id,
                'registered_name': str(row.get('sire_name', '') or row.get('sire', '')),
                'sex': 'Male',
                'profile_url': f"https://bernergarde.org/DB/Dog_Detail?DID={sire_id}"
            }
            # Fill defaults
            for k in ['call_name', 'color', 'birth_date', 'death_date', 'registration_number',
                      'dna_number', 'microchip', 'sire_name', 'sire_id', 'dam_name', 'dam_id',
                      'breeder_name', 'breeder_id', 'kennel_name', 'owner_name', 'owner_id',
                      'hip_rating', 'elbow_rating', 'heart_rating', 'eye_rating', 'dm_status',
                      'titles', 'image_url', 'image_path']:
                if k not in dogs[sire_id]:
                    dogs[sire_id][k] = ''
            dogs[sire_id]['age_at_death_years'] = None

        # Dam
        dam_link = str(row.get('dam_link', ''))
        dam_id = extract_id_from_link(dam_link)
        if dam_id and dam_id not in dogs:
            dogs[dam_id] = {
                'dog_id': dam_id,
                'registered_name': str(row.get('dam_name', '') or row.get('dam', '')),
                'sex': 'Female',
                'profile_url': f"https://bernergarde.org/DB/Dog_Detail?DID={dam_id}"
            }
            for k in ['call_name', 'color', 'birth_date', 'death_date', 'registration_number',
                      'dna_number', 'microchip', 'sire_name', 'sire_id', 'dam_name', 'dam_id',
                      'breeder_name', 'breeder_id', 'kennel_name', 'owner_name', 'owner_id',
                      'hip_rating', 'elbow_rating', 'heart_rating', 'eye_rating', 'dm_status',
                      'titles', 'image_url', 'image_path']:
                if k not in dogs[dam_id]:
                    dogs[dam_id][k] = ''
            dogs[dam_id]['age_at_death_years'] = None

    # Convert to DataFrame
    df = pd.DataFrame(list(dogs.values()))

    # Clean up
    df = df.replace('', pd.NA)
    df = df.replace('nan', pd.NA)

    # Sort by name
    if 'registered_name' in df.columns:
        df = df.sort_values('registered_name')

    # Reorder columns
    columns = ['dog_id', 'registered_name', 'call_name', 'sex', 'color',
               'birth_date', 'death_date', 'age_at_death_years',
               'registration_number', 'dna_number', 'microchip',
               'sire_name', 'sire_id', 'dam_name', 'dam_id',
               'breeder_name', 'breeder_id', 'kennel_name',
               'owner_name', 'owner_id',
               'hip_rating', 'elbow_rating', 'heart_rating', 'eye_rating', 'dm_status',
               'titles', 'image_url', 'image_path', 'profile_url']
    df = df[[c for c in columns if c in df.columns]]

    output_path = os.path.join(config.OUTPUT_DIR, 'ALL_DOGS.csv')
    df.to_csv(output_path, index=False)
    logger.info(f"Saved {len(df)} dogs to {output_path}")

    return df


def main():
    logger.info("=== CONSOLIDATING ALL DATA ===")

    breeders = consolidate_breeders()
    dogs = consolidate_dogs()

    print("\n" + "="*60)
    print("DATA CONSOLIDATION COMPLETE")
    print("="*60)
    print(f"\nBREEDERS: {len(breeders)} total")
    print(f"  -> output/ALL_BREEDERS.csv")
    print(f"\nDOGS: {len(dogs)} total")
    print(f"  -> output/ALL_DOGS.csv")
    print("\nThese are your two master spreadsheets with all data combined.")


if __name__ == "__main__":
    main()
