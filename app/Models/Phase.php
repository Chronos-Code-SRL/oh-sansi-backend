<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Phase extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order'];

    public function olympiadAreaPhases()
    {
        return $this->hasMany(OlympiadAreaPhase::class);
    }
}
