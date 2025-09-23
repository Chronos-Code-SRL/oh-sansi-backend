<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function olympiadAreas()
    {
        return $this->hasMany(OlympiadArea::class);
    }
}
