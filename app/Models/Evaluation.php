<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'score',
        'description',
        'registration_id',
        'olympiad_area_phase_id'
    ];

    /**
     * Get the registration that owns the evaluation
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the olympiad area phase for the evaluation
     */
    public function olympiadAreaPhase()
    {
        return $this->belongsTo(OlympiadAreaPhase::class);
    }
}
