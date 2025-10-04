<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'contestant_id',
        'olympiad_area_id',
        'grade_id',
        'level_id'
    ];

    protected $casts = [];

    /**
     * Get the contestant that owns the registration
     */
    public function contestant()
    {
        return $this->belongsTo(Contestant::class);
    }

    /**
     * Get the olympiad area for the registration
     */
    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }

    // groups() relation removed; Group entity no longer used in the new schema

    /**
     * Get the evaluations for the registration
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Get the grade for the registration
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the level for the registration
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
