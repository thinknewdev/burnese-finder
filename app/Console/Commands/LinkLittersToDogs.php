<?php

namespace App\Console\Commands;

use App\Models\Dog;
use App\Models\Litter;
use Illuminate\Console\Command;

class LinkLittersToDogs extends Command
{
    protected $signature = 'litters:link {--fresh : Clear existing links first}';
    protected $description = 'Link litters to dogs by matching sire/dam names';

    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing sire_id and dam_id links...');
            Litter::query()->update(['sire_id' => null, 'dam_id' => null]);
        }

        $this->info('Building dog name lookup...');
        // Create a map of registered names to bg_dog_ids
        $dogMap = Dog::whereNotNull('registered_name')
            ->pluck('bg_dog_id', 'registered_name')
            ->toArray();

        $this->info('Found ' . count($dogMap) . ' dogs with registered names');

        $litters = Litter::whereNull('sire_id')
            ->orWhereNull('dam_id')
            ->get();

        $this->info('Processing ' . $litters->count() . ' litters...');

        $sireLinked = 0;
        $damLinked = 0;

        $bar = $this->output->createProgressBar($litters->count());
        $bar->start();

        foreach ($litters as $litter) {
            $updated = false;

            // Try to link sire by name
            if (empty($litter->sire_id) && !empty($litter->sire_name)) {
                $sireName = strtoupper(trim($litter->sire_name));
                if (isset($dogMap[$sireName])) {
                    $litter->sire_id = $dogMap[$sireName];
                    $sireLinked++;
                    $updated = true;
                }
            }

            // Try to link dam by name
            if (empty($litter->dam_id) && !empty($litter->dam_name)) {
                $damName = strtoupper(trim($litter->dam_name));
                if (isset($dogMap[$damName])) {
                    $litter->dam_id = $dogMap[$damName];
                    $damLinked++;
                    $updated = true;
                }
            }

            if ($updated) {
                $litter->save();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Linked {$sireLinked} sires");
        $this->info("Linked {$damLinked} dams");

        // Show summary
        $totalLinked = Litter::whereNotNull('sire_id')
            ->orWhereNotNull('dam_id')
            ->count();

        $this->info("Total litters with at least one parent linked: {$totalLinked}");

        return 0;
    }
}
