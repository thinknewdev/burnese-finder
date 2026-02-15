"""
HTML parsers for extracting data from Bernergarde pages
"""

from bs4 import BeautifulSoup, Tag
from typing import Dict, List, Optional
import re
import logging

logger = logging.getLogger(__name__)


def parse_search_results_table(soup: BeautifulSoup) -> List[Dict]:
    """
    Parse search results table and extract links/data

    Args:
        soup: BeautifulSoup object of search results page

    Returns:
        List of dictionaries containing result data and links
    """
    results = []

    # Look for results table - ASP.NET GridView uses class "mGrid"
    tables = soup.find_all('table', {'class': re.compile(r'mGrid|grid|results|data', re.I)})
    if not tables:
        # Try finding any table with th headers
        tables = [t for t in soup.find_all('table') if t.find('th')]

    for table in tables:
        rows = table.find_all('tr')
        if len(rows) < 2:
            continue

        # Get headers from first row (look for th elements)
        header_row = rows[0]
        headers = []
        for th in header_row.find_all('th'):
            header_text = th.get_text(strip=True).lower()
            # Clean up headers - remove &nbsp; and normalize
            header_text = header_text.replace('\xa0', ' ').replace('  ', ' ')
            header_text = header_text.replace(' ', '_').replace('/', '_')
            headers.append(header_text)

        # If no th headers found, try td in first row
        if not headers:
            for td in header_row.find_all('td'):
                header_text = td.get_text(strip=True).lower()
                header_text = header_text.replace('\xa0', ' ').replace('  ', ' ')
                header_text = header_text.replace(' ', '_').replace('/', '_')
                headers.append(header_text)

        if not headers:
            continue

        # Parse data rows (skip header row)
        for row in rows[1:]:
            cells = row.find_all('td')
            if not cells:
                continue

            # Handle rows with different number of cells
            record = {}
            for i, cell in enumerate(cells):
                if i >= len(headers):
                    break

                # Get text content, clean up &nbsp;
                text = cell.get_text(strip=True).replace('\xa0', ' ').strip()
                record[headers[i]] = text

                # Check for links
                link = cell.find('a')
                if link and link.get('href'):
                    record[f'{headers[i]}_link'] = link.get('href')

            if record and any(v for v in record.values() if v and not v.isspace()):
                results.append(record)

    return results


def parse_dog_detail(soup: BeautifulSoup) -> Dict:
    """
    Parse dog detail page for all available information

    Args:
        soup: BeautifulSoup object of dog detail page

    Returns:
        Dictionary containing dog data
    """
    data = {}

    # Look for labeled data fields (common patterns: label/value pairs)
    # Pattern 1: Definition lists
    for dl in soup.find_all('dl'):
        dts = dl.find_all('dt')
        dds = dl.find_all('dd')
        for dt, dd in zip(dts, dds):
            key = _clean_key(dt.get_text(strip=True))
            value = dd.get_text(strip=True)
            link = dd.find('a')
            if link and link.get('href'):
                data[f'{key}_link'] = link.get('href')
            data[key] = value

    # Pattern 2: Table with label/value columns
    for table in soup.find_all('table'):
        for row in table.find_all('tr'):
            cells = row.find_all(['td', 'th'])
            if len(cells) >= 2:
                key = _clean_key(cells[0].get_text(strip=True))
                value = cells[1].get_text(strip=True)
                if key and value:
                    link = cells[1].find('a')
                    if link and link.get('href'):
                        data[f'{key}_link'] = link.get('href')
                    data[key] = value

    # Pattern 3: Span/div with class patterns
    for elem in soup.find_all(['span', 'div'], {'class': re.compile(r'field|value|data', re.I)}):
        label_elem = elem.find_previous(['label', 'span', 'div'],
                                         {'class': re.compile(r'label|field-name', re.I)})
        if label_elem:
            key = _clean_key(label_elem.get_text(strip=True))
            value = elem.get_text(strip=True)
            if key:
                data[key] = value

    # Extract specific sections if identifiable
    _extract_health_data(soup, data)
    _extract_pedigree_data(soup, data)
    _extract_breeder_owner_data(soup, data)

    return data


