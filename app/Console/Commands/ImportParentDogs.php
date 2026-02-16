<?php

namespace App\Console\Commands;

use App\Models\Breeder;
use App\Models\Dog;
use Illuminate\Console\Command;

class ImportParentDogs extends Command
{
    protected $signature = 'import:parent-dogs';
    protected $description = 'Import parent dogs from scraped details';

    public function handle()
    {
        $file = storage_path('app/import/parent_dogs_details.csv');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info('Importing parent dogs from scraped details...');

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        // Build breeder lookup
        $breederMap = Breeder::pluck('id', 'bg_person_id')->toArray();

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

            // Clean up .0 suffix from numeric IDs
            $bgDogId = preg_replace('/\.0$/', '', $bgDogId);

            // Check if dog already exists
            $dog = Dog::where('bg_dog_id', $bgDogId)->first();
            $isNew = !$dog;

            if (!$dog) {
                $dog = new Dog();
                $dog->bg_dog_id = $bgDogId;
            }

            // Map the fields
            $dog->registered_name = $data['registered_name'] ?? $data['dog name'] ?? null;
            $dog->call_name = $data['call name'] ?? null;
            $dog->sex = $data['sex'] ?? null;
            $dog->birth_date = $this->parseDate($data['whelp date'] ?? null);
            $dog->death_date = $this->parseDate($data['deceased'] ?? null);

            // Calculate age if we have birth/death dates
            if ($dog->birth_date) {
                $endDate = $dog->death_date ?? now();
                $dog->age_years = $dog->birth_date->diffInYears($endDate);
            }

            $dog->color = $data['coat_color'] ?? null;
            $dog->primary_image = $this->convertImagePath($data['primary_image'] ?? null);

            // New fields from scraped data
            $dog->registration_number = $data['registrations'] ?? null;
            $dog->dna_number = $data['dna reg '] ?? $data['dna reg'] ?? null;
            $dog->microchip = $data['microchip'] ?? null;
            $dog->weight = $data['weight'] ?? null;
            $dog->height = $data['height'] ?? null;
            $dog->bite = $data['bite'] ?? null;
            $dog->tail = $data['tail'] ?? null;
            $dog->eye_color = $data['eye_color'] ?? null;
            $dog->stud_book = $data['stud book'] ?? $data['stud_book'] ?? null;
            $dog->frozen_semen = !empty($data['frozen semen']) && strtolower($data['frozen semen']) !== 'no' && $data['frozen semen'] !== 'false';
            $dog->rescue_type = $data['rescue type'] ?? $data['rescue_type'] ?? null;
            $dog->litter_id = $data['litter id'] ?? $data['litter id_id'] ?? null;

            // Try to link to breeder if we have breeder info
            // Note: The scraped data doesn't include breeder_id, but we might have it from litters

            // Parent IDs
            if (!empty($data['sire_id'])) {
                $dog->sire_id = preg_replace('/\.0$/', '', $data['sire_id']);
            }
            if (!empty($data['dam_id'])) {
                $dog->dam_id = preg_replace('/\.0$/', '', $data['dam_id']);
            }
            if (!empty($data['sire'])) {
                $dog->sire_name = $data['sire'];
            }
            if (!empty($data['dam'])) {
                $dog->dam_name = $data['dam'];
            }

            $dog->save();

            if ($isNew) {
                $imported++;
            } else {
                $updated++;
            }

            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->info("Imported {$imported} new dogs");
        $this->info("Updated {$updated} existing dogs");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} records");
        }

        // Now calculate grades for the new dogs
        $this->info('Calculating grades for new dogs...');
        $newDogs = Dog::whereNull('grade')->get();

        $gradeBar = $this->output->createProgressBar($newDogs->count());
        $gradeBar->start();

        foreach ($newDogs as $dog) {
            $dog->health_score = $dog->calculateHealthScore();
            $dog->longevity_score = $dog->calculateLongevityScore();
            $dog->grade = $dog->calculateGrade();
            $dog->save();
            $gradeBar->advance();
        }

        $gradeBar->finish();
        $this->newLine(2);

        $this->info('Total dogs in database: ' . Dog::count());

        return 0;
    }

    private function parseDate(?string $date): ?\Carbon\Carbon
    {
        if (empty($date) || $date === 'None' || $date === 'null' || $date === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function convertImagePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Convert output/images/dog_X.jpg to /dog-images/dog_X.jpg
        if (str_contains($path, 'output/images/')) {
            return '/dog-images/' . basename($path);
        }

        return $path;
    }
}
