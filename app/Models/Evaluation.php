<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class Evaluation extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

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
     * Attributes to include in the Audit.
     *
     * @var array
     */
    protected $auditInclude = [
        'score',
        'description',
        'status',
        'classification_status',
        'classification_place'
    ];

    /**
     * Exclude events from being audited.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated'
    ];

    /**
     * Exclude specific fields from being audited.
     *
     * @var array
     */
    protected $auditExclude = [
        'updated_at',
        'created_at'
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
