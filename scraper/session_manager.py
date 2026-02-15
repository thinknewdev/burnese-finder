"""
Session manager for handling ASP.NET sessions and form state
"""

import requests
from bs4 import BeautifulSoup
import time
import logging
from fake_useragent import UserAgent

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class SessionManager:
    """Manages HTTP session with ASP.NET viewstate handling"""

    def __init__(self):
        self.session = requests.Session()
        self.ua = UserAgent()
        self._setup_session()
        self.viewstate = None
        self.viewstate_generator = None
        self.event_validation = None

    def _setup_session(self):
        """Configure session with appropriate headers"""
        self.session.headers.update({
            'User-Agent': self.ua.random,
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',  # Removed 'br' - brotli not auto-decoded by requests
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
        })

    def get_page(self, url: str, params: dict = None) -> BeautifulSoup:
        """
        Fetch a page and return parsed BeautifulSoup object

        Args:
            url: URL to fetch
            params: Optional query parameters

        Returns:
            BeautifulSoup object of the page
        """
        try:
            response = self.session.get(url, params=params, timeout=30)
            response.raise_for_status()
            soup = BeautifulSoup(response.text, 'lxml')
            self._extract_aspnet_fields(soup)
            return soup
        except requests.RequestException as e:
            logger.error(f"Error fetching {url}: {e}")
            raise

    def post_form(self, url: str, data: dict, extra_headers: dict = None) -> BeautifulSoup:
        """
        Submit a form via POST with ASP.NET viewstate

        Args:
            url: URL to post to
            data: Form data dictionary
            extra_headers: Optional additional headers

        Returns:
            BeautifulSoup object of the response
        """
        # Add ASP.NET hidden fields
        if self.viewstate:
            data['__VIEWSTATE'] = self.viewstate
        if self.viewstate_generator:
            data['__VIEWSTATEGENERATOR'] = self.viewstate_generator
        if self.event_validation:
            data['__EVENTVALIDATION'] = self.event_validation

        headers = {'Content-Type': 'application/x-www-form-urlencoded'}
        if extra_headers:
            headers.update(extra_headers)

        try:
            response = self.session.post(url, data=data, headers=headers, timeout=30)
            response.raise_for_status()
            soup = BeautifulSoup(response.text, 'lxml')
            self._extract_aspnet_fields(soup)
            return soup
        except requests.RequestException as e:
            logger.error(f"Error posting to {url}: {e}")
            raise

    def _extract_aspnet_fields(self, soup: BeautifulSoup):
        """Extract ASP.NET hidden form fields for subsequent requests"""
        viewstate = soup.find('input', {'name': '__VIEWSTATE'})
        if viewstate:
            self.viewstate = viewstate.get('value', '')

        viewstate_gen = soup.find('input', {'name': '__VIEWSTATEGENERATOR'})
        if viewstate_gen:
            self.viewstate_generator = viewstate_gen.get('value', '')

        event_val = soup.find('input', {'name': '__EVENTVALIDATION'})
        if event_val:
            self.event_validation = event_val.get('value', '')

    def reset_session(self):
        """Reset the session (useful if session times out)"""
        self.session = requests.Session()
        self._setup_session()
        self.viewstate = None
        self.viewstate_generator = None
        self.event_validation = None
        logger.info("Session reset")
