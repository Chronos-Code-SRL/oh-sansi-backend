<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OlympiadAreaMedal extends Model
{
    use HasFactory;

    protected $fillable = ['olympiad_area_id', 'gold', 'silver', 'bronze', 'honorable_mention'];

    public function olympiadArea()
    {
        return $this->belongsTo(OlympiadArea::class);
    }    
}
