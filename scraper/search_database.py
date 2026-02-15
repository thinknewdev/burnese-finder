#!/usr/bin/env python3
"""
Search utilities for the Bernergarde database

Provides functions to query the scraped data.
Can be used as a module or run interactively.
"""

import sqlite3
import pandas as pd
from typing import List, Optional, Dict
import os

import config

DATABASE_FILE = os.path.join(config.OUTPUT_DIR, "bernergarde.db")


class BernergardeDB:
    """Query interface for the Bernergarde database"""

    def __init__(self, db_path: str = DATABASE_FILE):
        self.db_path = db_path

    def _query(self, sql: str, params: tuple = ()) -> pd.DataFrame:
        """Execute a query and return results as DataFrame"""
        conn = sqlite3.connect(self.db_path)
        df = pd.read_sql_query(sql, conn, params=params)
        conn.close()
        return df

    # ===================
    # PEOPLE/BREEDER QUERIES
    # ===================

    def search_breeders(
        self,
        name: str = None,
        kennel: str = None,
        state: str = None,
        country: str = None
    ) -> pd.DataFrame:
        """
        Search for breeders with flexible criteria

        Args:
            name: Partial match on first or last name
            kennel: Partial match on kennel name
            state: State/province code
            country: Country name

        Returns:
            DataFrame of matching breeders
        """
        conditions = []
        params = []

        if name:
            conditions.append("(first_name LIKE ? OR last_name LIKE ?)")
            params.extend([f"%{name}%", f"%{name}%"])

        if kennel:
            conditions.append("kennel_name LIKE ?")
            params.append(f"%{kennel}%")

        if state:
            conditions.append("state = ?")
            params.append(state.upper())

        if country:
            conditions.append("country LIKE ?")
            params.append(f"%{country}%")

        where_clause = " AND ".join(conditions) if conditions else "1=1"

        sql = f"""
            SELECT bg_person_id, first_name, last_name, kennel_name,
                   city, state, country, email, phone, website
            FROM people
            WHERE {where_clause}
            ORDER BY last_name, first_name
        """

        return self._query(sql, tuple(params))

    def get_breeder_by_id(self, person_id: str) -> pd.DataFrame:
        """Get full breeder details by ID"""
        sql = "SELECT * FROM people WHERE bg_person_id = ?"
        return self._query(sql, (person_id,))

    def get_breeders_by_state(self, state: str) -> pd.DataFrame:
        """Get all breeders in a state"""
        return self.search_breeders(state=state)

    def get_breeders_by_country(self, country: str) -> pd.DataFrame:
        """Get all breeders in a country"""
        return self.search_breeders(country=country)

    def list_states_with_breeders(self) -> pd.DataFrame:
        """List all states/provinces with breeder counts"""
        sql = """
            SELECT state, country, COUNT(*) as breeder_count
            FROM people
            WHERE state IS NOT NULL AND state != ''
            GROUP BY state, country
            ORDER BY breeder_count DESC
        """
        return self._query(sql)

    # ===================
    # DOG QUERIES
    # ===================

    def search_dogs(
        self,
        name: str = None,
        call_name: str = None,
        breeder: str = None,
        kennel: str = None
    ) -> pd.DataFrame:
        """
        Search for dogs with flexible criteria

        Args:
            name: Partial match on registered name
            call_name: Partial match on call name
            breeder: Partial match on breeder name
            kennel: Partial match on kennel name

        Returns:
            DataFrame of matching dogs
        """
        conditions = []
        params = []

        if name:
            conditions.append("registered_name LIKE ?")
            params.append(f"%{name}%")

        if call_name:
            conditions.append("call_name LIKE ?")
            params.append(f"%{call_name}%")

        if breeder:
            conditions.append("breeder_name LIKE ?")
            params.append(f"%{breeder}%")

        if kennel:
            conditions.append("kennel_name LIKE ?")
            params.append(f"%{kennel}%")

        where_clause = " AND ".join(conditions) if conditions else "1=1"

        sql = f"""
            SELECT bg_dog_id, registered_name, call_name, sex,
                   birth_date, breeder_name, kennel_name,
                   sire_name, dam_name
            FROM dogs
            WHERE {where_clause}
            ORDER BY registered_name
        """

        return self._query(sql, tuple(params))

    def get_dog_by_id(self, dog_id: str) -> pd.DataFrame:
        """Get full dog details by ID"""
        sql = "SELECT * FROM dogs WHERE bg_dog_id = ?"
        return self._query(sql, (dog_id,))

    def get_dogs_by_breeder(self, breeder_name: str) -> pd.DataFrame:
        """Get all dogs by a specific breeder"""
        return self.search_dogs(breeder=breeder_name)

    def get_dogs_by_kennel(self, kennel_name: str) -> pd.DataFrame:
        """Get all dogs from a specific kennel"""
        return self.search_dogs(kennel=kennel_name)

    # ===================
    # LITTER QUERIES
    # ===================

    def search_litters(
        self,
        breeder: str = None,
        kennel: str = None,
        year: int = None,
        sire: str = None,
        dam: str = None
    ) -> pd.DataFrame:
        """
        Search for litters with flexible criteria

        Args:
            breeder: Partial match on breeder name
            kennel: Partial match on kennel name
            year: Birth year
            sire: Partial match on sire name
            dam: Partial match on dam name

        Returns:
            DataFrame of matching litters
        """
        conditions = []
        params = []

        if breeder:
            conditions.append("breeder_name LIKE ?")
            params.append(f"%{breeder}%")

        if kennel:
            conditions.append("kennel_name LIKE ?")
            params.append(f"%{kennel}%")

        if year:
            conditions.append("birth_year = ?")
            params.append(year)

        if sire:
            conditions.append("sire_name LIKE ?")
            params.append(f"%{sire}%")

        if dam:
            conditions.append("dam_name LIKE ?")
            params.append(f"%{dam}%")

        where_clause = " AND ".join(conditions) if conditions else "1=1"

        sql = f"""
            SELECT bg_litter_id, whelp_date, birth_year,
                   breeder_name, kennel_name, sire_name, dam_name,
                   country, puppy_count
            FROM litters
            WHERE {where_clause}
            ORDER BY birth_year DESC, whelp_date DESC
        """

        return self._query(sql, tuple(params))

    def get_litters_by_year(self, year: int) -> pd.DataFrame:
        """Get all litters from a specific year"""
        return self.search_litters(year=year)

    def get_litter_statistics(self) -> pd.DataFrame:
        """Get litter counts by year"""
        sql = """
            SELECT birth_year, COUNT(*) as litter_count
            FROM litters
            WHERE birth_year IS NOT NULL
            GROUP BY birth_year
            ORDER BY birth_year DESC
        """
        return self._query(sql)

    # ===================
    # STATISTICS
    # ===================

    def get_database_stats(self) -> Dict:
        """Get overall database statistics"""
        stats = {}

        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()

        cursor.execute("SELECT COUNT(*) FROM people")
        stats['total_breeders'] = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(*) FROM dogs")
        stats['total_dogs'] = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(*) FROM litters")
        stats['total_litters'] = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(DISTINCT state) FROM people WHERE state IS NOT NULL")
        stats['states_represented'] = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(DISTINCT country) FROM people WHERE country IS NOT NULL")
        stats['countries_represented'] = cursor.fetchone()[0]

        cursor.execute("SELECT COUNT(DISTINCT kennel_name) FROM people WHERE kennel_name IS NOT NULL")
        stats['unique_kennels'] = cursor.fetchone()[0]

        conn.close()
        return stats

    def export_to_csv(self, table: str, output_path: str):
        """Export a table to CSV"""
        sql = f"SELECT * FROM {table}"
        df = self._query(sql)
        df.to_csv(output_path, index=False)
        print(f"Exported {len(df)} records to {output_path}")


