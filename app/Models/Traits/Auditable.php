<?php

namespace App\Models\Traits;

use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::recordAudit($model, 'created');
        });

        static::updated(function ($model) {
            self::recordAudit($model, 'updated');
        });

        static::deleted(function ($model) {
            self::recordAudit($model, 'deleted');
        });
    }

    protected static function recordAudit($model, $event)
    {
        $user = Auth::user();

        $old = null;
        $new = null;

        if ($event === 'created') {
            $new = $model->getAttributes();
        } elseif ($event === 'updated') {
            $old = $model->getOriginal();
            $new = $model->getChanges();
        } elseif ($event === 'deleted') {
            $old = $model->getOriginal();
        }

        try {
            Audit::create([
                'user_id' => $user?->id,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'event' => $event,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            // Avoid breaking the app if auditing fails for some reason.
        }
    }
}
