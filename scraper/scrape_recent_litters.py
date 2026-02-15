#!/usr/bin/env python3
"""
Scrape recent litter details (2020+) to get sire/dam dog IDs.
This enables linking litters to dogs for active breeding searches.
"""

import os
import sys
import pandas as pd
from scrape_litter_details import LitterDetailScraper
import logging

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

def main():
    # Load all litters
    litters_file = 'output/litters.csv'
    if not os.path.exists(litters_file):
        logger.error(f"Litters file not found: {litters_file}")
        return
    
    df = pd.read_csv(litters_file)
    logger.info(f"Loaded {len(df)} total litters")
    
    # Filter for recent litters (2020+)
    recent = df[df['birth_year'] >= 2020].copy()
    logger.info(f"Found {len(recent)} litters from 2020 onwards")
    
    # Get unique litter IDs
    litter_ids = recent['litter_id'].dropna().astype(int).astype(str).unique().tolist()
    logger.info(f"Scraping {len(litter_ids)} unique recent litter IDs")
    
    # Initialize scraper
    scraper = LitterDetailScraper()
    
    # Check for existing progress
    output_file = 'output/recent_litters_details.csv'
    scraped_ids = set()
    if os.path.exists(output_file):
        existing = pd.read_csv(output_file)
        if 'bg_litter_id' in existing.columns:
            scraped_ids = set(existing['bg_litter_id'].dropna().astype(str))
        logger.info(f"Resuming from {len(scraped_ids)} already scraped")
    
    # Filter out already scraped
    litter_ids = [l for l in litter_ids if l not in scraped_ids]
    logger.info(f"Scraping {len(litter_ids)} remaining litters")
    
    # Scrape
    all_details = []
    for i, litter_id in enumerate(litter_ids):
        if i % 10 == 0:
            logger.info(f"Progress: {i}/{len(litter_ids)}")
        
        details = scraper.fetch_litter_details(litter_id)
        if details:
            all_details.append(details)
        
        scraper._rate_limit()
        
        # Save checkpoint every 50
        if (i + 1) % 50 == 0 and all_details:
            save_checkpoint(all_details, output_file, scraped_ids)
            all_details = []  # Clear after saving
    
    # Final save
    if all_details:
        save_checkpoint(all_details, output_file, scraped_ids)
    
    logger.info("✅ DONE: Recent litters scraped")

def save_checkpoint(new_details, output_file, scraped_ids):
    """Save checkpoint"""
    new_df = pd.DataFrame(new_details)
    
    if os.path.exists(output_file):
        existing = pd.read_csv(output_file)
        combined = pd.concat([existing, new_df], ignore_index=True)
        combined = combined.drop_duplicates(subset=['bg_litter_id'], keep='last')
    else:
        combined = new_df
    
    combined.to_csv(output_file, index=False)
    logging.info(f"✓ Checkpoint saved: {len(combined)} total litters")

if __name__ == "__main__":
    main()
