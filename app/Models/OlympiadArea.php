<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OlympiadArea extends Model
{
    use HasFactory;

    protected $fillable = ['olympiad_id', 'area_id'];

    public function olympiad()
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function phases()
    {
        return $this->hasMany(OlympiadAreaPhase::class);
    }
}
