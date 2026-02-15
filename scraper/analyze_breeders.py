#!/usr/bin/env python3
"""
Breeder and Dog Analysis Tool

Grades breeders and dogs based on:
- Health testing compliance
- Longevity of offspring
- Genetic diversity (COI)
- Litter frequency
- Health outcomes

For finding the best Bernese Mountain Dog to get.
"""

import os
import pandas as pd
import numpy as np
from typing import Dict, List, Optional
import logging
from datetime import datetime
import re

import config

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class BreederAnalyzer:
    """Analyze breeders and dogs to find best options"""

    def __init__(self):
        self.people_df = None
        self.dogs_df = None
        self.litters_df = None
        self.health_df = None
        self._load_data()

    def _load_data(self):
        """Load all available data"""
        files = {
            'people': [
                os.path.join(config.OUTPUT_DIR, 'people_details.csv'),
                config.PEOPLE_CSV,
                config.BREEDERS_CSV,
            ],
            'dogs': [
                os.path.join(config.OUTPUT_DIR, 'dogs_details.csv'),
                os.path.join(config.OUTPUT_DIR, 'dogs_health.csv'),
                config.DOGS_CSV,
            ],
            'litters': [
                config.LITTERS_CSV,
            ],
            'health': [
                os.path.join(config.OUTPUT_DIR, 'dogs_health.csv'),
            ]
        }

        for key, file_list in files.items():
            for f in file_list:
                if os.path.exists(f):
                    try:
                        df = pd.read_csv(f)
                        setattr(self, f'{key}_df', df)
                        logger.info(f"Loaded {len(df)} records from {f}")
                        break
                    except Exception as e:
                        logger.warning(f"Error loading {f}: {e}")

    def grade_breeder(self, breeder_id: str = None, breeder_name: str = None) -> Dict:
        """
        Grade a breeder based on multiple criteria

        Returns a score breakdown:
        - health_testing_score: % of dogs with health clearances
        - longevity_score: Average age of offspring at death
        - litter_frequency_score: Litters per year (lower is better)
        - total_dogs: Number of dogs bred
        - overall_grade: A-F grade
        """
        score = {
            'breeder_id': breeder_id,
            'breeder_name': breeder_name,
            'health_testing_score': 0,
            'longevity_score': 0,
            'litter_frequency_score': 0,
            'total_dogs_bred': 0,
            'dogs_with_health_data': 0,
            'avg_longevity_years': None,
            'overall_score': 0,
            'grade': 'N/A'
        }

        if self.dogs_df is None and self.health_df is None:
            return score

        # Find dogs by this breeder
        dogs = pd.DataFrame()

        if self.dogs_df is not None:
            if breeder_name:
                mask = self.dogs_df.apply(
                    lambda row: breeder_name.lower() in str(row.get('breeder', '')).lower()
                    or breeder_name.lower() in str(row.get('breeder_name', '')).lower(),
                    axis=1
                )
                dogs = self.dogs_df[mask]
            elif breeder_id:
                mask = self.dogs_df.apply(
                    lambda row: str(breeder_id) in str(row.get('breeder_link', ''))
                    or str(breeder_id) in str(row.get('breeder_id', '')),
                    axis=1
                )
                dogs = self.dogs_df[mask]

        score['total_dogs_bred'] = len(dogs)

        if len(dogs) == 0:
            return score

        # Health testing score (% with clearances)
        health_cols = ['hip', 'elbow', 'eye', 'heart', 'health_hip', 'health_elbow',
                       'health_eye', 'health_heart', 'dm', 'health_dm']
        existing_health_cols = [c for c in health_cols if c in dogs.columns]

        if existing_health_cols:
            dogs_with_health = dogs[existing_health_cols].notna().any(axis=1).sum()
            score['dogs_with_health_data'] = dogs_with_health
            score['health_testing_score'] = round(dogs_with_health / len(dogs) * 100, 1)

        # Longevity score
        if 'age_at_death_years' in dogs.columns:
            ages = dogs['age_at_death_years'].dropna()
            if len(ages) > 0:
                avg_age = ages.mean()
                score['avg_longevity_years'] = round(avg_age, 2)
                # Bernese avg is ~7-8 years, so score based on that
                # 10+ years = 100, 8 years = 75, 6 years = 50
                score['longevity_score'] = min(100, max(0, (avg_age - 4) * 16.67))

        # Litter frequency (from litters data)
        if self.litters_df is not None and breeder_name:
            breeder_litters = self.litters_df[
                self.litters_df.apply(
                    lambda row: breeder_name.lower() in str(row).lower(),
                    axis=1
                )
            ]
            if len(breeder_litters) > 0 and 'birth_year' in breeder_litters.columns:
                years = breeder_litters['birth_year'].dropna()
                if len(years) > 0:
                    year_span = years.max() - years.min() + 1
                    litters_per_year = len(breeder_litters) / max(year_span, 1)
                    # Ideal is 1-2 litters per year, more is concerning
                    if litters_per_year <= 2:
                        score['litter_frequency_score'] = 100
                    elif litters_per_year <= 4:
                        score['litter_frequency_score'] = 75
                    else:
                        score['litter_frequency_score'] = max(0, 100 - (litters_per_year - 2) * 12.5)

        # Calculate overall score
        weights = {
            'health_testing_score': 0.4,
            'longevity_score': 0.4,
            'litter_frequency_score': 0.2
        }

        overall = 0
        total_weight = 0
        for metric, weight in weights.items():
            if score[metric] > 0:
                overall += score[metric] * weight
                total_weight += weight

        if total_weight > 0:
            score['overall_score'] = round(overall / total_weight, 1)

            # Assign letter grade
            if score['overall_score'] >= 90:
                score['grade'] = 'A'
            elif score['overall_score'] >= 80:
                score['grade'] = 'B'
            elif score['overall_score'] >= 70:
                score['grade'] = 'C'
            elif score['overall_score'] >= 60:
                score['grade'] = 'D'
            else:
                score['grade'] = 'F'

        return score

    def find_top_breeders(self, min_dogs: int = 5, limit: int = 50) -> pd.DataFrame:
        """
        Find and rank the best breeders

        Args:
            min_dogs: Minimum dogs bred to be considered
            limit: Number of top breeders to return
        """
        if self.people_df is None:
            logger.warning("No people data loaded")
            return pd.DataFrame()

        # Get unique breeder names
        breeder_names = set()

        if 'name' in self.people_df.columns:
            breeder_names.update(self.people_df['name'].dropna().unique())
        if 'kennel_name' in self.people_df.columns:
            breeder_names.update(self.people_df['kennel_name'].dropna().unique())

        results = []
        for name in breeder_names:
            if not name or len(str(name)) < 2:
                continue

            grade = self.grade_breeder(breeder_name=str(name))
            if grade['total_dogs_bred'] >= min_dogs:
                results.append(grade)

        df = pd.DataFrame(results)
        if len(df) > 0:
            df = df.sort_values('overall_score', ascending=False).head(limit)

        return df

    def grade_dog(self, dog_id: str) -> Dict:
        """
        Grade an individual dog based on health, lineage, breeder

        Returns:
        - health_clearances: List of available clearances
        - health_score: Overall health testing score
        - lineage_score: Based on parent/grandparent health
        - breeder_score: Breeder's overall grade
        - overall_grade: Combined grade
        """
        score = {
            'dog_id': dog_id,
            'health_clearances': [],
            'health_score': 0,
            'lineage_score': 0,
            'breeder_score': 0,
            'overall_score': 0,
            'grade': 'N/A',
            'concerns': []
        }

        # Find dog in data
        dog = None
        if self.dogs_df is not None:
            matches = self.dogs_df[
                self.dogs_df.apply(
                    lambda row: str(dog_id) == str(row.get('bg_dog_id', '')),
                    axis=1
                )
            ]
            if len(matches) > 0:
                dog = matches.iloc[0].to_dict()

        if self.health_df is not None and dog is None:
            matches = self.health_df[
                self.health_df.apply(
                    lambda row: str(dog_id) == str(row.get('bg_dog_id', '')),
                    axis=1
                )
            ]
            if len(matches) > 0:
                dog = matches.iloc[0].to_dict()

        if dog is None:
            return score

        # Health clearances
        clearances = []
        health_fields = {
            'hip': ['hip', 'health_hip', 'ofa_hip'],
            'elbow': ['elbow', 'health_elbow', 'ofa_elbow'],
            'eye': ['eye', 'health_eye', 'cerf'],
            'heart': ['heart', 'health_heart', 'cardiac'],
            'dm': ['dm', 'health_dm', 'degenerative_myelopathy'],
        }

        for clearance_name, field_variants in health_fields.items():
            for field in field_variants:
                if field in dog and pd.notna(dog[field]):
                    clearances.append(f"{clearance_name}: {dog[field]}")
                    break

        score['health_clearances'] = clearances
        # Score based on number of clearances (5 main ones for BMD)
        score['health_score'] = min(100, len(clearances) * 20)

        # Check for concerning results
        for c in clearances:
            c_lower = c.lower()
            if any(bad in c_lower for bad in ['dysplasia', 'abnormal', 'fail', 'affected']):
                score['concerns'].append(c)

        # Lineage score - check if parents have clearances
        parent_health = 0
        parent_count = 0
        for parent in ['sire', 'dam']:
            parent_id = dog.get(f'{parent}_dog_id') or dog.get(f'{parent}_link', '')
            if parent_id:
                # Extract ID if it's a link
                match = re.search(r'DID=(\d+)', str(parent_id))
                if match:
                    parent_id = match.group(1)

                if parent_id and self.health_df is not None:
                    parent_matches = self.health_df[
                        self.health_df.apply(
                            lambda row: str(parent_id) == str(row.get('bg_dog_id', '')),
                            axis=1
                        )
                    ]
                    if len(parent_matches) > 0:
                        parent_count += 1
                        parent_data = parent_matches.iloc[0]
                        parent_clearances = sum(
                            1 for f in health_fields.values()
                            for field in f
                            if field in parent_data and pd.notna(parent_data[field])
                        )
                        parent_health += min(100, parent_clearances * 20)

        if parent_count > 0:
            score['lineage_score'] = parent_health / parent_count

        # Breeder score
        breeder = dog.get('breeder') or dog.get('breeder_name')
        if breeder:
            breeder_grade = self.grade_breeder(breeder_name=str(breeder))
            score['breeder_score'] = breeder_grade.get('overall_score', 0)

        # Overall score
        weights = {'health_score': 0.5, 'lineage_score': 0.3, 'breeder_score': 0.2}
        overall = sum(score[k] * v for k, v in weights.items() if score[k] > 0)
        total_weight = sum(v for k, v in weights.items() if score[k] > 0)

        if total_weight > 0:
            score['overall_score'] = round(overall / total_weight, 1)

            if score['overall_score'] >= 90:
                score['grade'] = 'A'
            elif score['overall_score'] >= 80:
                score['grade'] = 'B'
            elif score['overall_score'] >= 70:
                score['grade'] = 'C'
            elif score['overall_score'] >= 60:
                score['grade'] = 'D'
            else:
                score['grade'] = 'F'

        return score

    def find_recommended_dogs(self, min_health_score: int = 60, limit: int = 50) -> pd.DataFrame:
        """
        Find dogs that would be good choices based on health and lineage

        Returns dogs sorted by overall score
        """
        if self.dogs_df is None and self.health_df is None:
            logger.warning("No dog data loaded")
            return pd.DataFrame()

        source_df = self.health_df if self.health_df is not None else self.dogs_df

        results = []
        dog_ids = source_df['bg_dog_id'].dropna().unique() if 'bg_dog_id' in source_df.columns else []

        for dog_id in dog_ids:
            grade = self.grade_dog(str(dog_id))
            if grade['health_score'] >= min_health_score:
                # Add dog info
                dog_info = source_df[source_df['bg_dog_id'] == dog_id]
                if len(dog_info) > 0:
                    info = dog_info.iloc[0].to_dict()
                    grade['name'] = info.get('registered_name', info.get('name', ''))
                    grade['birth_date'] = info.get('birth_date', '')
                    grade['breeder'] = info.get('breeder', info.get('breeder_name', ''))

                results.append(grade)

        df = pd.DataFrame(results)
        if len(df) > 0:
            df = df.sort_values('overall_score', ascending=False).head(limit)

        return df

    def generate_breeder_report(self, breeder_name: str) -> str:
        """Generate a detailed report for a specific breeder"""
        grade = self.grade_breeder(breeder_name=breeder_name)

        report = f"""
================================================================================
BREEDER REPORT: {breeder_name}
================================================================================

OVERALL GRADE: {grade['grade']} ({grade['overall_score']}/100)

STATISTICS:
- Total Dogs Bred: {grade['total_dogs_bred']}
- Dogs with Health Testing: {grade['dogs_with_health_data']}
- Health Testing Rate: {grade['health_testing_score']}%
- Average Longevity: {grade['avg_longevity_years'] or 'Unknown'} years

SCORES:
- Health Testing Score: {grade['health_testing_score']}/100
- Longevity Score: {grade['longevity_score']}/100
- Litter Frequency Score: {grade['litter_frequency_score']}/100

RECOMMENDATION:
"""
        if grade['grade'] in ['A', 'B']:
            report += "This breeder appears to prioritize health testing and produces dogs with good longevity.\n"
        elif grade['grade'] == 'C':
            report += "This breeder shows some health testing commitment but could improve.\n"
        else:
            report += "Limited data or concerning patterns. Research further before considering.\n"

        return report


