# Bernese Mountain Dog Finder

## Project Overview

A Laravel 12 application for finding and evaluating Bernese Mountain Dogs based on health clearances, longevity, and breeder quality. The application imports data scraped from the Bernese Mountain Dog database and provides search and filtering capabilities to help users find healthy dogs from reputable breeders.

## Technology Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.0
- **Web Server**: Nginx (Alpine)
- **Runtime**: PHP 8.4-FPM
- **Containerization**: Docker & Docker Compose
- **Package Manager**: Composer

## Project Structure

```
burnese-finder/
├── app/
│   ├── Console/Commands/
│   │   └── ImportData.php           # Data import command from CSV files
│   ├── Http/Controllers/
│   │   ├── BreederController.php    # Breeder listing and details
│   │   ├── DogController.php        # Dog listing and details
│   │   └── SearchController.php     # Search and "find best dog" logic
│   └── Models/
│       ├── Breeder.php              # Breeder model with grading
│       ├── Dog.php                  # Dog model with health scoring
│       └── Litter.php               # Litter tracking
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_breeders_table.php
│   │   ├── 2024_01_01_000002_create_dogs_table.php
│   │   └── 2024_01_01_000003_create_litters_table.php
│   └── seeders/
├── docker/
│   └── nginx/conf.d/               # Nginx configuration
├── routes/
│   └── web.php                     # Application routes
├── docker-compose.yml              # Container orchestration
├── Dockerfile                      # PHP-FPM container build
└── README.md
```

## Core Features

### 1. Dog Management
- **Health Scoring**: Dogs are graded based on:
  - Hip ratings (OFA)
  - Elbow ratings
  - Heart status
  - Eye clearances
  - DNA status
  - OFA certification
- **Longevity Scoring**: Based on age (BMD average lifespan: 7-10 years)
- **Overall Grade**: 40% health + 40% longevity + 20% breeder quality

### 2. Breeder Management
- Track breeders with contact information
- Aggregate breeder grades based on their dogs' health scores
- Search by name, kennel name, or location

### 3. Search & Filter
- Search dogs by registered name or call name
- Filter by sex, health clearances, alive status
- Find "best dog" recommendations
- Top-rated dogs and breeders

## Database Schema

### Dogs Table
Key fields:
- `bg_dog_id`: Bernese database ID
- `registered_name`, `call_name`
- `sex`, `birth_date`, `death_date`, `age_years`
- Health: `hip_rating`, `elbow_rating`, `heart_status`, `eye_status`, `dna_status`, `ofa_certified`
- Parentage: `sire_id`, `dam_id`, `sire_name`, `dam_name`
- Scores: `health_score`, `longevity_score`, `grade`
- `breeder_id` (foreign key), `breeder_name`
- `primary_image`, `images` (JSON)

### Breeders Table
- `bg_person_id`: Bernese database person ID
- `first_name`, `last_name`, `kennel_name`
- `city`, `state`, `country`
- `email`, `phone`
- `dogs_bred_count`, `litters_count`
- `grade`: Average grade of their dogs

### Litters Table
- `bg_litter_id`: Bernese database litter ID
- `birth_date`, `birth_year`
- `sire_id`, `dam_id`, `sire_name`, `dam_name`
- `breeder_id`, `breeder_name`
- `puppies_count`, `males_count`, `females_count`

## Docker Setup

### Services
1. **app** (bernese_app)
   - PHP 8.4-FPM with Laravel dependencies
   - Mounts project directory to `/var/www`
   - Import data mounted from `../scraper/output`

2. **nginx** (bernese_nginx)
   - Port 8080:80
   - Serves static files and proxies to PHP-FPM
   - Dog images mounted from `../scraper/output/images` to `/var/www/public/dog-images`

3. **mysql** (bernese_mysql)
   - MySQL 8.0
   - Port 3306:3306
   - Database: `bernese`
   - User: `bernese` / Password: `secret`
   - Persistent volume: `mysql_data`

## Getting Started

### 1. Start Docker Containers
```bash
docker-compose up -d
```

### 2. Run Database Migrations
```bash
docker exec -it bernese_app php artisan migrate
```

### 3. Import Data
The import command reads CSV files from `storage/app/import` (mounted from `../scraper/output`):
```bash
docker exec -it bernese_app php artisan import:data --fresh
```

Expected CSV files:
- `ALL_BREEDERS_MERGED.csv` or `ALL_BREEDERS.csv`
- `ALL_DOGS_MERGED.csv` or `ALL_DOGS.csv`
- `litters.csv`
- `breeders_details.csv` (for linking dogs to breeders)

### 4. Access Application
Visit: http://localhost:8080

## Artisan Commands

### Import Data
```bash
# Import with existing data
php artisan import:data

# Fresh import (truncates tables first)
php artisan import:data --fresh
```

The import process:
1. Imports breeders from CSV
2. Imports dogs from CSV
3. Links dogs to breeders using breeder details
4. Imports litters
5. Calculates health scores, longevity scores, and grades for all dogs and breeders

### Import Parent Dogs
Import parent dogs from recent litters that were scraped:

