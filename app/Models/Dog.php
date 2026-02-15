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

    public function hasCompleteData(): bool
    {
        return $this->hip_rating || $this->elbow_rating ||
               $this->heart_status || $this->eye_status;
    }

    public function calculateGrade(): float
    {
        $healthScore = $this->calculateHealthScore();
        $longevityScore = $this->calculateLongevityScore();
        $breederScore = $this->breeder?->grade ?? 50;

        // Health: 40%, Longevity: 40%, Breeder: 20%
        return ($healthScore * 0.4) + ($longevityScore * 0.4) + ($breederScore * 0.2);
    }

    public function calculateHealthScore(): float
    {
        $score = 50; // Base score

        // Hip rating scoring
        $hipScores = [
            'Excellent' => 30,
            'Good' => 25,
            'Fair' => 15,
            'Borderline' => 5,
            'Mild' => -10,
            'Moderate' => -20,
            'Severe' => -30,
        ];
        foreach ($hipScores as $rating => $points) {
            if (str_contains($this->hip_rating ?? '', $rating)) {
                $score += $points;
                break;
            }
        }

        // Elbow scoring
        if (str_contains($this->elbow_rating ?? '', 'Normal')) {
            $score += 20;
        } elseif ($this->elbow_rating) {
            $score -= 10;
        }

        // Heart and eye clearances
        if (str_contains(strtolower($this->heart_status ?? ''), 'normal') ||
            str_contains(strtolower($this->heart_status ?? ''), 'clear')) {
            $score += 10;
        }
        if (str_contains(strtolower($this->eye_status ?? ''), 'normal') ||
            str_contains(strtolower($this->eye_status ?? ''), 'clear')) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    public function calculateLongevityScore(): float
    {
        if (!$this->age_years) {
            return 50; // Unknown
        }

        // BMD average lifespan is 7-10 years
        if ($this->age_years >= 12) return 100;
        if ($this->age_years >= 10) return 90;
        if ($this->age_years >= 8) return 75;
        if ($this->age_years >= 6) return 50;
        if ($this->age_years >= 4) return 30;

        return 20;
    }
}
