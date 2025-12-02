<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Evaluation;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Validator;

class EvaluationAuditController extends Controller
{
    /**
     * Get evaluation audit logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEvaluationLogs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'evaluation_id' => 'nullable|integer|exists:evaluations,id',
            'contestant_id' => 'nullable|integer|exists:contestants,id',
            'olympiad_id' => 'nullable|integer|exists:olympiads,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'phase_id' => 'nullable|integer|exists:phases,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'event' => 'nullable|string|in:created,updated',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $query = Audit::where('auditable_type', 'App\\Models\\Evaluation');

        // Filter by specific evaluation
        if ($request->has('evaluation_id')) {
            $query->where('auditable_id', $request->evaluation_id);
        }

        // Filter by contestant (through evaluation)
        if ($request->has('contestant_id')) {
            $evaluationIds = Evaluation::whereHas('registration', function($q) use ($request) {
                $q->where('contestant_id', $request->contestant_id);
            })->pluck('id');
            $query->whereIn('auditable_id', $evaluationIds);
        }

        // Filter by olympiad, area, or phase
        if ($request->has('olympiad_id') || $request->has('area_id') || $request->has('phase_id')) {
            $evaluationIds = Evaluation::whereHas('olympiadAreaPhase', function($q) use ($request) {
                if ($request->has('phase_id')) {
                    $q->where('phase_id', $request->phase_id);
                }

                if ($request->has('olympiad_id') || $request->has('area_id')) {
                    $q->whereHas('olympiadArea', function($subQ) use ($request) {
                        if ($request->has('olympiad_id')) {
                            $subQ->where('olympiad_id', $request->olympiad_id);
                        }
                        if ($request->has('area_id')) {
                            $subQ->where('area_id', $request->area_id);
                        }
                    });
                }
            })->pluck('id');
            $query->whereIn('auditable_id', $evaluationIds);
        }

        // Filter by user who made the change
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event type
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 15);
        $audits = $query->with(['user:id,first_name,last_name,email'])
                       ->orderBy('created_at', 'desc')
                       ->paginate($perPage);

        // Transform the audit data for clean response
        $transformedAudits = $audits->getCollection()->map(function ($audit) {
            $evaluation = Evaluation::with(['registration.contestant', 'olympiadAreaPhase.olympiadArea.olympiad', 'olympiadAreaPhase.olympiadArea.area', 'olympiadAreaPhase.phase'])
                                   ->find($audit->auditable_id);

            return [
                'audit_id' => $audit->id,
                'event' => $audit->event,
                'created_at' => $audit->created_at,
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'url' => $audit->url,
                'user' => [
                    'id' => $audit->user->id ?? null,
                    'name' => ($audit->user->first_name ?? '') . ' ' . ($audit->user->last_name ?? ''),
                    'email' => $audit->user->email ?? null
                ],
                'evaluation' => [
                    'id' => $evaluation->id ?? null,
                    'contestant' => [
                        'id' => $evaluation->registration->contestant->id ?? null,
                        'name' => ($evaluation->registration->contestant->first_name ?? '') . ' ' . ($evaluation->registration->contestant->last_name ?? ''),
                        'ci_document' => $evaluation->registration->contestant->ci_document ?? null
                    ],
                    'olympiad' => [
                        'id' => $evaluation->olympiadAreaPhase->olympiadArea->olympiad->id ?? null,
                        'name' => $evaluation->olympiadAreaPhase->olympiadArea->olympiad->name ?? null
                    ],
                    'area' => [
                        'id' => $evaluation->olympiadAreaPhase->olympiadArea->area->id ?? null,
                        'name' => $evaluation->olympiadAreaPhase->olympiadArea->area->name ?? null
                    ],
                    'phase' => [
                        'id' => $evaluation->olympiadAreaPhase->phase->id ?? null,
                        'name' => $evaluation->olympiadAreaPhase->phase->name ?? null,
                        'order' => $evaluation->olympiadAreaPhase->phase->order ?? null
                    ]
                ],
                'changes' => [
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values
                ]
            ];
        });

        return response()->json([
            'logs' => $transformedAudits,
            'pagination' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'from' => $audits->firstItem(),
                'to' => $audits->lastItem()
            ],
            'status' => 200
        ], 200);
    }

    /**
     * Get evaluation audit statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEvaluationAuditStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'olympiad_id' => 'nullable|integer|exists:olympiads,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'phase_id' => 'nullable|integer|exists:phases,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $query = Audit::where('auditable_type', 'App\\Models\\Evaluation');

        // Apply same filters as main logs method
        if ($request->has('olympiad_id') || $request->has('area_id') || $request->has('phase_id')) {
            $evaluationIds = Evaluation::whereHas('olympiadAreaPhase', function($q) use ($request) {
                if ($request->has('phase_id')) {
                    $q->where('phase_id', $request->phase_id);
                }

                if ($request->has('olympiad_id') || $request->has('area_id')) {
                    $q->whereHas('olympiadArea', function($subQ) use ($request) {
                        if ($request->has('olympiad_id')) {
                            $subQ->where('olympiad_id', $request->olympiad_id);
                        }
                        if ($request->has('area_id')) {
                            $subQ->where('area_id', $request->area_id);
                        }
                    });
                }
            })->pluck('id');
            $query->whereIn('auditable_id', $evaluationIds);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $totalLogs = $query->count();
        $createdCount = $query->clone()->where('event', 'created')->count();
        $updatedCount = $query->clone()->where('event', 'updated')->count();

        // Most active users
        $activeUsersQuery = $query->clone()
                                 ->selectRaw('user_id, count(*) as activity_count')
                                 ->whereNotNull('user_id')
                                 ->groupBy('user_id')
                                 ->orderBy('activity_count', 'desc')
                                 ->limit(10)
                                 ->get();

        $activeUsers = $activeUsersQuery->map(function($item) {
            $user = \App\Models\User::find($item->user_id);
            return [
                'user' => [
                    'id' => $user->id ?? null,
                    'name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                    'email' => $user->email ?? null
                ],
                'activity_count' => $item->activity_count
            ];
        });

        // Activity by day (last 7 days)
        $activityByDay = $query->clone()
                             ->selectRaw('DATE(created_at) as date, count(*) as count')
                             ->where('created_at', '>=', now()->subDays(7))
                             ->groupBy('date')
                             ->orderBy('date', 'desc')
                             ->get();

        return response()->json([
            'statistics' => [
                'total_logs' => $totalLogs,
                'created_evaluations' => $createdCount,
                'updated_evaluations' => $updatedCount,
                'most_active_users' => $activeUsers,
                'activity_by_day' => $activityByDay
            ],
            'status' => 200
        ], 200);
    }

    /**
     * Get specific evaluation audit history
     *
     * @param int $evaluationId
     * @return JsonResponse
     */
    public function getEvaluationHistory(int $evaluationId): JsonResponse
    {
        $evaluation = Evaluation::find($evaluationId);

        if (!$evaluation) {
            return response()->json([
                'message' => 'Evaluation not found',
                'status' => 404
            ], 404);
        }

        $audits = Audit::where('auditable_type', 'App\\Models\\Evaluation')
                      ->where('auditable_id', $evaluationId)
                      ->with(['user:id,first_name,last_name,email'])
                      ->orderBy('created_at', 'desc')
                      ->get();

        $transformedAudits = $audits->map(function ($audit) {
            return [
                'audit_id' => $audit->id,
                'event' => $audit->event,
                'created_at' => $audit->created_at,
                'ip_address' => $audit->ip_address,
                'user' => [
                    'id' => $audit->user->id ?? null,
                    'name' => ($audit->user->first_name ?? '') . ' ' . ($audit->user->last_name ?? ''),
                    'email' => $audit->user->email ?? null
                ],
                'changes' => [
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values
                ]
            ];
        });

        return response()->json([
            'evaluation_id' => $evaluationId,
            'audit_history' => $transformedAudits,
            'total_changes' => $audits->count(),
            'status' => 200
        ], 200);
    }
}
