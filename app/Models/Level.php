<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Get the grade levels for the level
     */
    public function gradeLevels()
    {
        return $this->hasMany(GradeLevel::class);
    }
}
