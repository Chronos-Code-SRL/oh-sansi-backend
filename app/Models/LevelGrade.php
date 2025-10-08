<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LevelGrade extends Model
{
    use HasFactory;

    protected $fillable = ['olympiad_area_id', 'level_id', 'grade_id'];

    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
