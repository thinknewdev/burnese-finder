<?php

namespace App\Console\Commands;

use App\Models\Breeder;
use App\Models\Dog;
use App\Models\Litter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportData extends Command
{
    protected $signature = 'import:data {--fresh : Truncate tables before import}';
    protected $description = 'Import scraped CSV data into database';

    public function handle()
    {
        $importPath = storage_path('app/import');

        if (!is_dir($importPath)) {
            $this->error("Import directory not found: {$importPath}");
            return 1;
        }

        if ($this->option('fresh')) {
            $this->info('Truncating tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Litter::truncate();
            Dog::truncate();
            Breeder::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->importBreeders($importPath);
        $this->importDogs($importPath);
        $this->linkDogsToBreedersFromBreederData($importPath);
        $this->importLitters($importPath);
        $this->calculateGrades();

        $this->info('Import complete!');
        $this->table(
            ['Model', 'Count'],
            [
                ['Breeders', Breeder::count()],
                ['Dogs', Dog::count()],
                ['Litters', Litter::count()],
            ]
        );

        return 0;
    }

    private function importBreeders(string $path): void
    {
        // Prefer merged file with details
        $file = "{$path}/ALL_BREEDERS_MERGED.csv";
        if (!file_exists($file)) {
            $file = "{$path}/ALL_BREEDERS.csv";
        }
        if (!file_exists($file)) {
            $this->warn("Breeders file not found: {$file}");
            return;
        }

        $this->info('Importing breeders...');
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $count = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $bgPersonId = $data['breeder_id'] ?? $data['bg_person_id'] ?? $data['person_id'] ?? null;
            // Clean up .0 suffix from numeric IDs
            if ($bgPersonId) {
                $bgPersonId = preg_replace('/\.0$/', '', $bgPersonId);
            }

            Breeder::updateOrCreate(
                ['bg_person_id' => $bgPersonId],
                [
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'kennel_name' => $data['kennel_name'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'dogs_bred_count' => (int)($data['dogs_bred_count'] ?? 0),
                    'litters_count' => (int)($data['litters_count'] ?? 0),
                ]
            );
            $count++;
            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine();
        $this->info("Imported {$count} breeders");
    }

    private function importDogs(string $path): void
    {
        // Prefer merged file with details
        $file = "{$path}/ALL_DOGS_MERGED.csv";
        if (!file_exists($file)) {
            $file = "{$path}/ALL_DOGS.csv";
        }
        if (!file_exists($file)) {
            $this->warn("Dogs file not found: {$file}");
            return;
        }

        $this->info('Importing dogs...');
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $count = 0;

        // Build breeder lookup
        $breederMap = Breeder::pluck('id', 'bg_person_id')->toArray();

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $breederId = null;
            if (!empty($data['breeder_id'])) {
                $breederId = $breederMap[$data['breeder_id']] ?? null;
            }

            $bgDogId = $data['bg_dog_id'] ?? $data['dog_id'] ?? null;
            // Clean up .0 suffix from numeric IDs
            if ($bgDogId) {
                $bgDogId = preg_replace('/\.0$/', '', $bgDogId);
            }

            Dog::updateOrCreate(
                ['bg_dog_id' => $bgDogId],
                [
                    'registered_name' => $data['registered_name'] ?? $data['name'] ?? null,
                    'call_name' => $data['call_name'] ?? null,
                    'sex' => $data['sex'] ?? null,
                    'birth_date' => $this->parseDate($data['birth_date'] ?? null),
                    'death_date' => $this->parseDate($data['death_date'] ?? null),
                    'age_years' => !empty($data['age_years']) ? (int)$data['age_years'] : null,
                    'color' => $data['color'] ?? null,
                    'breeder_id' => $breederId,
                    'breeder_name' => $data['breeder_name'] ?? null,
                    'hip_rating' => $data['hip_rating'] ?? null,
                    'elbow_rating' => $data['elbow_rating'] ?? null,
                    'heart_status' => $data['heart_status'] ?? null,
                    'eye_status' => $data['eye_status'] ?? null,
                    'dna_status' => $data['dna_status'] ?? null,
                    'ofa_certified' => !empty($data['ofa_certified']) && strtolower($data['ofa_certified']) !== 'false',
                    'sire_id' => $data['sire_id'] ?? null,
                    'dam_id' => $data['dam_id'] ?? null,
                    'sire_name' => $data['sire_name'] ?? null,
                    'dam_name' => $data['dam_name'] ?? null,
                    'primary_image' => $this->convertImagePath($data['primary_image'] ?? null),
                ]
            );
            $count++;
            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine();
        $this->info("Imported {$count} dogs");
    }

    private function importLitters(string $path): void
    {
        $file = "{$path}/litters.csv";
        if (!file_exists($file)) {
            $this->warn("Litters file not found: {$file}");
            return;
        }

        $this->info('Importing litters...');
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $count = 0;

        $breederMap = Breeder::pluck('id', 'bg_person_id')->toArray();

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $breederId = null;
            if (!empty($data['breeder_id'])) {
                $breederId = $breederMap[$data['breeder_id']] ?? null;
            }

            $birthDate = $this->parseDate($data['birth_date'] ?? null);

            Litter::updateOrCreate(
                ['bg_litter_id' => $data['bg_litter_id'] ?? $data['litter_id'] ?? null],
                [
                    'birth_date' => $birthDate,
                    'birth_year' => $birthDate?->year ?? (!empty($data['birth_year']) ? (int)$data['birth_year'] : null),
                    'sire_id' => $data['sire_id'] ?? null,
                    'dam_id' => $data['dam_id'] ?? null,
                    'sire_name' => $data['sire_name'] ?? null,
                    'dam_name' => $data['dam_name'] ?? null,
                    'breeder_id' => $breederId,
                    'breeder_name' => $data['breeder_name'] ?? null,
                    'puppies_count' => !empty($data['puppies_count']) ? (int)$data['puppies_count'] : null,
                    'males_count' => !empty($data['males_count']) ? (int)$data['males_count'] : null,
                    'females_count' => !empty($data['females_count']) ? (int)$data['females_count'] : null,
                ]
            );
            $count++;
            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine();
        $this->info("Imported {$count} litters");
    }

    private function linkDogsToBreedersFromBreederData(string $path): void
    {
        // Use breeders' dogs_bred_ids to link dogs to their breeders
        $file = "{$path}/breeders_details.csv";
        if (!file_exists($file)) {
            $file = "{$path}/ALL_BREEDERS_MERGED.csv";
        }
        if (!file_exists($file)) {
            $this->warn("No breeder details file for dog linking");
            return;
        }

        $this->info('Linking dogs to breeders...');

        // Build lookup maps
        $breederMap = Breeder::pluck('id', 'bg_person_id')->toArray();
        $dogMap = Dog::pluck('id', 'bg_dog_id')->toArray();

        $this->info("Breeder map size: " . count($breederMap));
        $this->info("Dog map size: " . count($dogMap));

        // Read CSV - use the same logic that works in test script
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $linked = 0;
        $foundInMap = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $dogsBredIds = $row[9] ?? '';  // Column 9 is dogs_bred_ids
            if (empty($dogsBredIds)) continue;

            $bgPersonId = $row[0] ?? null;  // Column 0 is bg_person_id
            $breederName = $row[1] ?? null; // Column 1 is name

            $breederId = $breederMap[$bgPersonId] ?? null;
            if (!$breederId) continue;

            foreach (explode('|', $dogsBredIds) as $bgDogId) {
                $bgDogId = trim(preg_replace('/\.0$/', '', $bgDogId));
                if (empty($bgDogId)) continue;

                if (isset($dogMap[$bgDogId])) {
                    $foundInMap++;
                    $dogId = $dogMap[$bgDogId];
                    $updated = Dog::where('id', $dogId)
                        ->whereNull('breeder_id')
                        ->update([
                            'breeder_id' => $breederId,
                            'breeder_name' => $breederName,
                        ]);
                    if ($updated) {
                        $linked++;
                    }
                }
            }
        }

        fclose($handle);
        $this->info("Linked {$linked} dogs to breeders ({$foundInMap} references found)");
    }

    private function calculateGrades(): void
    {
        $this->info('Calculating grades...');

        // Grade dogs
        $dogs = Dog::all();
        $bar = $this->output->createProgressBar($dogs->count());

        foreach ($dogs as $dog) {
            $dog->health_score = $dog->calculateHealthScore();
            $dog->longevity_score = $dog->calculateLongevityScore();
            $dog->grade = $dog->calculateGrade();
            $dog->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Grade breeders based on their dogs
        $breeders = Breeder::withCount('dogs')->get();
        foreach ($breeders as $breeder) {
            $avgDogGrade = Dog::where('breeder_id', $breeder->id)->avg('grade');
            $breeder->grade = $avgDogGrade ?? 50;
            $breeder->save();
        }

        $this->info('Grades calculated');
    }

    private function parseDate(?string $date): ?\Carbon\Carbon
    {
        if (empty($date) || $date === 'None' || $date === 'null') {
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
