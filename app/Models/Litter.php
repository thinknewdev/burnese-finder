<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Litter extends Model
{
    use HasFactory;

    protected $fillable = [
        'bg_litter_id',
        'birth_date',
        'birth_year',
        'sire_id',
        'dam_id',
        'sire_name',
        'dam_name',
        'breeder_id',
        'breeder_name',
        'puppies_count',
        'males_count',
        'females_count',
    ];

    protected $casts = [
        'birth_date' => 'date',
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

    public function scopeByYear($query, int $year)
    {
        return $query->where('birth_year', $year);
    }
}
