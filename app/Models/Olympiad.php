<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Olympiad extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'edition',
        'start_date',
        'end_date',
    ];

    public function olympiadAreas()
    {
        return $this->hasMany(OlympiadArea::class);
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'olympiad_areas');
    }

    public function phases()
    {
        return $this->hasManyThrough(
            OlympiadAreaPhase::class,   // Destination model
            OlympiadArea::class,        // Intermediate model
            'olympiad_id',              // FK in olympiad_area
            'olympiad_area_id',         // FK in olympiad_area_phase
            'id',                       // PK in olympiads
            'id');                      // PK in olympiad_area
    }
}
