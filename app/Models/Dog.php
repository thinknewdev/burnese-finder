<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bg_dog_id',
        'registered_name',
        'call_name',
        'sex',
        'birth_date',
        'death_date',
        'age_years',
        'color',
        'breeder_id',
        'breeder_name',
        'hip_rating',
        'elbow_rating',
        'heart_status',
        'eye_status',
        'dna_status',
        'dm_status',
        'ofa_certified',
        'health_data',
        'sire_id',
        'dam_id',
        'sire_name',
        'dam_name',
        'primary_image',
        'images',
        'grade',
        'health_score',
        'longevity_score',
        'pedigree_longevity_score',
        'breeder_score',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'ofa_certified' => 'boolean',
        'health_data' => 'array',
        'images' => 'array',
        'grade' => 'decimal:2',
        'health_score' => 'decimal:2',
        'longevity_score' => 'decimal:2',
        'pedigree_longevity_score' => 'decimal:2',
        'breeder_score' => 'decimal:2',
    ];

    public function breeder()
    {
        return $this->belongsTo(Breeder::class);
    }

    public function sire()
    {
        return Dog::where('bg_dog_id', $this->sire_id)->first();
    }

    public function dam()
    {
        return Dog::where('bg_dog_id', $this->dam_id)->first();
    }

    public function littersAsSire()
    {
        return Litter::where('sire_id', $this->bg_dog_id)
            ->orderByDesc('birth_year')
            ->get();
    }

    public function littersAsDam()
    {
        return Litter::where('dam_id', $this->bg_dog_id)
            ->orderByDesc('birth_year')
            ->get();
    }

    public function allLitters()
    {
        return Litter::where('sire_id', $this->bg_dog_id)
            ->orWhere('dam_id', $this->bg_dog_id)
            ->orderByDesc('birth_year')
            ->get();
    }

    public function getMostRecentLitterYearAttribute()
    {
        $litter = Litter::where('sire_id', $this->bg_dog_id)
            ->orWhere('dam_id', $this->bg_dog_id)
            ->orderByDesc('birth_year')
            ->first();

        return $litter?->birth_year;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw(
            "MATCH(registered_name, call_name) AGAINST(? IN BOOLEAN MODE)",
            ["*{$term}*"]
        );
    }

    public function scopeWithHealthClearances($query)
    {
        return $query->whereNotNull('hip_rating')
            ->orWhereNotNull('elbow_rating')
            ->orWhereNotNull('heart_status');
    }

    public function scopeTopRated($query, int $limit = 10)
    {
        return $query->whereNotNull('grade')
            ->orderByDesc('grade')
            ->limit($limit);
    }

    public function scopeBySex($query, string $sex)
    {
        return $query->where('sex', $sex);
    }

    public function scopeAlive($query)
    {
        return $query->whereNull('death_date');
    }

    public function scopeWithCompleteData($query)
    {
        // Dogs with complete data have at least some health clearance info
        // from the detail scraper
        return $query->where(function ($q) {
            $q->whereNotNull('hip_rating')
              ->orWhereNotNull('elbow_rating')
              ->orWhereNotNull('heart_status')
              ->orWhereNotNull('eye_status');
        });
    }

    public function scopeWithRecentLitters($query, ?int $sinceYear = null)
    {
        $year = $sinceYear ?? now()->subYears(3)->year;

        return $query->where(function ($q) use ($year) {
            // Find dogs that are sires or dams in recent litters
            $q->whereIn('bg_dog_id', function ($subQuery) use ($year) {
                $subQuery->select('sire_id')
                    ->from('litters')
                    ->whereNotNull('sire_id')
                    ->where('birth_year', '>=', $year);
            })->orWhereIn('bg_dog_id', function ($subQuery) use ($year) {
                $subQuery->select('dam_id')
                    ->from('litters')
                    ->whereNotNull('dam_id')
                    ->where('birth_year', '>=', $year);
            });
        });
    }

    public function scopeAliveWithRecentLitters($query, ?int $sinceYear = null)
    {
        return $query->alive()->withRecentLitters($sinceYear);
    }

    public function hasCompleteData(): bool
    {
        return $this->hip_rating || $this->elbow_rating ||
               $this->heart_status || $this->eye_status;
    }

    public function calculateGrade(): float
    {
        $healthScore = $this->calculateHealthScore();
        $longevityScore = $this->calculateLongevityScore();
        $pedigreeLongevityScore = $this->calculatePedigreeLongevityScore();

        // Health: 50%, Own Longevity: 30%, Pedigree Longevity: 20%
        return ($healthScore * 0.5) + ($longevityScore * 0.3) + ($pedigreeLongevityScore * 0.2);
    }

    public function calculateHealthScore(): float
    {
        $score = 50; // Base score

        // --- Hips (critical: -10 penalty if not tested) ---
        $hip = $this->hip_rating ?? '';
        if ($hip) {
            $hipScores = [
                'Excellent'  => 30,
                'Good'       => 25,
                'Fair'       => 15,
                'Borderline' => 5,
                'Mild'       => -5,
                'Moderate'   => -15,
                'Severe'     => -25,
            ];
            $matched = false;
            foreach ($hipScores as $rating => $points) {
                if (str_contains($hip, $rating)) {
                    $score += $points;
                    $matched = true;
                    break;
                }
            }
            // Tested but unrecognised result — no adjustment
        } else {
            $score -= 10; // Not tested: penalty
        }

        // --- Elbows (critical: -10 penalty if not tested) ---
        $elbow = $this->elbow_rating ?? '';
        if ($elbow) {
            $elbowLower = strtolower($elbow);
            if (str_contains($elbowLower, 'normal')) {
                $score += 20;
            } elseif (str_contains($elbowLower, 'grade iii') || str_contains($elbowLower, 'grade3') || str_contains($elbowLower, 'iii')) {
                $score -= 15;
            } elseif (str_contains($elbowLower, 'grade ii') || str_contains($elbowLower, 'grade2') || str_contains($elbowLower, 'ii')) {
                $score -= 5;
            } elseif (str_contains($elbowLower, 'grade i') || str_contains($elbowLower, 'grade1')) {
                $score += 5;
            }
            // Any other tested result: no adjustment
        } else {
            $score -= 10; // Not tested: penalty
        }

        // --- Heart (-5 if not tested, +3 if tested, +10 if normal/clear) ---
        $heart = strtolower($this->heart_status ?? '');
        if ($heart) {
            if (str_contains($heart, 'normal') || str_contains($heart, 'clear')) {
                $score += 10;
            } else {
                $score += 3; // Tested but not ideal
            }
        } else {
            $score -= 5;
        }

        // --- Eyes (-5 if not tested, +3 if tested, +10 if normal/clear) ---
        $eye = strtolower($this->eye_status ?? '');
        if ($eye) {
            if (str_contains($eye, 'normal') || str_contains($eye, 'clear')) {
                $score += 10;
            } else {
                $score += 3;
            }
        } else {
            $score -= 5;
        }

        // --- DM: Clear +10, Carrier 0, Affected -10, not tested -5 ---
        $dm = strtolower($this->dm_status ?? '');
        if ($dm) {
            if (str_contains($dm, 'clear')) {
                $score += 10;
            } elseif (str_contains($dm, 'affected')) {
                $score -= 10;
            }
            // Carrier or other: 0 adjustment
        } else {
            $score -= 5;
        }

        return max(0, min(100, $score));
    }

    public function calculateLongevityScore(): float
    {
        if (!$this->age_years) {
            return 50; // Unknown / still alive
        }

        // BMD average lifespan is 7-10 years
        if ($this->age_years >= 12) return 100;
        if ($this->age_years >= 10) return 90;
        if ($this->age_years >= 8) return 75;
        if ($this->age_years >= 6) return 50;
        if ($this->age_years >= 4) return 30;

        return 20;
    }

    public function calculatePedigreeLongevityScore(): float
    {
        $scores = [];

        // sire_id/dam_id may be stored with a trailing .0 from CSV import (e.g. "82433.0")
        $sireId = $this->sire_id ? preg_replace('/\.0+$/', '', (string) $this->sire_id) : null;
        $damId  = $this->dam_id  ? preg_replace('/\.0+$/', '', (string) $this->dam_id)  : null;

        if ($sireId && ($sire = Dog::where('bg_dog_id', $sireId)->first())) {
            $scores[] = $sire->longevity_score ?? $sire->calculateLongevityScore();
        }

        if ($damId && ($dam = Dog::where('bg_dog_id', $damId)->first())) {
            $scores[] = $dam->longevity_score ?? $dam->calculateLongevityScore();
        }

        return count($scores) ? array_sum($scores) / count($scores) : 50;
    }
}
