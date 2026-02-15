<?php

namespace App\Console\Commands;

use App\Models\Litter;
use Illuminate\Console\Command;

class UpdateLitterDogIds extends Command
{
    protected $signature = 'litters:update-dog-ids';
    protected $description = 'Update litter sire_id and dam_id from scraped details';

    public function handle()
    {
        $file = storage_path('app/import/recent_litters_details.csv');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info('Updating litter dog IDs from scraped details...');

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $updated = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $bgLitterId = $data['bg_litter_id'] ?? null;
            if (empty($bgLitterId)) {
                $bar->advance();
                continue;
            }

            $litter = Litter::where('bg_litter_id', $bgLitterId)->first();

            if (!$litter) {
                $notFound++;
                $bar->advance();
                continue;
            }

            // Update sire_id if we have it
            if (!empty($data['sire_dog_id']) && $data['sire_dog_id'] !== 'None') {
                $sireId = preg_replace('/\.0$/', '', $data['sire_dog_id']);
                if (!empty($sireId)) {
                    $litter->sire_id = $sireId;
                }
            }

            // Update dam_id if we have it
            if (!empty($data['dam_dog_id']) && $data['dam_dog_id'] !== 'None') {
                $damId = preg_replace('/\.0$/', '', $data['dam_dog_id']);
                if (!empty($damId)) {
                    $litter->dam_id = $damId;
                }
            }

            if ($litter->isDirty()) {
                $litter->save();
                $updated++;
            }

            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->info("Updated {$updated} litters with dog IDs");
        if ($notFound > 0) {
            $this->warn("Could not find {$notFound} litters in database");
        }

        // Show summary
        $withSire = Litter::whereNotNull('sire_id')->count();
        $withDam = Litter::whereNotNull('dam_id')->count();
        $withBoth = Litter::whereNotNull('sire_id')->whereNotNull('dam_id')->count();

        $this->info("Total litters with sire: {$withSire}");
        $this->info("Total litters with dam: {$withDam}");
        $this->info("Total litters with both parents: {$withBoth}");

        return 0;
    }
}
