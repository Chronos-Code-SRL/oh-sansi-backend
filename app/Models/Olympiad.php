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

    public static function createWithDefaults(array $data) {
        $olympiad = self::create($data);

        // Default Areas
        $defaultAreas = ['Matemáticas', 'Física', 'Química', 'Informática'];
        $areaIds = [];

        foreach ($defaultAreas as $areaName) {
            $area = Area::firstOrCreate(['name' => $areaName]);
            $areaIds[] = $area->id;
        }

        // Relate Olympiad with Areas
        $olympiad->areas()->sync($areaIds);

        // Default Phases (number_of_phases)
        $phaseIds = [];
        for ($i = 1; $i <= $olympiad->number_of_phases; $i++) {
            $phase = Phase::firstOrCreate([
                'name' => 'Fase ' . $i,
                'order' => $i,
            ]);
            $phaseIds[] = $phase->id;
        }

        // Relate each OlympiadArea with all Phases
        foreach ($areaIds as $areaId) {
            // Get the pivot relationship between Olympiad and Area
            $olympiadArea = OlympiadArea::where('olympiad_id', $olympiad->id)
                ->where('area_id', $areaId)
                ->first();

            foreach ($phaseIds as $phaseId) {
                OlympiadAreaPhase::firstOrCreate([
                    'olympiad_area_id' => $olympiadArea->id,
                    'phase_id' => $phaseId,
                ]);
            }
        }

        return $olympiad->load('areas', 'phases');
    }
}
