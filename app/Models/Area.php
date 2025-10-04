<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function olympiads(): BelongsToMany
    {
        return $this->belongsToMany(Olympiad::class, 'olympiad_areas');
    }

    public function olympiadAreas(): HasMany
    {
        return $this->hasMany(OlympiadArea::class);
    }

    public function phases(): HasManyThrough
    {
        return $this->hasManyThrough(
            OlympiadAreaPhase::class,   // Destination model
            OlympiadArea::class,        // Intermediate model
            'area_id',                  // FK in olympiad_areas
            'olympiad_area_id',         // FK in olympiad_area_phases
            'id',                       // PK in areas
            'id'                        // PK in olympiad_areas
        );
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_areas');
    }
}
