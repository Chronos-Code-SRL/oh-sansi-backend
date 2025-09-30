<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_group',
        'contestant_id',
        'olympiad_area_id'
    ];

    protected $casts = [
        'is_group' => 'boolean',
    ];

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

    /**
     * Get the groups for the registration
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the evaluations for the registration
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}
