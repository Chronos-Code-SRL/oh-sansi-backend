<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contestant extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'ci_document',
        'gender',
        'school_name',
        'department',
        'grade',
        'phone_number',
        'email',
        'tutor_name',
        'tutor_number'
    ];

    protected $casts = [
        'gender' => 'string',
    ];

    /**
     * Get the registrations for the contestant
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    // groups() relation removed; Group entity no longer used in the new schema

    /**
     * Get the olympiad areas through registrations
     */
    public function olympiadAreas()
    {
        return $this->hasManyThrough(
            OlympiadArea::class,
            Registration::class,
            'contestant_id',
            'id',
            'id',
            'olympiad_area_id'
        );
    }

    /**
     * Get the contestant level grades
     */
    public function contestantLevelGrades()
    {
        return $this->hasMany(ContestantLevelGrade::class);
    }

    /**
     * Get the level grades through contestant level grades
     */
    public function levelGrades()
    {
        return $this->hasManyThrough(
            LevelGrade::class,
            ContestantLevelGrade::class,
            'contestant_id',
            'id',
            'id',
            'level_grade_id'
        );
    }
}
