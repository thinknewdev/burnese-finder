"""
Configuration settings for Bernergarde scraper
"""

# Base URLs
BASE_URL = "https://bernergarde.org"
DOG_SEARCH_URL = f"{BASE_URL}/DB/Dog_Search"
PEOPLE_SEARCH_URL = f"{BASE_URL}/DB/People_Search"
LITTER_SEARCH_URL = f"{BASE_URL}/DB/Litter_Search"

# Rate limiting - be respectful to the server
REQUESTS_PER_MINUTE = 30
REQUEST_DELAY_SECONDS = 2.0

# Retry settings
MAX_RETRIES = 3
RETRY_DELAY_SECONDS = 5

# Output directories
OUTPUT_DIR = "output"
IMAGES_DIR = f"{OUTPUT_DIR}/images"
DOGS_CSV = f"{OUTPUT_DIR}/dogs.csv"
PEOPLE_CSV = f"{OUTPUT_DIR}/people.csv"
LITTERS_CSV = f"{OUTPUT_DIR}/litters.csv"
BREEDERS_CSV = f"{OUTPUT_DIR}/breeders.csv"

# Image settings
DOWNLOAD_IMAGES = True
IMAGE_TIMEOUT_SECONDS = 30

# Search parameters - letters to iterate through for comprehensive scraping
ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"

# US States and Canadian Provinces for geographic iteration
US_STATES = [
    "AL", "AK", "AZ", "AR", "CA", "CO", "CT", "DE", "FL", "GA",
    "HI", "ID", "IL", "IN", "IA", "KS", "KY", "LA", "ME", "MD",
    "MA", "MI", "MN", "MS", "MO", "MT", "NE", "NV", "NH", "NJ",
    "NM", "NY", "NC", "ND", "OH", "OK", "OR", "PA", "RI", "SC",
    "SD", "TN", "TX", "UT", "VT", "VA", "WA", "WV", "WI", "WY", "DC"
]

CANADIAN_PROVINCES = [
    "AB", "BC", "MB", "NB", "NL", "NS", "NT", "NU", "ON", "PE", "QC", "SK", "YT"
]

# Countries commonly associated with Bernese Mountain Dog breeding
COUNTRIES = [
    "United States", "Canada", "Germany", "Switzerland", "Austria",
    "Netherlands", "Belgium", "France", "United Kingdom", "Australia",
    "New Zealand", "Sweden", "Norway", "Denmark", "Finland", "Italy",
    "Czech Republic", "Poland", "Hungary", "Spain"
]

# Session settings
SESSION_TIMEOUT_MINUTES = 15