```bash
# Import parent dogs from scraped details
php artisan import:parent-dogs
```

This command:
- Imports 961 parent dogs from recent litters (2020+)
- Includes detailed health clearances and pedigree information
- Automatically calculates grades for new dogs
- Dramatically improves active breeding search coverage

### Update Litter Dog IDs
Update litters with actual dog IDs from scraped data:

```bash
# Update litter sire_id and dam_id from scraped details
php artisan litters:update-dog-ids
```

This command:
- Updates litter records with actual sire_dog_id and dam_dog_id
- Replaces name-based matching with direct ID links
- Improves accuracy from ~7% to ~94% parent coverage
- Processes 604 recent litters (2020+)

### Link Litters to Dogs
After importing, link litters to dogs by matching sire/dam names:

```bash
# Link litters to dogs by name matching
php artisan litters:link

# Clear existing links first, then re-link
php artisan litters:link --fresh
```

This command:
- Matches litter sire_name and dam_name fields to dog registered_name
- Updates litter sire_id and dam_id with matching bg_dog_id
- Enables searching for dogs with recent litters
- Required for the "Active Breeding" search feature
- Note: Less accurate than using scraped dog IDs (use litters:update-dog-ids instead)

## Routes

### Public Routes
- `GET /` - Home/search page
- `GET /search` - Search results
- `GET /find-best` - Find best dog recommendations
- `GET /active-breeding` - Find alive dogs with recent litters
- `GET /dogs` - All dogs listing
- `GET /dogs/top` - Top-rated dogs
- `GET /dogs/{id}` - Dog details
- `GET /breeders` - All breeders listing
- `GET /breeders/top` - Top-rated breeders
- `GET /breeders/{id}` - Breeder details

### Active Breeding Search
Find dogs that are alive and have produced recent litters:

**Current Statistics:**
- 946 alive dogs with litters since 2020
- 505 alive dogs with litters since 2023
- 360 alive dogs with litters since 2024
- 645 litters linked to sire dogs
- 635 litters linked to dam dogs
- 569 litters with both parents identified

**Endpoint:** `/active-breeding`

**Query Parameters:**
- `since_year` - Filter litters from this year forward (default: 3 years ago)
- `sex` - Filter by dog sex (Male/Female)
- `state` - Filter by breeder's state
- `sort` - Sort by: `recent_litter` (default), `grade`, `health`

**Example:**
```
GET /active-breeding?since_year=2023&sex=Male&sort=grade
```

**JSON Response:**
```json
{
  "count": 20,
  "dogs": [...],
  "filters": {
    "since_year": 2023,
    "sex": "Male",
    "state": null,
    "sort_by": "grade"
  }
}
```

## Grading Algorithm

### Dog Health Score (0-100)
- Base: 50 points
- Hip ratings:
  - Excellent: +30
  - Good: +25
  - Fair: +15
  - Borderline: +5
  - Mild: -10
  - Moderate: -20
  - Severe: -30
- Elbow normal: +20, abnormal: -10
- Heart normal/clear: +10
- Eye normal/clear: +10

### Dog Longevity Score (0-100)
- 12+ years: 100
- 10+ years: 90
- 8+ years: 75
- 6+ years: 50
- 4+ years: 30
- <4 years: 20
- Unknown: 50

### Dog Overall Grade
- Health Score × 40%
- Longevity Score × 40%
- Breeder Grade × 20%

### Breeder Grade
- Average of all their dogs' grades

## Development Notes

### Environment Variables
- `DB_HOST=mysql` (Docker service name)
- `DB_DATABASE=bernese`
- `DB_USERNAME=bernese`
- `DB_PASSWORD=secret`

### Data Import Path
- Import data is mounted from `../scraper/output` to `/var/www/storage/app/import`
- Dog images are mounted to `/var/www/public/dog-images`

### Composer Scripts
```bash
composer setup    # Full setup: install, env, key, migrate, npm
composer dev      # Run dev server with queue, logs, and vite
composer test     # Run tests
```

## Troubleshooting

### Docker Container Issues
```bash
# Check container status
docker ps -a

# View logs
docker logs bernese_app
docker logs bernese_nginx
docker logs bernese_mysql

# Restart containers
docker-compose restart

# Rebuild app container
docker-compose up -d --build app
```

### Database Issues
```bash
# Connect to MySQL
docker exec -it bernese_mysql mysql -u bernese -psecret bernese

# Reset database
docker exec -it bernese_app php artisan migrate:fresh
docker exec -it bernese_app php artisan import:data --fresh
```

### Import Issues
- Ensure scraper output directory exists at `../scraper/output`
- Check CSV file formats match expected headers
- Verify file permissions

## Related Projects

This application depends on a scraper (located at `../scraper/`) that:
- Scrapes Bernese Mountain Dog data from the official database
- Generates CSV files in `output/` directory
- Downloads dog images to `output/images/`

## Future Enhancements

- Add user authentication and favorites
- Implement advanced filtering (location, breeding history)
- Add pedigree visualization
- Email notifications for new dogs/litters
- API endpoints for mobile app
- Export search results to PDF
