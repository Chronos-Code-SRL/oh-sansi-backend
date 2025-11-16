<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit as AuditModel;
use App\Models\Grade;

class AuditController extends Controller
{
    /**
     * Return audits for grades only, with user info and diffs.
     */
    public function grades(Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);

        $query = AuditModel::with('user')
            ->where('auditable_type', Grade::class)
            ->orderBy('created_at', 'desc');

        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->query('auditable_id'));
        }

        $audits = $query->paginate($perPage);

        return response()->json($audits);
    }
}
