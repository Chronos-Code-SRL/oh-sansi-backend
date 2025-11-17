<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\Models\Phase;
use App\Models\OlympiadArea;
use App\Models\OlympiadAreaPhase;
use App\Models\Evaluation;
use App\Models\OlympiadAreaPhaseLevelGrade;
use App\Models\LevelGrade;
use App\Models\Level;

class PhaseController extends Controller
{
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

    public function getPhaseStatus(string $olympiadId, string $areaId, string $levelId)
    {
        // Verify that the olympiad_area relationship exists
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

        // Verify that the level_grade exists for this level and olympiad_area
        $levelGrade = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId)
            ->first();

        if (!$levelGrade) {
            $data = [
                'message' => 'Level not found for this olympiad area',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Get all phases with their status for this specific level
        $phaseStatuses = OlympiadAreaPhaseLevelGrade::where('level_grade_id', $levelGrade->id)
            ->with(['olympiadAreaPhase.phase'])
            ->get()
            ->map(function ($oaplg) {
                return [
                    'phase_id' => $oaplg->olympiadAreaPhase->phase_id,
                    'phase_name' => $oaplg->olympiadAreaPhase->phase->name,
                    'phase_order' => $oaplg->olympiadAreaPhase->phase->order,
                    'status' => $oaplg->status
                ];
            })
            ->sortBy('phase_order')
            ->values();

        $data = [
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'level_id' => $levelId,
            'phase_statuses' => $phaseStatuses,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    public function updatePhaseStatus(Request $request, string $olympiadId, string $areaId, string $levelId)
    {
        // Data validation
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

        // Verify that the olympiad_area relationship exists
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

        // Verify that the level_grade exists for this level and olympiad_area
        $levelGrade = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId)
            ->first();

        if (!$levelGrade) {
            $data = [
                'message' => 'Level not found for this olympiad area',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Find the specific olympiad_area_phase relationship
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

        // Find the specific record for this level
        $olympiadAreaPhaseLevelGrade = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
            ->where('level_grade_id', $levelGrade->id)
            ->first();

        if (!$olympiadAreaPhaseLevelGrade) {
            $data = [
                'message' => 'Phase level grade configuration not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Update the status
        $olympiadAreaPhaseLevelGrade->status = $request->status;

        if (!$olympiadAreaPhaseLevelGrade->save()) {
            $data = [
                'message' => 'Error updating phase status',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        // If the phase is marked as "Terminada", process classifications
        if ($request->status === 'Terminada') {
            $this->processPhaseClassifications($olympiadAreaPhaseLevelGrade, $levelGrade);
        }

        // Load the phase information for the response
        $olympiadAreaPhase->load('phase');

        $data = [
            'message' => 'Phase status updated successfully',
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'level_id' => $levelId,
            'phase_id' => $olympiadAreaPhase->phase_id,
            'phase_name' => $olympiadAreaPhase->phase->name,
            'status' => $olympiadAreaPhaseLevelGrade->status,
            'status_code' => 200
        ];

        return response()->json($data, 200);
    }

    public function endorsePhase(string $olympiadId, string $areaId, string $levelId, string $phaseId)
    {
        // Verify that the olympiad_area relationship exists
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('area_id', $areaId)
            ->first();

        if (!$olympiadArea) {
            return response()->json([
                'message' => 'Olympiad area relationship not found',
                'status' => 404
            ], 404);
        }

        // Verify that the level_grade exists for this level and olympiad_area
        $levelGrade = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId)
            ->first();

        if (!$levelGrade) {
            return response()->json([
                'message' => 'Level not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Verify that the phase exists for this olympiad area
        $currentOlympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $phaseId)
            ->first();

        if (!$currentOlympiadAreaPhase) {
            return response()->json([
                'message' => 'Phase not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Verify the specific status for this level
        $currentOaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
            ->where('level_grade_id', $levelGrade->id)
            ->first();

        if (!$currentOaplg) {
            return response()->json([
                'message' => 'Phase level grade configuration not found',
                'status' => 404
            ], 404);
        }

        // Verify that the phase is not already completed
        if ($currentOaplg->status === 'Terminada') {
            return response()->json([
                'message' => 'Phase is already completed for this level',
                'status' => 400
            ], 400);
        }

        // Mark the current phase as "Terminada" using updatePhaseStatus
        $updateRequest = new Request([
            'phase_id' => $phaseId,
            'status' => 'Terminada'
        ]);

        $updateResponse = $this->updatePhaseStatus($updateRequest, $olympiadId, $areaId, $levelId);

        // Check if the update was successful
        if ($updateResponse->getStatusCode() !== 200) {
            return $updateResponse;
        }

        $responseData = [
            'message' => 'Phase endorsed successfully',
            'current_phase' => [
                'phase_id' => $phaseId,
                'status' => 'Terminada'
            ],
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'level_id' => $levelId,
            'status' => 200
        ];

        // Check if it's not the final phase and activate the next one
        if (!$this->isFinalPhase($currentOaplg, $levelGrade)) {
            $nextOaplg = $this->getNextPhase($currentOaplg, $levelGrade);

            if ($nextOaplg) {
                // Activate the next phase
                $nextUpdateRequest = new Request([
                    'phase_id' => $nextOaplg->olympiadAreaPhase->phase_id,
                    'status' => 'Activa'
                ]);

                $nextUpdateResponse = $this->updatePhaseStatus($nextUpdateRequest, $olympiadId, $areaId, $levelId);

                if ($nextUpdateResponse->getStatusCode() === 200) {
                    $nextOaplg->load('olympiadAreaPhase.phase');
                    $responseData['next_phase'] = [
                        'phase_id' => $nextOaplg->olympiadAreaPhase->phase_id,
                        'phase_name' => $nextOaplg->olympiadAreaPhase->phase->name,
                        'status' => 'Activa'
                    ];
                    $responseData['message'] = 'Phase endorsed successfully and next phase activated';
                }
            }
        } else {
            $responseData['message'] = 'Phase endorsed successfully - final phase completed';
            $responseData['is_final_phase'] = true;
        }

        return response()->json($responseData, 200);
    }

    /**
     * Process automatic classification when a phase is marked as "Terminada"
     */
    private function processPhaseClassifications($olympiadAreaPhaseLevelGrade, $levelGrade)
    {
        // Get all evaluations from this phase first (without level filter)
        $allEvaluations = Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhaseLevelGrade->olympiad_area_phase_id)
            ->whereNotNull('score')
            ->with('registration.contestant')
            ->get();

        // Filter by level using level_id instead of specific level_grade_id
        $evaluations = $allEvaluations->filter(function ($evaluation) use ($levelGrade) {
            // Check if the competitor has any level_grade with the same level_id and olympiad_area_id
            return DB::table('contestant_level_grades as clg')
                ->join('level_grades as lg', 'clg.level_grade_id', '=', 'lg.id')
                ->where('clg.contestant_id', $evaluation->registration->contestant_id)
                ->where('lg.level_id', $levelGrade->level_id)
                ->where('lg.olympiad_area_id', $levelGrade->olympiad_area_id)
                ->exists();
        });

        // Check if this is the final phase for this level
        $isFinalPhase = $this->isFinalPhase($olympiadAreaPhaseLevelGrade, $levelGrade);

        // Get the next phase if it's not the final one
        $nextOaplg = null;
        if (!$isFinalPhase) {
            $nextOaplg = $this->getNextPhase($olympiadAreaPhaseLevelGrade, $levelGrade);
        }

        foreach ($evaluations as $evaluation) {
            // Get score_cut for this specific evaluation (already level-aware)
            $scoreCut = $this->getScoreCut($olympiadAreaPhaseLevelGrade, $evaluation);

            if ($scoreCut !== null) {
                // Classify based on score_cut
                if ($evaluation->score >= $scoreCut) {
                    $evaluation->classification_status = 'clasificado';

                    // If it's not the final phase and the competitor qualifies, create record for next phase
                    if (!$isFinalPhase && $nextOaplg) {
                        $this->createNextPhaseEvaluation($evaluation, $nextOaplg->olympiadAreaPhase);
                    }
                } else {
                    $evaluation->classification_status = 'no_clasificado';
                }
            }
        }

        // If it's the final phase, assign medals by level
        if ($isFinalPhase) {
            $this->assignMedals($evaluations);
        }

        // Save all evaluations
        foreach ($evaluations as $evaluation) {
            $evaluation->save();
        }
    }

    /**
     * Get the score cut for a specific evaluation
     */
    private function getScoreCut($olympiadAreaPhaseLevelGrade, $evaluation)
    {
        // The score_cut is directly in the olympiad_area_phase_level_grades record
        return $olympiadAreaPhaseLevelGrade->score_cut;
    }

    /**
     * Check if the current phase is the final phase for this level
     */
    private function isFinalPhase($olympiadAreaPhaseLevelGrade, $levelGrade)
    {
        // Get the maximum order of phases for this specific level
        $maxOrder = OlympiadAreaPhaseLevelGrade::where('level_grade_id', $levelGrade->id)
            ->join('olympiad_area_phases as oap', 'olympiad_area_phase_level_grades.olympiad_area_phase_id', '=', 'oap.id')
            ->join('phases', 'oap.phase_id', '=', 'phases.id')
            ->max('phases.order');

        $currentPhase = $olympiadAreaPhaseLevelGrade->olympiadAreaPhase->load('phase');

        return $currentPhase->phase->order == $maxOrder;
    }

    /**
     * Assign medals for the final phase
     */
    private function assignMedals($evaluations)
    {
        // Sort by score descending (only classified competitors)
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
     * Get the next phase for this level
     */
    private function getNextPhase($olympiadAreaPhaseLevelGrade, $levelGrade)
    {
        $currentPhase = $olympiadAreaPhaseLevelGrade->olympiadAreaPhase->load('phase');
        $nextPhaseOrder = $currentPhase->phase->order + 1;

        // Find the next phase in order
        $nextPhase = Phase::where('order', $nextPhaseOrder)->first();

        if (!$nextPhase) {
            return null;
        }

        // Find the corresponding OlympiadAreaPhase for the next phase
        $nextOlympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $levelGrade->olympiad_area_id)
            ->where('phase_id', $nextPhase->id)
            ->first();

        if (!$nextOlympiadAreaPhase) {
            return null;
        }

        // Find the OlympiadAreaPhaseLevelGrade for this level in the next phase
        $nextOaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $nextOlympiadAreaPhase->id)
            ->where('level_grade_id', $levelGrade->id)
            ->first();

        return $nextOaplg;
    }

    /**
     * Create evaluation record for next phase when competitor qualifies
     */
    private function createNextPhaseEvaluation($currentEvaluation, $nextOlympiadAreaPhase)
    {
        // Check if a record already exists for this competitor in the next phase
        $existingEvaluation = Evaluation::where('registration_id', $currentEvaluation->registration_id)
            ->where('olympiad_area_phase_id', $nextOlympiadAreaPhase->id)
            ->first();

        // Only create if it doesn't exist
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
