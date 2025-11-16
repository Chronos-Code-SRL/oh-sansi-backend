<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Evaluation extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    // Audit only the important fields for calificaciones
    protected $auditInclude = [
        'score',
        'status',
        'classification_status',
        'classification_place',
    ];

    protected $fillable = [
        'score',
        'description',
        'registration_id',
        'olympiad_area_phase_id',
        'status',
        'classification_status',
        'classification_place'
    ];

    /**
     * Get the registration that owns the evaluation
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the olympiad area phase for the evaluation
     */
    public function olympiadAreaPhase()
    {
        return $this->belongsTo(OlympiadAreaPhase::class);
    }
}
