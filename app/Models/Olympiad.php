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
        'status',
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
            'id'                        // PK in olympiad_areas
        );
    }

    public function assignAreas(array $areaNames)
    {
        // Get areas by their names
        $areas = Area::whereIn('name', $areaNames)->get();
        $areaIds = $areas->pluck('id')->toArray();

        // Check if any area is already related to this olympiad
        $existingAreas = $this->areas()->whereIn('areas.id', $areaIds)->exists();
        if ($existingAreas) {
            return response()->json([
                'message' => 'One or more areas are already assigned to this olympiad',
                'status' => 400
            ], 400);
        }

        // Relate Olympiad with Areas
        $this->areas()->attach($areaIds);

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
