<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit as AuditModel;
use Carbon\Carbon;
use App\Models\Evaluation;

class AuditController extends Controller
{
    // Audits for grades removed; controller now serves evaluations only.

    /**
     * Return audits for evaluations (calificaciones) only.
     */
    public function evaluations(Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);

        $query = AuditModel::with('user')
            ->where('auditable_type', Evaluation::class)
            ->orderBy('created_at', 'desc');

        if ($request->filled('event')) {
            $query->where('event', $request->query('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->query('date_from'));
            $query->where('created_at', '>=', $from);
        }

        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->query('date_to'));
            $query->where('created_at', '<=', $to);
        }

        $audits = $query->paginate($perPage);

        $transformed = $audits->getCollection()->map(function ($a) {
            $old = $a->old_values ?: [];
            $new = $a->new_values ?: [];

            $changes = [];
            $keys = array_unique(array_merge(array_keys((array)$old), array_keys((array)$new)));
            foreach ($keys as $k) {
                $ov = array_key_exists($k, (array)$old) ? $old[$k] : null;
                $nv = array_key_exists($k, (array)$new) ? $new[$k] : null;
                if ($ov !== $nv) {
                    $changes[$k] = ['old' => $ov, 'new' => $nv];
                }
            }

            return [
                'id' => $a->id,
                'event' => $a->event,
                'auditable_type' => $a->auditable_type,
                'auditable_id' => $a->auditable_id,
                'changes' => $changes,
                'old_values' => $old,
                'new_values' => $new,
                'user' => $a->user ? [
                    'id' => $a->user->id ?? null,
                    'name' => $a->user->name ?? null,
                    'email' => $a->user->email ?? null,
                ] : null,
                'ip_address' => $a->ip_address ?? null,
                'url' => $a->url ?? null,
                'created_at' => $a->created_at,
            ];
        });

        $audits->setCollection($transformed);

        return response()->json($audits);
    }
}
