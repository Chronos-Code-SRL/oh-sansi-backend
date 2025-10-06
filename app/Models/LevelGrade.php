<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LevelGrade extends Model
{
    use HasFactory;

    protected $fillable = ['level_id', 'grade_id'];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function olympiadAreas()
    {
        return $this->belongsToMany(OlympiadArea::class, 'olympiad_area_level_grades');
    }
}
