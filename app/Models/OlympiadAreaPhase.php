<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OlympiadAreaPhase extends Model
{
    use HasFactory;

    protected $fillable = ['olympiad_area_id', 'phase_id', 'score_cut'];

    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }
}
