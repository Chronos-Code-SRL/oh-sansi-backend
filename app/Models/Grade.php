<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Grade extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    // Only audit the fillable attributes we care about
    protected $auditInclude = [
        'name'
    ];

    protected $fillable = [
        'name'
    ];

    /**
     * Get the contestants for the grade
     */
    public function contestants()
    {
        return $this->hasMany(Contestant::class);
    }

    /**
     * gradeLevels relation removed; grade_levels table not used in new schema
     */
    // public function gradeLevels() {}
}
