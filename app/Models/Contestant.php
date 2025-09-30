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
        'phone_number',
        'email',
        'tutor_name',
        'tutor_number',
        'grade'
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

    /**
     * Get the groups for the contestant
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

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
}
