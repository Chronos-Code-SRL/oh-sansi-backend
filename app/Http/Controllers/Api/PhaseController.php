<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\Phase;
use App\Models\OlympiadArea;
use App\Models\OlympiadAreaPhase;
use App\Models\Evaluation;
use App\Models\OlympiadAreaPhaseLevelGrade;
use App\Models\LevelGrade;

/**
 * @OA\Tag(
 *     name="Phases",
 *     description="Endpoints for Phases management"
 * )
 */
class PhaseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/phases",
     *     summary="Get list of phases",
     *     tags={"Phases"},
     *     @OA\Response(
     *         response=200,
     *         description="List of phases retrieved successfully",
     *     )
     * )
     */
    public function index()
    {
        $phases = Phase::all();

        if ($phases->isEmpty()) {
            $data = [
                'message' => 'No phases found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'phases' => $phases,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/phases",
     *     summary="Create a new phase",
     *     tags={"Phases"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description","start_date","end_date"},
     *             @OA\Property(property="name", type="string", example="Fase Clasificatoria"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Phase created successfully",
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Data validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:phases,name',
            'order' => 'required|integer|min:1|unique:phases,order'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $phase = Phase::create([
            'name' => $request->name,
            'order' => $request->order
        ]);

        if (!$phase) {
            $data = [
                'message' => 'Error creating phase',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $data = [
            'message' => 'Phase created successfully',
            'phase' => $phase,
            'status' => 201
        ];

        return response()->json($data, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/phases/{id}",
     *     summary="Get a phase by ID",
     *     tags={"Phases"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phase found",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Phase not found",
     *     )
     * )
     */
    public function show(string $id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            $data = [
                'message' => 'Phase not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'phase' => $phase,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/phases/{id}",
     *     summary="Update a phase by ID",
     *     tags={"Phases"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phase updated successfully"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            $data = [
                'message' => 'Phase not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Data validation
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('phases', 'name')->ignore($phase->id)
            ],
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('phases', 'order')->ignore($phase->id)
            ]
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $phase->name = $request->name;
        $phase->order = $request->order;

        if (!$phase->save()) {
            $data = [
                'message' => 'Error updating phase',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        $data = [
            'message' => 'Phase updated successfully',
            'phase' => $phase,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/phases/{id}",
     *     summary="Delete a phase by ID",
     *     tags={"Phases"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phase deleted successfully"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            $data = [
                'message' => 'Phase not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $phase->delete();

        $data = [
            'message' => 'Phase deleted successfully',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/phase-status",
     *     summary="Get the status of all phases for an olympiad area",
     *     tags={"Phases"},
     *     @OA\Parameter(
     *         name="olympiadId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phase statuses retrieved successfully",
     *     )
     * )
     */
    public function getPhaseStatus(string $olympiadId, string $areaId)
    {
        // Verificar que existe la relación olympiad_area
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('area_id', $areaId)
            ->first();

        if (!$olympiadArea) {
            $data = [
                'message' => 'Olympiad area relationship not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Obtener todas las fases con su estado para esta olympiad_area
        $phaseStatuses = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->with('phase')
            ->get()
            ->map(function ($olympiadAreaPhase) {
                return [
                    'phase_id' => $olympiadAreaPhase->phase_id,
                    'phase_name' => $olympiadAreaPhase->phase->name,
                    'phase_order' => $olympiadAreaPhase->phase->order,
                    'status' => $olympiadAreaPhase->status
                ];
            });

        $data = [
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'phase_statuses' => $phaseStatuses,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/phase-status",
     *     summary="Update the status of a specific phase for an olympiad area",
     *     tags={"Phases"},
     *     @OA\Parameter(
     *         name="olympiadId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phase_id","status"},
     *             @OA\Property(property="phase_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"Sin empezar", "Activa", "Terminada"}, example="Activa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phase status updated successfully"
     *     )
     * )
     */
    public function updatePhaseStatus(Request $request, string $olympiadId, string $areaId)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'phase_id' => 'required|integer|exists:phases,id',
            'status' => 'required|string|in:Sin empezar,Activa,Terminada'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        // Verificar que existe la relación olympiad_area
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('area_id', $areaId)
            ->first();

        if (!$olympiadArea) {
            $data = [
                'message' => 'Olympiad area relationship not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Buscar la relación olympiad_area_phase específica
        $olympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $request->phase_id)
            ->first();

        if (!$olympiadAreaPhase) {
            $data = [
                'message' => 'Phase not found for this olympiad area',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Actualizar el estado
        $olympiadAreaPhase->status = $request->status;

        if (!$olympiadAreaPhase->save()) {
            $data = [
                'message' => 'Error updating phase status',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        // Si la fase se marca como "Terminada", procesar clasificaciones
        if ($request->status === 'Terminada') {
            $this->processPhaseClassifications($olympiadAreaPhase, $olympiadArea);
        }

        // Cargar la información de la fase para la respuesta
        $olympiadAreaPhase->load('phase');

        $data = [
            'message' => 'Phase status updated successfully',
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'phase_id' => $olympiadAreaPhase->phase_id,
            'phase_name' => $olympiadAreaPhase->phase->name,
            'status' => $olympiadAreaPhase->status,
            'status_code' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Process automatic classification when a phase is marked as "Terminada"
     */
    private function processPhaseClassifications($olympiadAreaPhase, $olympiadArea)
    {
        // Obtener todas las evaluaciones de esta fase
        $evaluations = Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
            ->whereNotNull('score')
            ->get();

        // Verificar si es la fase final
        $isFinalPhase = $this->isFinalPhase($olympiadAreaPhase, $olympiadArea);

        // Obtener la siguiente fase si no es la final
        $nextOlympiadAreaPhase = null;
        if (!$isFinalPhase) {
            $nextOlympiadAreaPhase = $this->getNextPhase($olympiadAreaPhase, $olympiadArea);
        }

        foreach ($evaluations as $evaluation) {
            // Obtener score_cut para esta evaluación específica
            $scoreCut = $this->getScoreCut($olympiadAreaPhase, $evaluation);

            if ($scoreCut !== null) {
                // Clasificar basado en score_cut
                if ($evaluation->score >= $scoreCut) {
                    $evaluation->classification_status = 'clasificado';

                    // Si no es la fase final y el competidor clasifica, crear registro para siguiente fase
                    if (!$isFinalPhase && $nextOlympiadAreaPhase) {
                        $this->createNextPhaseEvaluation($evaluation, $nextOlympiadAreaPhase);
                    }
                } else {
                    $evaluation->classification_status = 'no_clasificado';
                }
            }
        }

        // Si es la fase final, asignar medallas
        if ($isFinalPhase) {
            $this->assignMedals($evaluations);
        }

        // Guardar todas las evaluaciones
        foreach ($evaluations as $evaluation) {
            $evaluation->save();
        }
    }

    /**
     * Get the score cut for a specific evaluation
     */
    private function getScoreCut($olympiadAreaPhase, $evaluation)
    {
        // Usar consulta SQL directa basada en la tabla contestant_level_grades
        $scoreCut = DB::table('evaluations as e')
            ->join('registrations as r', 'e.registration_id', '=', 'r.id')
            ->join('contestant_level_grades as clg', 'r.contestant_id', '=', 'clg.contestant_id')
            ->join('olympiad_area_phase_level_grades as oaplg', 'clg.level_grade_id', '=', 'oaplg.level_grade_id')
            ->where('e.id', $evaluation->id)
            ->value('oaplg.score_cut');

        return $scoreCut;
    }

    /**
     * Check if the current phase is the final phase for this olympiad area
     */
    private function isFinalPhase($currentOlympiadAreaPhase, $olympiadArea)
    {
        $maxOrder = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->join('phases', 'olympiad_area_phases.phase_id', '=', 'phases.id')
            ->max('phases.order');

        $currentPhase = $currentOlympiadAreaPhase->load('phase');

        return $currentPhase->phase->order == $maxOrder;
    }

    /**
     * Assign medals for the final phase
     */
    private function assignMedals($evaluations)
    {
        // Ordenar por puntuación descendente (solo clasificados)
        $classifiedEvaluations = $evaluations
            ->where('classification_status', 'clasificado')
            ->sortByDesc('score')
            ->values();

        foreach ($classifiedEvaluations as $index => $evaluation) {
            switch ($index) {
                case 0:
                    $evaluation->classification_place = 'Oro';
                    break;
                case 1:
                    $evaluation->classification_place = 'Plata';
                    break;
                case 2:
                    $evaluation->classification_place = 'Bronce';
                    break;
                default:
                    $evaluation->classification_place = 'Mención honorífica';
                    break;
            }
        }
    }

    /**
     * Get the next phase for this olympiad area
     */
    private function getNextPhase($currentOlympiadAreaPhase, $olympiadArea)
    {
        $currentPhase = $currentOlympiadAreaPhase->load('phase');
        $nextPhaseOrder = $currentPhase->phase->order + 1;

        // Buscar la siguiente fase en orden
        $nextPhase = Phase::where('order', $nextPhaseOrder)->first();

        if (!$nextPhase) {
            return null;
        }

        // Buscar el OlympiadAreaPhase correspondiente para la siguiente fase
        $nextOlympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $nextPhase->id)
            ->first();

        return $nextOlympiadAreaPhase;
    }

    /**
     * Create evaluation record for next phase when competitor qualifies
     */
    private function createNextPhaseEvaluation($currentEvaluation, $nextOlympiadAreaPhase)
    {
        // Verificar si ya existe un registro para este competidor en la siguiente fase
        $existingEvaluation = Evaluation::where('registration_id', $currentEvaluation->registration_id)
            ->where('olympiad_area_phase_id', $nextOlympiadAreaPhase->id)
            ->first();

        // Solo crear si no existe
        if (!$existingEvaluation) {
            Evaluation::create([
                'registration_id' => $currentEvaluation->registration_id,
                'olympiad_area_phase_id' => $nextOlympiadAreaPhase->id,
                'score' => null,
                'description' => null,
                'status' => false,
                'classification_status' => null,
                'classification_place' => null
            ]);
        }
    }
}