def parse_person_detail(soup: BeautifulSoup) -> Dict:
    """
    Parse person/breeder detail page

    Args:
        soup: BeautifulSoup object of person detail page

    Returns:
        Dictionary containing person data
    """
    data = {}

    # Similar patterns as dog detail
    for table in soup.find_all('table'):
        for row in table.find_all('tr'):
            cells = row.find_all(['td', 'th'])
            if len(cells) >= 2:
                key = _clean_key(cells[0].get_text(strip=True))
                value = cells[1].get_text(strip=True)
                if key and value:
                    data[key] = value

    # Look for contact information patterns
    contact_patterns = {
        'email': re.compile(r'[\w\.-]+@[\w\.-]+\.\w+'),
        'phone': re.compile(r'[\d\-\.\(\)\s]{10,}'),
        'website': re.compile(r'https?://[\w\.-]+\.\w+'),
    }

    page_text = soup.get_text()
    for field, pattern in contact_patterns.items():
        if field not in data:
            match = pattern.search(page_text)
            if match:
                data[field] = match.group()

    # Look for kennel name
    kennel_elem = soup.find(string=re.compile(r'kennel', re.I))
    if kennel_elem:
        parent = kennel_elem.find_parent()
        if parent:
            sibling = parent.find_next_sibling()
            if sibling:
                data['kennel_name'] = sibling.get_text(strip=True)

    return data


def parse_litter_detail(soup: BeautifulSoup) -> Dict:
    """
    Parse litter detail page

    Args:
        soup: BeautifulSoup object of litter detail page

    Returns:
        Dictionary containing litter data
    """
    data = {}

    # Extract standard table data
    for table in soup.find_all('table'):
        for row in table.find_all('tr'):
            cells = row.find_all(['td', 'th'])
            if len(cells) >= 2:
                key = _clean_key(cells[0].get_text(strip=True))
                value = cells[1].get_text(strip=True)
                if key and value:
                    link = cells[1].find('a')
                    if link and link.get('href'):
                        data[f'{key}_link'] = link.get('href')
                    data[key] = value

    # Look for puppies list
    puppies = []
    puppies_section = soup.find(string=re.compile(r'puppies|offspring', re.I))
    if puppies_section:
        parent = puppies_section.find_parent()
        if parent:
            puppy_links = parent.find_all_next('a', href=re.compile(r'dog', re.I))
            for link in puppy_links[:20]:  # Limit to reasonable number
                puppies.append({
                    'name': link.get_text(strip=True),
                    'link': link.get('href')
                })
    if puppies:
        data['puppies'] = puppies

    return data


def extract_pagination_info(soup: BeautifulSoup) -> Dict:
    """
    Extract pagination information from search results

    Args:
        soup: BeautifulSoup object

    Returns:
        Dictionary with pagination details
    """
    info = {
        'current_page': 1,
        'total_pages': 1,
        'has_next': False,
        'next_page_link': None
    }

    # Look for common pagination patterns
    pager = soup.find(['div', 'span', 'nav'], {'class': re.compile(r'pag|page', re.I)})
    if pager:
        # Look for page numbers
        page_links = pager.find_all('a')
        current = pager.find(['span', 'strong'], {'class': re.compile(r'current|active', re.I)})
        if current:
            try:
                info['current_page'] = int(current.get_text(strip=True))
            except ValueError:
                pass

        # Find max page number
        for link in page_links:
            try:
                page_num = int(link.get_text(strip=True))
                if page_num > info['total_pages']:
                    info['total_pages'] = page_num
            except ValueError:
                continue

        # Find next link
        next_link = pager.find('a', string=re.compile(r'next|>', re.I))
        if next_link:
            info['has_next'] = True
            info['next_page_link'] = next_link.get('href')

    return info


def _clean_key(text: str) -> str:
    """Clean and normalize field names"""
    if not text:
        return ''
    # Remove colons, normalize whitespace, convert to lowercase
    text = re.sub(r'[:\s]+', '_', text.strip().lower())
    text = re.sub(r'_+', '_', text)
    text = text.strip('_')
    return text


def _extract_health_data(soup: BeautifulSoup, data: Dict):
    """Extract health-related data from dog page"""
    health_keywords = ['hip', 'elbow', 'eye', 'heart', 'thyroid', 'ofa', 'pennhip', 'cerf']

    for keyword in health_keywords:
        elem = soup.find(string=re.compile(keyword, re.I))
        if elem:
            parent = elem.find_parent()
            if parent:
                # Get the value from next sibling or same parent
                value_elem = parent.find_next_sibling()
                if value_elem:
                    data[f'health_{keyword}'] = value_elem.get_text(strip=True)


def _extract_pedigree_data(soup: BeautifulSoup, data: Dict):
    """Extract pedigree/lineage data"""
    pedigree_fields = ['sire', 'dam', 'grandsire', 'granddam']

    for field in pedigree_fields:
        elem = soup.find(string=re.compile(f'^{field}', re.I))
        if elem:
            parent = elem.find_parent()
            if parent:
                link = parent.find_next('a')
                if link:
                    data[field] = link.get_text(strip=True)
                    data[f'{field}_link'] = link.get('href')


