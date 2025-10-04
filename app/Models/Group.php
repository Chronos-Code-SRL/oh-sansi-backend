<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'contestant_id',
        'registration_id'
    ];

    /**
     * Get the contestant that owns the group
     */
    public function contestant()
    {
        return $this->belongsTo(Contestant::class);
    }

    /**
     * Get the registration that owns the group
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