def main():
    import argparse

    parser = argparse.ArgumentParser(description="Analyze breeders and dogs")
    parser.add_argument('--top-breeders', action='store_true', help='Find top breeders')
    parser.add_argument('--recommended-dogs', action='store_true', help='Find recommended dogs')
    parser.add_argument('--breeder-report', type=str, help='Generate report for breeder')
    parser.add_argument('--grade-dog', type=str, help='Grade a specific dog by ID')
    parser.add_argument('--limit', type=int, default=20, help='Number of results')

    args = parser.parse_args()

    analyzer = BreederAnalyzer()

    if args.top_breeders:
        print("\n=== TOP BREEDERS ===\n")
        df = analyzer.find_top_breeders(limit=args.limit)
        if len(df) > 0:
            print(df.to_string())
            df.to_csv(os.path.join(config.OUTPUT_DIR, 'top_breeders.csv'), index=False)
        else:
            print("No breeder data available yet. Run the scraper first.")

    if args.recommended_dogs:
        print("\n=== RECOMMENDED DOGS ===\n")
        df = analyzer.find_recommended_dogs(limit=args.limit)
        if len(df) > 0:
            print(df.to_string())
            df.to_csv(os.path.join(config.OUTPUT_DIR, 'recommended_dogs.csv'), index=False)
        else:
            print("No dog data available yet. Run the health scraper first.")

    if args.breeder_report:
        report = analyzer.generate_breeder_report(args.breeder_report)
        print(report)

    if args.grade_dog:
        grade = analyzer.grade_dog(args.grade_dog)
        print(f"\n=== DOG GRADE: {args.grade_dog} ===")
        for k, v in grade.items():
            print(f"  {k}: {v}")


if __name__ == "__main__":
    main()
