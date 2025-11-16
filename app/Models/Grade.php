<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\Auditable;

class Grade extends Model
{
    use HasFactory, Auditable;

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
     * gradeLevels relation removed; grade_levels table not used in new schema
     */
    // public function gradeLevels() {}
}
