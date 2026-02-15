# Database Expansion Guide

## Current Status

**Active Process**: Scraping recent litters (2020+) for dog IDs
- **Litters to scrape**: 604
- **Output file**: `scraper/output/recent_litters_details.csv`
- **Check progress**: `tail -f /private/tmp/claude-502/-Users-zlhockman-Development-burnese-finder/tasks/b4fc30e.output`

## When Scraper Completes

### 1. Import New Litter Data

The scraper creates `recent_litters_details.csv` with columns:
- `bg_litter_id`
- `sire_dog_id` - **KEY: Dog ID for sire**
- `dam_dog_id` - **KEY: Dog ID for dam**
- `sire`, `dam` - Dog names
- `breeder_person_id`
- `whelp_date`
- `puppy_count`, `puppy_ids`

### 2. Update Database Import Script

Edit `app/Console/Commands/ImportData.php` to import the new litter data:

```php
// In importLitters() method, update to use recent_litters_details.csv
$file = "{$path}/recent_litters_details.csv";
if (file_exists($file)) {
    // Process and import with dog IDs
    $litter->sire_id = $data['sire_dog_id'] ?? null;
    $litter->dam_id = $data['dam_dog_id'] ?? null;
}
```

### 3. Re-import Litters

```bash
# Import just the new litter data
docker exec bernese_app php artisan migrate:fresh --force
docker exec bernese_app php artisan import:data --fresh

# The new sire_id and dam_id will be populated directly from scraper
```

### 4. Expected Results

After importing the scraped data:
- **Before**: 178 litters linked (6.8%)
- **After**: ~600 recent litters with dog IDs (23%)
- **Active breeding dogs**: Should increase from 32 to ~100+

## Expanding Further

### Scrape ALL Litters (2,604 total)

```bash
cd scraper
python3 scrape_litter_details.py
```

**Time estimate**: 2-3 hours
**Rate limiting**: 2 seconds per request
**Output**: `scraper/output/litters_details.csv`

### Scrape More Dogs

Get details for all dogs in the database:

```bash
cd scraper
python3 scrape_dog_details.py
```

This will:
- Download dog photos
- Get complete health clearances
- Get full pedigree information
- Take 4-6 hours for ~8,000 dogs

### Scrape Health Clearances

```bash
cd scraper
python3 scrape_health_litters.py --mode health
```

Gets OFA clearances for all dogs.

## Available Scrapers

| Script | Purpose | Time |
|--------|---------|------|
| `scrape_recent_litters.py` | ✅ RUNNING - Get dog IDs for 2020+ litters | 20-30min |
| `scrape_litter_details.py` | Get dog IDs for ALL litters | 2-3hrs |
| `scrape_dog_details.py` | Full details for all dogs | 4-6hrs |
| `scrape_breeder_details.py` | Breeder contact info & kennels | 2-4hrs |
| `scrape_health_litters.py` | Health clearances | 3-5hrs |
| `run_scraper.py` | Get all breeders | 1-2hrs |

## Monitoring Progress

```bash
# Check what's running
docker ps

# Follow scraper output
tail -f /private/tmp/claude-502/-Users-zlhockman-Development-burnese-finder/tasks/b4fc30e.output

# Check output files
ls -lh scraper/output/*.csv

# Count records in CSV
wc -l scraper/output/recent_litters_details.csv
```

## Data Quality Tips

1. **Name Matching Improvements**
   - The current `litters:link` command matches exact names
   - Could be improved with fuzzy matching (Levenshtein distance)
   - Many names have slight variations ("VON" vs "VOM", extra spaces, etc.)

2. **Incremental Updates**
   - Run scrapers monthly to get new litters
   - Add to existing data rather than re-scraping everything

3. **Error Handling**
   - Scrapers save checkpoints every 50 records
   - Safe to stop and restart
   - Will resume from where it left off

## Configuration

Edit `scraper/config.py`:

```python
REQUEST_DELAY_SECONDS = 2.0  # Don't reduce - be respectful
DOWNLOAD_IMAGES = True        # Set False to skip images
BASE_URL = "https://www.bernergarde.org"
```

## Troubleshooting

**SSL Warnings**: Safe to ignore - cosmetic issue with macOS LibreSSL

**Rate Limiting**: If you get blocked, increase `REQUEST_DELAY_SECONDS`

**Missing Data**: Some litters don't have dog IDs in the database - this is normal

**Session Errors**: Scraper will auto-retry with exponential backoff
