#!/usr/bin/env python3
"""
Build a searchable SQLite database from scraped CSV files

This script:
1. Reads all CSV files from the output directory
2. Cleans and normalizes the data
3. Creates a SQLite database with proper indexes
4. Provides query utilities for searching
"""

import os
import sqlite3
import pandas as pd
from typing import List, Optional
import logging
import re

import config

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

DATABASE_FILE = os.path.join(config.OUTPUT_DIR, "bernergarde.db")


def clean_column_names(df: pd.DataFrame) -> pd.DataFrame:
    """Normalize column names"""
    df.columns = [
        re.sub(r'[^\w]', '_', col.lower().strip())
        .replace('__', '_')
        .strip('_')
        for col in df.columns
    ]
    return df


def create_database():
    """Create SQLite database with proper schema"""
    conn = sqlite3.connect(DATABASE_FILE)
    cursor = conn.cursor()

    # Breeders/People table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS people (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bg_person_id TEXT UNIQUE,
            first_name TEXT,
            last_name TEXT,
            kennel_name TEXT,
            city TEXT,
            state TEXT,
            country TEXT,
            email TEXT,
            phone TEXT,
            website TEXT,
            raw_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')

    # Dogs table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS dogs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bg_dog_id TEXT UNIQUE,
            registered_name TEXT,
            call_name TEXT,
            registration_number TEXT,
            dna_registration TEXT,
            microchip TEXT,
            sex TEXT,
            color TEXT,
            birth_date TEXT,
            death_date TEXT,
            breeder_id TEXT,
            breeder_name TEXT,
            owner_id TEXT,
            owner_name TEXT,
            sire_id TEXT,
            sire_name TEXT,
            dam_id TEXT,
            dam_name TEXT,
            kennel_name TEXT,
            health_hip TEXT,
            health_elbow TEXT,
            health_heart TEXT,
            health_eye TEXT,
            titles TEXT,
            raw_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (breeder_id) REFERENCES people(bg_person_id),
            FOREIGN KEY (owner_id) REFERENCES people(bg_person_id)
        )
    ''')

    # Litters table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS litters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bg_litter_id TEXT UNIQUE,
            whelp_date TEXT,
            birth_year INTEGER,
            breeder_id TEXT,
            breeder_name TEXT,
            kennel_name TEXT,
            sire_id TEXT,
            sire_name TEXT,
            dam_id TEXT,
            dam_name TEXT,
            country TEXT,
            puppy_count INTEGER,
            raw_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (breeder_id) REFERENCES people(bg_person_id),
            FOREIGN KEY (sire_id) REFERENCES dogs(bg_dog_id),
            FOREIGN KEY (dam_id) REFERENCES dogs(bg_dog_id)
        )
    ''')

    # Create indexes for faster searching
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_people_last_name ON people(last_name)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_people_kennel ON people(kennel_name)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_people_state ON people(state)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_people_country ON people(country)')

    cursor.execute('CREATE INDEX IF NOT EXISTS idx_dogs_name ON dogs(registered_name)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_dogs_call_name ON dogs(call_name)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_dogs_breeder ON dogs(breeder_name)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_dogs_kennel ON dogs(kennel_name)')

    cursor.execute('CREATE INDEX IF NOT EXISTS idx_litters_year ON litters(birth_year)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_litters_breeder ON litters(breeder_name)')

    conn.commit()
    conn.close()
    logger.info(f"Database created at {DATABASE_FILE}")


def import_people_csv():
    """Import people/breeders from CSV files"""
    conn = sqlite3.connect(DATABASE_FILE)

    csv_files = [
        config.BREEDERS_CSV,
        config.PEOPLE_CSV,
        os.path.join(config.OUTPUT_DIR, "breeders_by_location.csv"),
        os.path.join(config.OUTPUT_DIR, "breeders_by_name.csv"),
        os.path.join(config.OUTPUT_DIR, "people_by_location.csv"),
        os.path.join(config.OUTPUT_DIR, "people_by_name.csv"),
    ]

    total_imported = 0

    for csv_file in csv_files:
        if not os.path.exists(csv_file):
            continue

        try:
            df = pd.read_csv(csv_file)
            df = clean_column_names(df)

            # Map columns to our schema
            column_mapping = {
                'bg_person_id': ['bg_person_id', 'person_id', 'id'],
                'first_name': ['first_name', 'firstname', 'first'],
                'last_name': ['last_name', 'lastname', 'last', 'name'],
                'kennel_name': ['kennel_name', 'kennel'],
                'city': ['city'],
                'state': ['state', 'state_province', 'province'],
                'country': ['country'],
                'email': ['email', 'e_mail'],
                'phone': ['phone', 'telephone'],
                'website': ['website', 'web', 'url'],
            }

            for target, sources in column_mapping.items():
                for source in sources:
                    if source in df.columns and target not in df.columns:
                        df[target] = df[source]
                        break

            # Add raw data column
            df['raw_data'] = df.apply(lambda x: x.to_json(), axis=1)

            # Keep only columns in our schema
            valid_cols = list(column_mapping.keys()) + ['raw_data']
            df = df[[c for c in valid_cols if c in df.columns]]

            # Insert with conflict handling
            for _, row in df.iterrows():
                try:
                    row_dict = row.dropna().to_dict()
                    if row_dict:
                        cols = ', '.join(row_dict.keys())
                        placeholders = ', '.join(['?' for _ in row_dict])
                        sql = f'INSERT OR IGNORE INTO people ({cols}) VALUES ({placeholders})'
                        conn.execute(sql, list(row_dict.values()))
                        total_imported += 1
                except Exception as e:
                    logger.debug(f"Row insert error: {e}")

            conn.commit()
            logger.info(f"Imported from {csv_file}")

        except Exception as e:
            logger.error(f"Error importing {csv_file}: {e}")

    conn.close()
    logger.info(f"Total people records imported: {total_imported}")


