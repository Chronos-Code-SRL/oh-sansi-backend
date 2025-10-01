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
        'number_of_phases',
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

    public function assignAreas(array $areaNames) {
        $areaIds = [];

        // Create or get areas
        foreach ($areaNames as $areaName) {
            $area = Area::firstOrCreate(['name' => $areaName]);
            $areaIds[] = $area->id;
        }

        // Relate Olympiad with Areas
        $this->areas()->sync($areaIds);

        // Create phases for each area
        foreach ($areaIds as $areaId) {
            $olympiadArea = OlympiadArea::where('olympiad_id', $this->id)
                ->where('area_id', $areaId)
                ->first();

            // Create phases for this area
            for ($i = 1; $i <= $this->number_of_phases; $i++) {
                $phase = Phase::firstOrCreate([
                    'name' => 'Fase ' . $i,
                    'order' => $i,
                ]);

                OlympiadAreaPhase::firstOrCreate([
                    'olympiad_area_id' => $olympiadArea->id,
                    'phase_id' => $phase->id,
                ]);
            }
        }

        return $this->load('areas', 'phases');
    }
}
