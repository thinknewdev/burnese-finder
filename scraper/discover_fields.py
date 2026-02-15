#!/usr/bin/env python3
"""
Field discovery script for Bernergarde website

Run this first to discover the actual form field names and page structure.
This will help calibrate the main scraper to work with the site.
"""

import json
from bs4 import BeautifulSoup
from session_manager import SessionManager
import config


def discover_form_fields(url: str, session: SessionManager) -> dict:
    """
    Discover all form fields on a page

    Returns dictionary with:
    - inputs: All input fields
    - selects: All select/dropdown fields
    - buttons: All buttons
    - hidden: Hidden ASP.NET fields
    """
    soup = session.get_page(url)

    fields = {
        'url': url,
        'inputs': [],
        'selects': [],
        'buttons': [],
        'hidden': [],
        'links': []
    }

    # Find all forms
    forms = soup.find_all('form')
    for form in forms:
        form_info = {
            'action': form.get('action'),
            'method': form.get('method'),
            'id': form.get('id')
        }
        print(f"\nForm found: {form_info}")

        # Input fields
        for inp in form.find_all('input'):
            field_info = {
                'name': inp.get('name'),
                'id': inp.get('id'),
                'type': inp.get('type', 'text'),
                'value': inp.get('value', '')[:50] if inp.get('value') else ''
            }
            if inp.get('type') == 'hidden':
                fields['hidden'].append(field_info)
            else:
                fields['inputs'].append(field_info)

        # Select/dropdowns
        for select in form.find_all('select'):
            options = []
            for opt in select.find_all('option')[:20]:  # First 20 options
                options.append({
                    'value': opt.get('value'),
                    'text': opt.get_text(strip=True)
                })
            fields['selects'].append({
                'name': select.get('name'),
                'id': select.get('id'),
                'options_sample': options,
                'total_options': len(select.find_all('option'))
            })

        # Buttons
        for btn in form.find_all(['button', 'input']):
            if btn.get('type') in ['submit', 'button']:
                fields['buttons'].append({
                    'name': btn.get('name'),
                    'id': btn.get('id'),
                    'value': btn.get('value'),
                    'text': btn.get_text(strip=True) if btn.name == 'button' else None
                })

    # Find any data tables
    tables = soup.find_all('table')
    for i, table in enumerate(tables):
        headers = [th.get_text(strip=True) for th in table.find_all('th')]
        if headers:
            print(f"\nTable {i} headers: {headers}")

    # Find relevant links
    for a in soup.find_all('a', href=True):
        href = a.get('href', '')
        if any(x in href.lower() for x in ['dog', 'person', 'litter', 'detail', 'search']):
            fields['links'].append({
                'text': a.get_text(strip=True),
                'href': href
            })

    return fields


def main():
    session = SessionManager()

    pages = {
        'Dog Search': config.DOG_SEARCH_URL,
        'People Search': config.PEOPLE_SEARCH_URL,
        'Litter Search': config.LITTER_SEARCH_URL,
    }

    all_fields = {}

    for name, url in pages.items():
        print(f"\n{'='*60}")
        print(f"Discovering fields for: {name}")
        print(f"URL: {url}")
        print('='*60)

        try:
            fields = discover_form_fields(url, session)
            all_fields[name] = fields

            print(f"\nInputs found: {len(fields['inputs'])}")
            for f in fields['inputs']:
                print(f"  - {f['name']} (id={f['id']}, type={f['type']})")

            print(f"\nSelects found: {len(fields['selects'])}")
            for f in fields['selects']:
                print(f"  - {f['name']} (id={f['id']}, {f['total_options']} options)")
                if f['options_sample']:
                    print(f"    Sample options: {[o['text'] for o in f['options_sample'][:5]]}")

            print(f"\nButtons found: {len(fields['buttons'])}")
            for f in fields['buttons']:
                print(f"  - {f['name']} (value={f['value']})")

            print(f"\nRelevant links found: {len(fields['links'])}")
            for link in fields['links'][:10]:
                print(f"  - {link['text']}: {link['href']}")

        except Exception as e:
            print(f"Error discovering fields: {e}")

    # Save to JSON for reference
    output_file = 'discovered_fields.json'
    with open(output_file, 'w') as f:
        json.dump(all_fields, f, indent=2)
    print(f"\n\nField discovery saved to {output_file}")


if __name__ == "__main__":
    main()
