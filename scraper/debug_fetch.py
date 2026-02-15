#!/usr/bin/env python3
"""Debug script to see raw HTML from Bernergarde"""

import requests
from bs4 import BeautifulSoup

urls = [
    "https://bernergarde.org/DB/Dog_Search",
    "https://bernergarde.org/DB/People_Search",
]

session = requests.Session()
session.headers.update({
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
})

for url in urls:
    print(f"\n{'='*60}")
    print(f"Fetching: {url}")
    print('='*60)

    try:
        response = session.get(url, timeout=30)
        print(f"Status: {response.status_code}")
        print(f"Content-Type: {response.headers.get('content-type')}")
        print(f"Content length: {len(response.text)}")

        # Save raw HTML
        filename = url.split('/')[-1] + '.html'
        with open(filename, 'w') as f:
            f.write(response.text)
        print(f"Saved to: {filename}")

        # Parse and show structure
        soup = BeautifulSoup(response.text, 'lxml')

        # Find all forms
        forms = soup.find_all('form')
        print(f"\nForms found: {len(forms)}")
        for i, form in enumerate(forms):
            print(f"\nForm {i}:")
            print(f"  ID: {form.get('id')}")
            print(f"  Action: {form.get('action')}")
            print(f"  Method: {form.get('method')}")

            inputs = form.find_all('input')
            print(f"  Inputs: {len(inputs)}")
            for inp in inputs[:10]:
                print(f"    - {inp.get('name')} (type={inp.get('type')})")

            selects = form.find_all('select')
            print(f"  Selects: {len(selects)}")
            for sel in selects:
                print(f"    - {sel.get('name')} ({len(sel.find_all('option'))} options)")

        # Look for any input/select outside forms
        all_inputs = soup.find_all('input')
        all_selects = soup.find_all('select')
        print(f"\nTotal inputs on page: {len(all_inputs)}")
        print(f"Total selects on page: {len(all_selects)}")

        # Check for JavaScript frameworks
        scripts = soup.find_all('script')
        print(f"\nScripts found: {len(scripts)}")
        for script in scripts:
            src = script.get('src', '')
            if src:
                print(f"  - {src}")

    except Exception as e:
        print(f"Error: {e}")
