<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contestant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','lastname','gender','ci_document','tutor_id','school_id','department','grade_id'
    ];

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }
}


