<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OlympiadAreaPhaseLevelGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'olympiad_area_phase_id',
        //'olympiad_area_level_grade_id',
        'level_grade_id',
        'score_cut'
    ];

    public function olympiadAreaPhase()
    {
        return $this->belongsTo(OlympiadAreaPhase::class);
    }

    // public function olympiadAreaLevelGrade()
    // {
    //     return $this->belongsTo(OlympiadAreaLevelGrade::class);
    // }

    public function levelGrade()
    {
        return $this->belongsTo(LevelGrade::class);
    }
}
