<?php

namespace App\Console\Commands;

use App\Models\Dog;
use Illuminate\Console\Command;

class ImportHealthCertifications extends Command
{
    protected $signature = 'import:health-certifications';
    protected $description = 'Import health certification data scraped from BernerGarde';

    public function handle()
    {
        $file = storage_path('app/import/health_certifications.csv');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            $this->line("Run the scraper first: cd scraper && python3 scrape_certifications.py --active-only");
            return 1;
        }

        $this->info('Importing health certifications...');

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $updated = 0;
        $skipped = 0;
        $noData = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $bgDogId = $data['bg_dog_id'] ?? null;
            if (empty($bgDogId)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $bgDogId = preg_replace('/\.0$/', '', trim($bgDogId));

            // Only update if there's at least one health field
            $hasCerts = filter_var($data['has_certifications'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$hasCerts) {
                $noData++;
                $bar->advance();
                continue;
            }

            $dog = Dog::where('bg_dog_id', $bgDogId)->first();
            if (!$dog) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Map certification fields to dog columns
            $fields = ['hip_rating', 'elbow_rating', 'heart_status', 'eye_status', 'dm_status', 'dna_status'];
            $changed = false;

            foreach ($fields as $field) {
                if (!empty($data[$field])) {
                    $dog->$field = $data[$field];
                    $changed = true;
                }
            }

            if ($changed) {
                // Recalculate health score and grade
                $dog->health_score = $dog->calculateHealthScore();
                $dog->pedigree_longevity_score = $dog->calculatePedigreeLongevityScore();
                $dog->grade = $dog->calculateGrade();
                $dog->save();
                $updated++;
            }

            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine();

        $this->info("Updated: {$updated} dogs with health certifications");
        $this->line("No certifications found: {$noData}");
        $this->line("Skipped (not in DB): {$skipped}");

        // Recalculate breeder grades based on updated dog grades
        $this->info('Recalculating breeder grades...');
        \App\Models\Breeder::with('dogs')->chunk(100, function ($breeders) {
            foreach ($breeders as $breeder) {
                $avgGrade = $breeder->dogs()->whereNotNull('grade')->avg('grade');
                if ($avgGrade) {
                    $breeder->grade = round($avgGrade, 2);
                    $breeder->save();
                }
            }
        });

        $this->info('Done.');
        return 0;
    }
}
