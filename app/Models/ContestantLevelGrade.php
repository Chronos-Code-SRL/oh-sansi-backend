<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContestantLevelGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'contestant_id',
        'level_grade_id'
    ];

    public function contestant()
    {
        return $this->belongsTo(Contestant::class);
    }

    public function levelGrade()
    {
        return $this->belongsTo(LevelGrade::class);
    }
}
