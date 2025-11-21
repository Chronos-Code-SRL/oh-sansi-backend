<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Olympiad extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'edition',
        'start_date',
        'end_date',
        'number_of_phases',
        'default_score_cut',
        'default_max_score',
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

            // Create phases for this area only if they don't exist
            for ($i = 1; $i <= $this->number_of_phases; $i++) {
                // First, ensure the phase exists in the phases table
                $phase = Phase::firstOrCreate([
                    'name' => 'Fase ' . $i,
                    'order' => $i,
                ]);

                // Only create the olympiad_area_phase relationship if it doesn't exist
                $existingRelation = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
                    ->where('phase_id', $phase->id)
                    ->first();

                if (!$existingRelation) {
                    OlympiadAreaPhase::create([
                        'olympiad_area_id' => $olympiadArea->id,
                        'phase_id' => $phase->id,
                    ]);
                }
            }
        }

        return $this->load('areas', 'phases');
    }

    public function updateStatus(){
        $today = Carbon::now()->format('Y-m-d');
        $newStatus = $this->status;

        if ($today >= $this->start_date and $today <= $this->end_date) {
            $newStatus = 'Activa';
        }

        if ($today > $this->end_date) {
            $newStatus = 'Terminada';
        }

        if ($newStatus != $this->status) {
            $this->status = $newStatus;
            $this->save();
        }

        return $this;
    }
}