def _extract_breeder_owner_data(soup: BeautifulSoup, data: Dict):
    """Extract breeder and owner information"""
    for role in ['breeder', 'owner', 'co-owner']:
        elem = soup.find(string=re.compile(f'^{role}', re.I))
        if elem:
            parent = elem.find_parent()
            if parent:
                # Look for link to person
                link = parent.find_next('a')
                if link:
                    data[role] = link.get_text(strip=True)
                    data[f'{role}_link'] = link.get('href')
                else:
                    # Just get text value
                    next_elem = parent.find_next_sibling()
                    if next_elem:
                        data[role] = next_elem.get_text(strip=True)


def extract_dog_images(soup: BeautifulSoup) -> List[Dict]:
    """
    Extract all dog image URLs from a page

    Args:
        soup: BeautifulSoup object

    Returns:
        List of dicts with image info: {'url': str, 'alt': str, 'type': str}
    """
    images = []

    # Common patterns for dog images
    img_patterns = [
        # Direct img tags with dog-related attributes
        {'tag': 'img', 'attrs': {'src': re.compile(r'(dog|photo|image|pic)', re.I)}},
        {'tag': 'img', 'attrs': {'alt': re.compile(r'(dog|photo|picture)', re.I)}},
        {'tag': 'img', 'attrs': {'class': re.compile(r'(dog|photo|main|profile)', re.I)}},
        {'tag': 'img', 'attrs': {'id': re.compile(r'(dog|photo|main|profile)', re.I)}},
    ]

    seen_urls = set()

    # Find images matching patterns
    for pattern in img_patterns:
        for img in soup.find_all(pattern['tag'], pattern['attrs']):
            src = img.get('src') or img.get('data-src')
            if src and src not in seen_urls:
                # Skip tiny images (likely icons)
                width = img.get('width', '100')
                height = img.get('height', '100')
                try:
                    if int(width) < 50 or int(height) < 50:
                        continue
                except (ValueError, TypeError):
                    pass

                seen_urls.add(src)
                images.append({
                    'url': src,
                    'alt': img.get('alt', ''),
                    'type': 'photo'
                })

    # Also look for any larger images in content area
    content_areas = soup.find_all(['div', 'section', 'article'],
                                   {'class': re.compile(r'(content|detail|main|body)', re.I)})
    if not content_areas:
        content_areas = [soup]

    for area in content_areas:
        for img in area.find_all('img'):
            src = img.get('src') or img.get('data-src')
            if not src or src in seen_urls:
                continue

            # Skip common non-dog images
            if any(x in src.lower() for x in ['logo', 'icon', 'button', 'banner', 'ad', 'sprite']):
                continue

            # Check for reasonable image extensions
            if any(src.lower().endswith(ext) for ext in ['.jpg', '.jpeg', '.png', '.gif', '.webp']):
                seen_urls.add(src)
                images.append({
                    'url': src,
                    'alt': img.get('alt', ''),
                    'type': 'photo'
                })

    # Look for linked images (thumbnails that link to larger versions)
    for a in soup.find_all('a', href=re.compile(r'\.(jpg|jpeg|png|gif|webp)$', re.I)):
        href = a.get('href')
        if href and href not in seen_urls:
            seen_urls.add(href)
            images.append({
                'url': href,
                'alt': a.get('title', ''),
                'type': 'photo_link'
            })

    return images


def extract_all_page_images(soup: BeautifulSoup, min_size: int = 100) -> List[Dict]:
    """
    Extract ALL images from a page above a minimum size

    Args:
        soup: BeautifulSoup object
        min_size: Minimum width/height to include

    Returns:
        List of image info dicts
    """
    images = []
    seen_urls = set()

    for img in soup.find_all('img'):
        src = img.get('src') or img.get('data-src')
        if not src or src in seen_urls:
            continue

        # Try to filter out small images
        width = img.get('width', '')
        height = img.get('height', '')

        try:
            if width and int(width) < min_size:
                continue
            if height and int(height) < min_size:
                continue
        except (ValueError, TypeError):
            pass

        # Skip obvious non-content images
        skip_patterns = ['logo', 'icon', 'button', 'sprite', 'spacer', 'pixel', 'tracking']
        if any(p in src.lower() for p in skip_patterns):
            continue

        seen_urls.add(src)
        images.append({
            'url': src,
            'alt': img.get('alt', ''),
            'width': width,
            'height': height
        })

    return images
