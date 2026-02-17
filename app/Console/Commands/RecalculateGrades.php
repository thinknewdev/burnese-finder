<?php

namespace App\Console\Commands;

use App\Models\Breeder;
use App\Models\Dog;
use Illuminate\Console\Command;

class RecalculateGrades extends Command
{
    protected $signature = 'dogs:recalculate-grades';
    protected $description = 'Recalculate health scores, longevity scores, pedigree longevity scores, and grades for all dogs and breeders';

    public function handle(): void
    {
        $total = Dog::count();
        $this->info("Recalculating grades for {$total} dogs...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $healthSum = $longevitySum = $pedigreeSum = $gradeSum = 0;
        $count = 0;

        Dog::chunk(500, function ($dogs) use ($bar, &$healthSum, &$longevitySum, &$pedigreeSum, &$gradeSum, &$count) {
            foreach ($dogs as $dog) {
                // Compute age_years from dates if not already set
                if (!$dog->age_years && $dog->birth_date && $dog->death_date) {
                    $dog->age_years = (int) round($dog->birth_date->diffInDays($dog->death_date) / 365.25, 0);
                    $dog->save();
                }

                $healthScore         = $dog->calculateHealthScore();
                $longevityScore      = $dog->calculateLongevityScore();
                $pedigreeLongevity   = $dog->calculatePedigreeLongevityScore();
                $grade               = $dog->calculateGrade();

                $dog->health_score              = $healthScore;
                $dog->longevity_score           = $longevityScore;
                $dog->pedigree_longevity_score  = $pedigreeLongevity;
                $dog->grade                     = $grade;
                $dog->save();

                $healthSum    += $healthScore;
                $longevitySum += $longevityScore;
                $pedigreeSum  += $pedigreeLongevity;
                $gradeSum     += $grade;
                $count++;

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        // Recalculate breeder grades (average of their dogs' grades)
        $this->info('Recalculating breeder grades...');
        $breeders = Breeder::all();
        foreach ($breeders as $breeder) {
            $avg = Dog::where('breeder_id', $breeder->id)->avg('grade');
            $breeder->grade = $avg ?? 50;
            $breeder->save();
        }

        if ($count > 0) {
            $this->table(
                ['Metric', 'Average Score'],
                [
                    ['Avg Health Score',           number_format($healthSum / $count, 1)],
                    ['Avg Own Longevity Score',     number_format($longevitySum / $count, 1)],
                    ['Avg Pedigree Longevity Score', number_format($pedigreeSum / $count, 1)],
                    ['Avg Overall Grade',           number_format($gradeSum / $count, 1)],
                ]
            );
        }

        $this->info("Done. {$count} dogs recalculated, {$breeders->count()} breeders updated.");
    }
}
