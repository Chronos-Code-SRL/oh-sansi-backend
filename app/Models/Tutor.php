<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tutor extends Model
{
    use HasFactory;

    protected $fillable = ['first_name','last_name','phone','email'];

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class);
    }
}


