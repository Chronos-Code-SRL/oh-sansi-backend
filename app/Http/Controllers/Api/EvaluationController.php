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

class EvaluationController extends Controller
{

    public function updatePartialEvaluation(Request $request, $id): JsonResponse
    {
        // search evaluation by id
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

        //If validation fails
        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        if ($request->has('score')) {
            $evaluation->score = $request->score;
            $evaluation->status = true;
            $this->updateClassificationAutomatic($evaluation);
        }

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

    public function checksUpdates(Request $request): JsonResponse
    {

        $lastUpdateAt = $request->query('lastUpdateAt');
        $lastUpdate = $lastUpdateAt
            ? Carbon::parse($lastUpdateAt)
            : Carbon::createFromTimestamp(0);

        // $updatedEvaluations = Evaluation::where('updated_at', '>', $lastUpdate)
        // ->orderBy('updated_at', 'asc')
        // ->get();

        $updatedEvaluations = Evaluation::with(['registration.contestant'])
            ->where('updated_at', '>', $lastUpdate)
            ->orderBy('updated_at', 'asc')
            ->get();

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
     * Get competitors by phase with eligibility logic
     */
    public function getCompetitorsByPhase(Request $request, $olympiadId, $areaId, $phaseId): JsonResponse
    {
        // Verificar que existe la relación olympiad_area
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

        // Obtener competidores elegibles para esta fase
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
     * Update classification status manually
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
            'classification_status' => 'required|string|in:clasificado,no_clasificado,descalificado',
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

        $evaluation->classification_status = $request->classification_status;

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
        // Para la primera fase (order = 1), todos los registrados pueden participar
        $currentPhase = Phase::find($currentPhaseId);

        if ($currentPhase->order == 1) {
            // Primera fase: todos los evaluados en esta fase
            return Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                ->with(['registration.contestant'])
                ->get()
                ->map(function($evaluation) {
                    return $this->formatCompetitorData($evaluation);
                });
        } else {
            // Fases posteriores: solo clasificados de la fase anterior
            $previousPhaseOrder = $currentPhase->order - 1;

            // Obtener registros que clasificaron en la fase anterior
            $qualifiedRegistrations = $this->getQualifiedFromPreviousPhase(
                $olympiadAreaPhase->olympiad_area_id,
                $previousPhaseOrder
            );

            // Obtener evaluaciones de la fase actual para estos competidores
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

    private function updateClassificationAutomatic(Evaluation $evaluation)
    {
        $contestantId = $evaluation->registration->contestant->id;

        $levelGrade = DB::table('contestant_level_grades')
                ->where('contestant_id', $contestantId)
                ->join('level_grades', 'contestant_level_grades.level_grade_id', '=', 'level_grades.id')
                ->select('level_grades.*')
                ->first();

        $levelGradeId = $levelGrade->id ?? null;

        if ($levelGradeId) {
            // Find the corresponding threshold
            $threshold = \App\Models\OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $evaluation->olympiad_area_phase_id)
                ->where('level_grade_id', $levelGradeId)
                ->first();

            if ($threshold) {
                if ($evaluation->score >= $threshold->score_cut) {
                    $evaluation->classification_status = 'clasificado';
                } else {
                    $evaluation->classification_status = 'no_clasificado';
                }
            } else {
                // No threshold was found for that level
                $evaluation->classification_status = 'descalificado';
            }
        } else {
                // The competitor does not have a registered level
                $evaluation->classification_status = 'descalificado';
        }
    }
}
