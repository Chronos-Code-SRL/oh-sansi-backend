<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Evaluation;
use App\Models\OlympiadArea;
use App\Models\OlympiadAreaPhase;
use App\Models\Phase;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Exists;

use function Adminer\where;

class EvaluationController extends Controller
{
    // Update evaluation score and/or description with automatic classification
    public function updatePartialEvaluation(Request $request, $id): JsonResponse
    {
        // Find evaluation by ID
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            $data = [
                'message' => 'Evaluation not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'score' => 'integer|nullable',
            'description' => 'string|nullable',
        ]);

        // Validate input data
        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        // Update score and status if provided
        if ($request->has('score')) {
            $evaluation->score = $request->score;
            $evaluation->status = true;
            // Automatically update classification based on score
            $this->updateClassificationAutomatic($evaluation);
        }

        // Update description if provided
        if ($request->has('description')) {
            $evaluation->description = $request->description;
        }

        $evaluation->save();

        $data = [
            'message' => 'Evaluation updated successfully',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    // Check for evaluation updates since last sync timestamp
    public function checksUpdates(Request $request): JsonResponse
    {
        // Parse last update timestamp or use epoch time as fallback
        $lastUpdateAt = $request->query('lastUpdateAt');
        $lastUpdate = $lastUpdateAt
            ? Carbon::parse($lastUpdateAt)
            : Carbon::createFromTimestamp(0);

        $updatedEvaluations = Evaluation::with(['registration.contestant'])
            ->where('updated_at', '>', $lastUpdate)
            ->orderBy('updated_at', 'asc')
            ->get();

        // Transform evaluations for frontend consumption
        $transformedEvaluations = $updatedEvaluations->map(function ($evaluation) {
            $contestant = $evaluation->registration->contestant ?? null;

            return [
                'evaluation_id' => $evaluation->id,
                'contestant_id' => $contestant->id ?? null,
                'score' => $evaluation->score,
                'description' => $evaluation->description,
                'status' => $evaluation->status,
                'classification_status' => $evaluation->classification_status,
                'classification_place' => $evaluation->classification_place,
            ];
        });

        $maxUpdatedAt = $updatedEvaluations->max('updated_at') ?? $lastUpdate;

        $data = [
            'new_evaluations' => $transformedEvaluations,
            'last_updated_at' => $maxUpdatedAt,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Get competitors eligible for a specific phase with validation logic
     */
    public function getCompetitorsByPhase(Request $request, $olympiadId, $areaId, $phaseId): JsonResponse
    {
        // Verify that olympiad-area relationship exists
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('area_id', $areaId)
            ->first();

        if (!$olympiadArea) {
            return response()->json([
                'message' => 'Olympiad area relationship not found',
                'status' => 404
            ], 404);
        }

        $olympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $phaseId)
            ->first();

        if (!$olympiadAreaPhase) {
            return response()->json([
                'message' => 'Phase not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Get competitors eligible for this phase
        $eligibleCompetitors = $this->getEligibleCompetitors($olympiadAreaPhase, $phaseId);

        return response()->json([
            'competitors' => $eligibleCompetitors,
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'phase_id' => $phaseId,
            'status' => 200
        ], 200);
    }

    /**
     * Update classification status and place manually
     */
    public function updateClassificationStatus(Request $request, $id): JsonResponse
    {
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            return response()->json([
                'message' => 'Evaluation not found',
                'status' => 404
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'classification_status' => 'nullable|string|in:clasificado,no_clasificado,descalificado',
            'classification_place' => 'nullable|string|in:Oro,Plata,Bronce,Mención honorífica',
            'description' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        if($request->has('classification_status')) {
            $evaluation->classification_status = $request->classification_status;
        }

        if ($request->has('classification_place')) {
            $evaluation->classification_place = $request->classification_place;
        }

        if ($request->has('description')) {
            $evaluation->description = $request->description;
        }

        $evaluation->save();

        return response()->json([
            'message' => 'Classification status updated successfully',
            'evaluation' => $evaluation,
            'status' => 200
        ], 200);
    }

    /**
     * Get eligible competitors for a phase
     */
    private function getEligibleCompetitors($olympiadAreaPhase, $currentPhaseId)
    {
        // For first phase (order = 1), all registered can participate
        $currentPhase = Phase::find($currentPhaseId);

        if ($currentPhase->order == 1) {
            // First phase: all evaluated in this phase
            return Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                ->with(['registration.contestant'])
                ->get()
                ->map(function($evaluation) {
                    return $this->formatCompetitorData($evaluation);
                });
        } else {
            // Subsequent phases: only qualified from previous phase
            $previousPhaseOrder = $currentPhase->order - 1;

            // Get registrations that qualified in previous phase
            $qualifiedRegistrations = $this->getQualifiedFromPreviousPhase(
                $olympiadAreaPhase->olympiad_area_id,
                $previousPhaseOrder
            );

            // Get evaluations from current phase for these competitors
            return Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                ->whereIn('registration_id', $qualifiedRegistrations)
                ->with(['registration.contestant'])
                ->get()
                ->map(function($evaluation) {
                    return $this->formatCompetitorData($evaluation);
                });
        }
    }

    /**
     * Get registrations that qualified from previous phase
     */
    private function getQualifiedFromPreviousPhase($olympiadAreaId, $previousPhaseOrder)
    {
        return Evaluation::whereHas('olympiadAreaPhase', function($q) use ($olympiadAreaId, $previousPhaseOrder) {
                $q->where('olympiad_area_id', $olympiadAreaId)
                  ->whereHas('phase', function($phaseQuery) use ($previousPhaseOrder) {
                      $phaseQuery->where('order', $previousPhaseOrder);
                  });
            })
            ->where('classification_status', 'clasificado')
            ->pluck('registration_id');
    }

    /**
     * Format competitor data for response
     */
    private function formatCompetitorData($evaluation)
    {
        $contestant = $evaluation->registration->contestant ?? null;

        return [
            'evaluation_id' => $evaluation->id,
            'registration_id' => $evaluation->registration_id,
            'contestant_id' => $contestant->id ?? null,
            'first_name' => $contestant->first_name ?? null,
            'last_name' => $contestant->last_name ?? null,
            'ci_document' => $contestant->ci_document ?? null,
            'score' => $evaluation->score,
            'description' => $evaluation->description,
            'status' => $evaluation->status,
            'classification_status' => $evaluation->classification_status,
            'classification_place' => $evaluation->classification_place,
        ];
    }

    // Automatically update classification status based on score cut threshold
    private function updateClassificationAutomatic(Evaluation $evaluation)
    {
        // Don't change status if already disqualified
        if ($evaluation->classification_status === 'descalificado') {
            return;
        }

        $contestantId = $evaluation->registration->contestant->id;

        // Get contestant's level grade information
        $levelGrade = DB::table('contestant_level_grades')
                ->where('contestant_id', $contestantId)
                ->join('level_grades', 'contestant_level_grades.level_grade_id', '=', 'level_grades.id')
                ->select('level_grades.*')
                ->first();

        $levelGradeId = $levelGrade->id ?? null;

        if ($levelGradeId) {
            // Find the score cut threshold for this level
            $threshold = \App\Models\OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $evaluation->olympiad_area_phase_id)
                ->where('level_grade_id', $levelGradeId)
                ->first();

            if ($threshold) {
                // Compare score against threshold to determine classification
                if ($evaluation->score >= $threshold->score_cut) {
                    $evaluation->classification_status = 'clasificado';
                } else {
                    $evaluation->classification_status = 'no_clasificado';
                }
            } else {
                // No threshold found for this level - disqualify
                $evaluation->classification_status = 'descalificado';
            }
        } else {
                // Contestant has no registered level - disqualify
                $evaluation->classification_status = 'descalificado';
        }
    }

    // Check if score cut threshold can be edited (no evaluated competitors exist)
    public function checkEvaluations(string $olympiadId, string $phaseId, string $areaId, string $levelId)
    {
        $olympiadAreaId = OlympiadArea::where('olympiad_id', $olympiadId)
        ->where('area_id', $areaId)
        ->value('id');

    if (!$olympiadAreaId) {
        return response()->json([
            'message' => 'Olympiad and area not found'
        ], 404);
    }

    $olympiadAreaPhaseId = OlympiadAreaPhase::where('olympiad_area_id', $olympiadAreaId)
        ->where('phase_id', $phaseId)
        ->value('id');

    if (!$olympiadAreaPhaseId) {
        return response()->json([
            'message' => 'Phase not found for this olympiad area'
        ], 404);
    }

    // Check if there are evaluated competitors for this level
    $existEvaluation = DB::table('evaluations as e')
        ->join('registrations as r', 'e.registration_id', '=', 'r.id')
        ->join('contestant_level_grades as clg', 'clg.contestant_id', '=', 'r.contestant_id')
        ->join('level_grades as lg', 'lg.id', '=', 'clg.level_grade_id')
        ->where('r.olympiad_area_id', $olympiadAreaId)
        ->where('lg.level_id', $levelId)
        ->where('e.olympiad_area_phase_id', $olympiadAreaPhaseId)
        ->whereNotNull('e.score')
        ->exists();

    if (!$existEvaluation) {
        return response()->json([
            'message' => 'No hay competidores calificados puedes editar el umbral',
            'status' => 200
        ], 200);
    }

    return response()->json([
        'message'=> 'El umbral no se puede editar ya existen competidores calificados',
        'status' => 403
    ], 403);
    }
}
