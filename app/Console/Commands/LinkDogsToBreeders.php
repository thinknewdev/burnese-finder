<?php

namespace App\Console\Commands;

use App\Models\Breeder;
use App\Models\Dog;
use Illuminate\Console\Command;

class LinkDogsToBreeders extends Command
{
    protected $signature = 'dogs:link-breeders';
    protected $description = 'Link dogs to breeder records using breeders_details.csv dogs_bred_ids';

    public function handle()
    {
        $file = storage_path('app/import/breeders_details.csv');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info('Building lookup maps...');

        // Build lookup: bg_person_id => internal breeder id
        $breederMap = Breeder::pluck('id', 'bg_person_id')->toArray();
        // Build lookup: bg_dog_id => internal dog id (all dogs, including parent dogs)
        $dogMap = Dog::pluck('id', 'bg_dog_id')->toArray();

        $this->info("Breeders in DB: " . count($breederMap));
        $this->info("Dogs in DB: " . count($dogMap));

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        // Find column indices by name (handles non-breaking spaces in headers)
        $personIdCol = null;
        $nameCol = null;
        $kennelCol = null;
        $dogsBredIdsCol = null;

        foreach ($headers as $i => $h) {
            $clean = trim(str_replace(["\xc2\xa0", "\xa0"], ' ', $h));
            if ($clean === 'bg_person_id') $personIdCol = $i;
            elseif ($clean === 'name') $nameCol = $i;
            elseif ($clean === 'kennel name') $kennelCol = $i;
            elseif ($clean === 'dogs_bred_ids') $dogsBredIdsCol = $i;
        }

        if ($dogsBredIdsCol === null) {
            $this->error("Could not find dogs_bred_ids column. Headers: " . implode(', ', $headers));
            return 1;
        }

        $this->info("Linking dogs to breeders...");

        $linked = 0;
        $alreadyLinked = 0;
        $notFound = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $dogsBredIds = $row[$dogsBredIdsCol] ?? '';
            if (empty($dogsBredIds)) continue;

            $bgPersonId = $row[$personIdCol] ?? null;
            $breederName = $row[$nameCol] ?? null;

            $breederId = $breederMap[$bgPersonId] ?? null;
            if (!$breederId) continue;

            foreach (explode('|', $dogsBredIds) as $bgDogId) {
                $bgDogId = trim(preg_replace('/\.0$/', '', $bgDogId));
                if (empty($bgDogId) || !is_numeric($bgDogId)) continue;

                if (!isset($dogMap[$bgDogId])) {
                    $notFound++;
                    continue;
                }

                $dogId = $dogMap[$bgDogId];
                $updated = Dog::where('id', $dogId)
                    ->whereNull('breeder_id')
                    ->update([
                        'breeder_id'   => $breederId,
                        'breeder_name' => $breederName,
                    ]);

                if ($updated) {
                    $linked++;
                } else {
                    $alreadyLinked++;
                }
            }
        }

        fclose($handle);

        $this->info("Newly linked: {$linked} dogs");
        $this->line("Already linked: {$alreadyLinked} dogs");
        $this->line("Dog IDs in CSV not found in DB: {$notFound}");

        // Recalculate breeder grades now that more dogs are linked
        if ($linked > 0) {
            $this->info('Recalculating breeder grades...');
            Breeder::with('dogs')->chunk(100, function ($breeders) {
                foreach ($breeders as $breeder) {
                    $avgGrade = $breeder->dogs()->whereNotNull('grade')->avg('grade');
                    if ($avgGrade) {
                        $breeder->grade = round($avgGrade, 2);
                        $breeder->save();
                    }
                }
            });
        }

        $this->info('Done.');
        return 0;
    }
}
