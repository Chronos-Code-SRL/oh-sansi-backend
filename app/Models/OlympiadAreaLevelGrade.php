<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OlympiadAreaLevelGrade extends Model
{
    use HasFactory;

    protected $table = 'olympiad_area_level_grades';
    protected $fillable = ['olympiad_area_id', 'level_grade_id'];

    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }

    public function levelGrade()
    {
        return $this->belongsTo(LevelGrade::class);
    }

    public function olympiadAreaPhaseLevelGrades()
    {
        return $this->hasMany(OlympiadAreaPhaseLevelGrade::class);
    }
}
