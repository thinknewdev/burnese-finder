<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Breeder extends Model
{
    use HasFactory;

    protected $fillable = [
        'bg_person_id',
        'first_name',
        'last_name',
        'kennel_name',
        'city',
        'state',
        'country',
        'email',
        'phone',
        'dogs_bred_count',
        'litters_count',
        'grade',
        'health_stats',
    ];

    protected $casts = [
        'health_stats' => 'array',
        'grade' => 'decimal:2',
    ];

    public function dogs()
    {
        return $this->hasMany(Dog::class);
    }

    public function litters()
    {
        return $this->hasMany(Litter::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw(
            "MATCH(first_name, last_name, kennel_name) AGAINST(? IN BOOLEAN MODE)",
            ["*{$term}*"]
        );
    }

    public function scopeByState($query, string $state)
    {
        return $query->where('state', $state);
    }

    public function scopeTopRated($query, int $limit = 10)
    {
        return $query->whereNotNull('grade')
            ->orderByDesc('grade')
            ->limit($limit);
    }
}
