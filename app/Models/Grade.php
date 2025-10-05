<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Get the contestants for the grade
     */
    public function contestants()
    {
        return $this->hasMany(Contestant::class);
    }

    /**
     * Get the grade levels for the grade
     */
    public function gradeLevels()
    {
        return $this->hasMany(GradeLevel::class);
    }
}
