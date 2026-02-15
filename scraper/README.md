# Bernergarde Database Scraper

**Purpose:** Scrape breeder, dog, litter, and health data from bernergarde.org to find the best Bernese Mountain Dog breeders and dogs.

## Quick Start

```bash
cd /Users/adamcoburn/Development/Bernese/scraper
source venv/bin/activate  # Virtual env already set up

# Run complete data collection (takes several hours)
./run_all.sh
```

## Scripts Overview

| Script | Purpose |
|--------|---------|
| `run_scraper.py` | Collect all breeders by state and name |
| `scrape_details.py` | Get full details (kennel, dogs bred, contact info) |
| `scrape_health_litters.py` | Get litter history and health clearances |
| `analyze_breeders.py` | **Grade breeders and dogs** to find the best |
| `build_database.py` | Create searchable SQLite database |
| `search_database.py` | Query interface |

## Data Collection Steps

### Step 1: Get All Breeders (DONE - 7,115 breeders)
```bash
python run_scraper.py --mode people
```

### Step 2: Get Breeder Details (Running)
```bash
python scrape_details.py --mode people
```
Gets: kennel names, contact info, dogs bred/owned

### Step 3: Get Litter Data (Running)
```bash
python scrape_health_litters.py --mode litters --start-year 1990 --end-year 2026
```
Gets: sire/dam pairings, birth years, puppy lists

### Step 4: Get Dog Health Data
```bash
python scrape_health_litters.py --mode health
```
Gets: OFA clearances (hips, elbows, heart, eyes), DM status, longevity

### Step 5: Download Dog Images
```bash
python scrape_details.py --mode dogs
```
Gets: All available dog photos saved to `output/images/`

## Finding the Best Dog

### Grade Breeders
```bash
python analyze_breeders.py --top-breeders --limit 50
```

### Get Breeder Report
```bash
python analyze_breeders.py --breeder-report "SMITH"
```

### Find Recommended Dogs
```bash
python analyze_breeders.py --recommended-dogs --limit 50
```

### Grade Specific Dog
```bash
python analyze_breeders.py --grade-dog 12345
```

## Grading Criteria

### Breeder Score (A-F)
| Factor | Weight | Description |
|--------|--------|-------------|
| Health Testing | 40% | % of dogs with OFA clearances |
| Longevity | 40% | Average age of offspring (Bernese avg is 7-8 years) |
| Litter Frequency | 20% | 1-2/year is ideal, more is concerning |

### Dog Score (A-F)
| Factor | Weight | Description |
|--------|--------|-------------|
| Health Clearances | 50% | Hip, elbow, heart, eye, DM testing |
| Lineage Health | 30% | Parents' health clearances |
| Breeder Quality | 20% | Breeder's overall grade |

## Output Files

```
output/
├── breeders.csv           # 7,115 breeders from search
├── breeders_by_location.csv
├── breeders_by_name.csv
├── people_details.csv     # Full breeder info with kennels
├── litters.csv            # All litters 1990-2026
├── dogs_health.csv        # Health clearances
├── dogs_details.csv       # Full dog info
├── top_breeders.csv       # Graded breeder rankings
├── recommended_dogs.csv   # Dogs to consider
├── bernergarde.db         # SQLite database
└── images/                # Dog photos
    └── dog_12345_0.jpg
```

## Search the Database

### Interactive Mode
```bash
python search_database.py
```

### Python API
```python
from search_database import BernergardeDB

db = BernergardeDB()

# Find breeders in Colorado
breeders = db.search_breeders(state="CO")

# Find dogs by kennel
dogs = db.get_dogs_by_kennel("Mountain View")

# Get statistics
stats = db.get_database_stats()
```

## Key Health Clearances for Bernese

| Test | What It Checks | Good Result |
|------|---------------|-------------|
| OFA Hips | Hip dysplasia | Excellent/Good |
| OFA Elbows | Elbow dysplasia | Normal |
| OFA Heart | Cardiac disease | Normal |
| CERF/OFA Eyes | Eye disease | Clear |
| DM | Degenerative Myelopathy | Clear/Carrier |

## Data Collected

### Breeders
- Name, kennel name
- Location (city, state, country)
- Contact (email, phone, website)
- Dogs bred, dogs owned
- Litter history

### Dogs
- Registered name, call name
- Registration numbers (AKC, DNA, microchip)
- Pedigree (sire, dam, grandparents)
- Health clearances (OFA, CERF)
- Titles and awards
- Birth/death dates (for longevity calculation)
- Photos

### Litters
- Whelp date
- Sire and dam
- Breeder
- Puppies list
- Country of birth

## Configuration

Edit `config.py`:
```python
REQUEST_DELAY_SECONDS = 2.0  # Don't reduce - be nice to server
DOWNLOAD_IMAGES = True       # Set False to skip images
```

## Notes

- Scraping takes several hours due to respectful rate limiting
- Run overnight or in background
- Data is saved incrementally - safe to restart
- Health data depends on what breeders have submitted to Bernergarde
