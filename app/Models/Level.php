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

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'level_grades');
    }
}
