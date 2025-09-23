<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function olympiads()
    {
        return $this->belongsToMany(Olympiad::class, 'olympiad_areas');
    }

    public function olympiadAreas()
    {
        return $this->hasMany(OlympiadArea::class);
    }

    public function phases()
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
}