def interactive_search():
    """Interactive command-line search interface"""
    db = BernergardeDB()

    print("\n" + "=" * 50)
    print("BERNERGARDE DATABASE SEARCH")
    print("=" * 50)

    # Show stats
    stats = db.get_database_stats()
    print(f"\nDatabase contains:")
    print(f"  - {stats['total_breeders']} breeders/people")
    print(f"  - {stats['total_dogs']} dogs")
    print(f"  - {stats['total_litters']} litters")
    print(f"  - {stats['unique_kennels']} unique kennels")
    print(f"  - {stats['states_represented']} states/provinces")
    print(f"  - {stats['countries_represented']} countries")

    while True:
        print("\n" + "-" * 40)
        print("Search options:")
        print("  1. Search breeders by name")
        print("  2. Search breeders by state")
        print("  3. Search breeders by kennel")
        print("  4. Search dogs by name")
        print("  5. Search dogs by breeder")
        print("  6. Search litters by year")
        print("  7. List states with breeders")
        print("  8. Export all data to CSV")
        print("  q. Quit")
        print("-" * 40)

        choice = input("\nEnter choice: ").strip().lower()

        if choice == 'q':
            break
        elif choice == '1':
            name = input("Enter name to search: ").strip()
            results = db.search_breeders(name=name)
            print(results.to_string() if not results.empty else "No results found")
        elif choice == '2':
            state = input("Enter state code (e.g., CO, CA): ").strip()
            results = db.get_breeders_by_state(state)
            print(results.to_string() if not results.empty else "No results found")
        elif choice == '3':
            kennel = input("Enter kennel name: ").strip()
            results = db.search_breeders(kennel=kennel)
            print(results.to_string() if not results.empty else "No results found")
        elif choice == '4':
            name = input("Enter dog name: ").strip()
            results = db.search_dogs(name=name)
            print(results.to_string() if not results.empty else "No results found")
        elif choice == '5':
            breeder = input("Enter breeder name: ").strip()
            results = db.get_dogs_by_breeder(breeder)
            print(results.to_string() if not results.empty else "No results found")
        elif choice == '6':
            year = int(input("Enter year: ").strip())
            results = db.get_litters_by_year(year)
            print(results.to_string() if not results.empty else "No results found")
        elif choice == '7':
            results = db.list_states_with_breeders()
            print(results.to_string())
        elif choice == '8':
            db.export_to_csv('people', 'export_breeders.csv')
            db.export_to_csv('dogs', 'export_dogs.csv')
            db.export_to_csv('litters', 'export_litters.csv')
            print("All data exported!")
        else:
            print("Invalid choice")


if __name__ == "__main__":
    interactive_search()