def import_dogs_csv():
    """Import dogs from CSV files"""
    conn = sqlite3.connect(DATABASE_FILE)

    csv_file = config.DOGS_CSV
    if not os.path.exists(csv_file):
        logger.warning(f"Dogs CSV not found: {csv_file}")
        return

    try:
        df = pd.read_csv(csv_file)
        df = clean_column_names(df)

        # Map columns
        column_mapping = {
            'bg_dog_id': ['bg_dog_id', 'dog_id', 'id'],
            'registered_name': ['registered_name', 'name', 'reg_name'],
            'call_name': ['call_name', 'callname'],
            'registration_number': ['registration_number', 'reg_number', 'registration'],
            'sex': ['sex', 'gender'],
            'birth_date': ['birth_date', 'birthdate', 'dob', 'whelp_date'],
            'breeder_name': ['breeder_name', 'breeder', 'breeder_last_name'],
            'kennel_name': ['kennel_name', 'kennel'],
            'sire_name': ['sire_name', 'sire'],
            'dam_name': ['dam_name', 'dam'],
        }

        for target, sources in column_mapping.items():
            for source in sources:
                if source in df.columns and target not in df.columns:
                    df[target] = df[source]
                    break

        df['raw_data'] = df.apply(lambda x: x.to_json(), axis=1)

        valid_cols = list(column_mapping.keys()) + ['raw_data']
        df = df[[c for c in valid_cols if c in df.columns]]

        total_imported = 0
        for _, row in df.iterrows():
            try:
                row_dict = row.dropna().to_dict()
                if row_dict:
                    cols = ', '.join(row_dict.keys())
                    placeholders = ', '.join(['?' for _ in row_dict])
                    sql = f'INSERT OR IGNORE INTO dogs ({cols}) VALUES ({placeholders})'
                    conn.execute(sql, list(row_dict.values()))
                    total_imported += 1
            except Exception as e:
                logger.debug(f"Row insert error: {e}")

        conn.commit()
        logger.info(f"Total dogs imported: {total_imported}")

    except Exception as e:
        logger.error(f"Error importing dogs: {e}")

    conn.close()


def import_litters_csv():
    """Import litters from CSV files"""
    conn = sqlite3.connect(DATABASE_FILE)

    # Find all litter CSV files
    litter_files = [config.LITTERS_CSV]
    for f in os.listdir(config.OUTPUT_DIR):
        if f.startswith('litters_') and f.endswith('.csv'):
            litter_files.append(os.path.join(config.OUTPUT_DIR, f))

    total_imported = 0

    for csv_file in litter_files:
        if not os.path.exists(csv_file):
            continue

        try:
            df = pd.read_csv(csv_file)
            df = clean_column_names(df)

            column_mapping = {
                'bg_litter_id': ['bg_litter_id', 'litter_id', 'id'],
                'whelp_date': ['whelp_date', 'birth_date', 'date'],
                'birth_year': ['birth_year', 'year'],
                'breeder_name': ['breeder_name', 'breeder', 'breeder_last_name'],
                'kennel_name': ['kennel_name', 'kennel'],
                'sire_name': ['sire_name', 'sire'],
                'dam_name': ['dam_name', 'dam'],
                'country': ['country', 'country_of_birth'],
            }

            for target, sources in column_mapping.items():
                for source in sources:
                    if source in df.columns and target not in df.columns:
                        df[target] = df[source]
                        break

            df['raw_data'] = df.apply(lambda x: x.to_json(), axis=1)

            valid_cols = list(column_mapping.keys()) + ['raw_data']
            df = df[[c for c in valid_cols if c in df.columns]]

            for _, row in df.iterrows():
                try:
                    row_dict = row.dropna().to_dict()
                    if row_dict:
                        cols = ', '.join(row_dict.keys())
                        placeholders = ', '.join(['?' for _ in row_dict])
                        sql = f'INSERT OR IGNORE INTO litters ({cols}) VALUES ({placeholders})'
                        conn.execute(sql, list(row_dict.values()))
                        total_imported += 1
                except Exception as e:
                    logger.debug(f"Row insert error: {e}")

            conn.commit()
            logger.info(f"Imported litters from {csv_file}")

        except Exception as e:
            logger.error(f"Error importing {csv_file}: {e}")

    conn.close()
    logger.info(f"Total litters imported: {total_imported}")


def build_database():
    """Main function to build the complete database"""
    logger.info("Building Bernergarde database...")

    create_database()
    import_people_csv()
    import_dogs_csv()
    import_litters_csv()

    # Print summary
    conn = sqlite3.connect(DATABASE_FILE)
    cursor = conn.cursor()

    cursor.execute("SELECT COUNT(*) FROM people")
    people_count = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM dogs")
    dogs_count = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM litters")
    litters_count = cursor.fetchone()[0]

    conn.close()

    logger.info("=" * 50)
    logger.info("DATABASE BUILD COMPLETE")
    logger.info("=" * 50)
    logger.info(f"People/Breeders: {people_count}")
    logger.info(f"Dogs: {dogs_count}")
    logger.info(f"Litters: {litters_count}")
    logger.info(f"Database file: {DATABASE_FILE}")


if __name__ == "__main__":
    build_database()
