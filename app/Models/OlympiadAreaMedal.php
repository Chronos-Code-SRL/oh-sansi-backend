<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OlympiadAreaMedal extends Model
{
    use HasFactory;

    protected $fillable = ['olympiad_area_id', 'gold', 'silver', 'bronze', 'honorable_mention', 'minimum_classification_score'];

    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }
}
