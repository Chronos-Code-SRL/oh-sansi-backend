<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'olympiad_area_id',
        'grade_id',
        'level_id'
    ];

    /**
     * Get the olympiad area for the grade level
     */
    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }

    /**
     * Get the grade for the grade level
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the level for the grade level
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
