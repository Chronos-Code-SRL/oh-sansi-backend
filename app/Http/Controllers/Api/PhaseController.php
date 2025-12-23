<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\Models\Phase;
use App\Models\Olympiad;
use App\Models\OlympiadArea;
use App\Models\OlympiadAreaPhase;
use App\Models\Evaluation;
use App\Models\OlympiadAreaPhaseLevelGrade;
use App\Models\LevelGrade;
use App\Models\Level;
use App\Models\OlympiadAreaMedal;

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

    public function getSinglePhaseStatus(string $olympiadId, string $areaId, string $levelId, string $phaseId)
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
        $olympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $phaseId)
            ->with('phase')
            ->first();

        if (!$olympiadAreaPhase) {
            return response()->json([
                'message' => 'Phase not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Get the specific phase status for this level
        $oaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
            ->where('level_grade_id', $levelGrade->id)
            ->first();

        if (!$oaplg) {
            return response()->json([
                'message' => 'Phase status configuration not found for this level',
                'error' => 'Please configure this phase for the specified level first.',
                'status' => 404
            ], 404);
        }

        $phaseStatus = [
            'phase_id' => (int)$phaseId,
            'phase_name' => $olympiadAreaPhase->phase->name,
            'phase_order' => $olympiadAreaPhase->phase->order,
            'status' => $oaplg->status,
            'score_cut' => $oaplg->score_cut,
            'max_score' => $oaplg->max_score
        ];

        return response()->json([
            'olympiad_id' => (int)$olympiadId,
            'area_id' => (int)$areaId,
            'level_id' => (int)$levelId,
            'phase_status' => $phaseStatus,
            'status' => 200
        ], 200);
    }

    public function updatePhaseStatus(Request $request, string $olympiadId, string $areaId, string $levelId, ?string $gradeId = null)
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

        // Verify that level_grades exist for this level and olympiad_area
        $levelGradeQuery = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId);

        if ($gradeId !== null) {
            $levelGradeQuery->where('grade_id', $gradeId);
        }

        $levelGrades = $levelGradeQuery->get(); // Changed from first() to get()

        if ($levelGrades->isEmpty()) {
            $data = [
                'message' => $gradeId ? 'Grade not found for this level and olympiad area' : 'Level not found for this olympiad area',
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

        // Process all level grades
        $updatedCount = 0;
        $firstLevelGrade = $levelGrades->first(); // Keep for response compatibility

        foreach ($levelGrades as $levelGrade) {
            // Find the specific record for this level grade
            $olympiadAreaPhaseLevelGrade = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                ->where('level_grade_id', $levelGrade->id)
                ->first();

            if (!$olympiadAreaPhaseLevelGrade) {
                continue; // Skip if configuration doesn't exist for this grade
            }

            // Update the status
            $olympiadAreaPhaseLevelGrade->status = $request->status;

            if ($olympiadAreaPhaseLevelGrade->save()) {
                $updatedCount++;

                // If the phase is marked as "Terminada", process classifications
                if ($request->status === 'Terminada') {
                    $this->processPhaseClassifications($olympiadAreaPhaseLevelGrade, $levelGrade);
                }
            }
        }

        if ($updatedCount === 0) {
            $data = [
                'message' => 'Error updating phase status - no grades were updated',
                'status' => 500
            ];
            return response()->json($data, 500);
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
            'status' => $request->status,
            'status_code' => 200
        ];

        return response()->json($data, 200);
    }

    public function endorsePhase(Request $request, string $olympiadId, string $areaId, string $levelId, string $phaseId)
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

        // Verify that level_grades exist for this level and olympiad_area
        $levelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId)
            ->get();

        if ($levelGrades->isEmpty()) {
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

        // Check if this is the final phase and validate medal assignment
        $firstLevelGrade = $levelGrades->first();
        $firstOaplgForCheck = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
            ->where('level_grade_id', $firstLevelGrade->id)
            ->first();

        if ($firstOaplgForCheck && $this->isFinalPhase($firstOaplgForCheck, $firstLevelGrade)) {
            $validation = $this->validateMedalAssignment($olympiadArea->id, $currentOlympiadAreaPhase->id, $firstLevelGrade);

            // If there are errors (ties that exceed medal availability), don't allow endorsement
            if (!empty($validation['errors'])) {
                return response()->json([
                    'message' => 'Cannot endorse phase due to ties exceeding medal availability',
                    'can_endorse' => false,
                    'errors' => $validation['errors'],
                    'status' => 400
                ], 400);
            }

            // If there are warnings (ties within medal availability) and force_endorse is not true
            if (!empty($validation['warnings']) && !$request->input('force_endorse', false)) {
                return response()->json([
                    'message' => 'Phase has ties in medal positions. Review and confirm to proceed.',
                    'can_endorse' => true,
                    'warnings' => $validation['warnings'],
                    'requires_confirmation' => true,
                    'status' => 409
                ], 409);
            }
        }

        $processedGrades = [];
        $failedGrades = [];
        $firstLevelGrade = $levelGrades->first(); // For compatibility with existing logic

        // Process each grade in this level
        foreach ($levelGrades as $levelGrade) {
            // Verify the specific status for this level-grade
            $currentOaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
                ->where('level_grade_id', $levelGrade->id)
                ->first();

            if (!$currentOaplg) {
                // Try to create the missing configuration with default values
                $olympiad = Olympiad::find($olympiadId);
                if ($olympiad && ($olympiad->default_score_cut !== null || $olympiad->default_max_score !== null)) {
                    $currentOaplg = OlympiadAreaPhaseLevelGrade::create([
                        'olympiad_area_phase_id' => $currentOlympiadAreaPhase->id,
                        'level_grade_id' => $levelGrade->id,
                        'score_cut' => $olympiad->default_score_cut ?? 0,
                        'max_score' => $olympiad->default_max_score ?? null,
                        'status' => 'Sin empezar'
                    ]);
                }

                if (!$currentOaplg) {
                    $failedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
                    continue;
                }
            }

            // Skip if already completed
            if ($currentOaplg->status === 'Terminada') {
                continue;
            }

            // Mark this grade's phase as "Terminada" using updatePhaseStatus
            $updateRequest = new Request([
                'phase_id' => $phaseId,
                'status' => 'Terminada'
            ]);

            // Create a temporary level ID for this specific grade to maintain updatePhaseStatus compatibility
            $gradeResponse = $this->updatePhaseStatus($updateRequest, $olympiadId, $areaId, $levelId, $levelGrade->grade_id);

            if ($gradeResponse->getStatusCode() === 200) {
                $processedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
            } else {
                $failedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
            }
        }

        // Return error if no grades were processed
        if (empty($processedGrades)) {
            return response()->json([
                'message' => 'No grades could be endorsed for this level',
                'failed_grades' => $failedGrades,
                'status' => 400
            ], 400);
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
            'processed_grades' => $processedGrades,
            'status' => 200
        ];

        if (!empty($failedGrades)) {
            $responseData['failed_grades'] = $failedGrades;
            $responseData['message'] = 'Phase endorsed with some failures';
        }

        // Use the first level-grade for compatibility with existing next phase logic
        $firstOaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
            ->where('level_grade_id', $firstLevelGrade->id)
            ->first();

        // Check if it's not the final phase and activate the next one
        if ($firstOaplg && !$this->isFinalPhase($firstOaplg, $firstLevelGrade)) {
            $nextOaplg = $this->getNextPhase($firstOaplg, $firstLevelGrade);

            if ($nextOaplg) {
                // Activate the next phase for all grades in this level
                $nextPhaseId = $nextOaplg->olympiadAreaPhase->phase_id;
                $activatedGrades = [];

                foreach ($levelGrades as $levelGrade) {
                    $nextUpdateRequest = new Request([
                        'phase_id' => $nextPhaseId,
                        'status' => 'Activa'
                    ]);

                    $nextUpdateResponse = $this->updatePhaseStatus($nextUpdateRequest, $olympiadId, $areaId, $levelId, $levelGrade->grade_id);

                    if ($nextUpdateResponse->getStatusCode() === 200) {
                        $activatedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
                    }
                }

                if (!empty($activatedGrades)) {
                    $nextOaplg->load('olympiadAreaPhase.phase');
                    $responseData['next_phase'] = [
                        'phase_id' => $nextPhaseId,
                        'phase_name' => $nextOaplg->olympiadAreaPhase->phase->name,
                        'status' => 'Activa',
                        'activated_grades' => $activatedGrades
                    ];
                    $responseData['message'] = 'Phase endorsed successfully and next phase activated';
                }
            }
        } else {
            $responseData['message'] = 'Phase endorsed successfully - final phase completed for all grades';
            $responseData['is_final_phase'] = true;
        }

        return response()->json($responseData, 200);
    }

    public function endorsePhaseAllLevels(Request $request, string $olympiadId, string $areaId, string $phaseId)
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

        // Verify that the phase exists for this olympiad area
        $olympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $phaseId)
            ->first();

        if (!$olympiadAreaPhase) {
            return response()->json([
                'message' => 'Phase not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Get all unique levels for this olympiad area (group by level_id to avoid duplicates)
        $levelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->with('level')
            ->get()
            ->groupBy('level_id')
            ->map(function ($group) {
                return $group->first(); // Take only the first LevelGrade for each level_id
            });

        if ($levelGrades->isEmpty()) {
            return response()->json([
                'message' => 'No levels found for this olympiad area',
                'status' => 404
            ], 404);
        }

        $results = [];
        $errors = [];
        $successCount = 0;
        $processedLevelIds = []; // Track which levels we've already processed

        foreach ($levelGrades as $levelGrade) {
            // Skip if we've already processed this level_id
            if (in_array($levelGrade->level_id, $processedLevelIds)) {
                continue;
            }

            $processedLevelIds[] = $levelGrade->level_id;

            try {
                // Use the existing endorsePhase method for each unique level
                $endorseResponse = $this->endorsePhase($request, $olympiadId, $areaId, $levelGrade->level_id, $phaseId);

                if ($endorseResponse->getStatusCode() === 200) {
                    $responseData = json_decode($endorseResponse->getContent(), true);
                    $results[] = [
                        'level_id' => $levelGrade->level_id,
                        'level_name' => $levelGrade->level->name ?? 'Unknown',
                        'status' => 'success',
                        'current_phase' => $responseData['current_phase'] ?? null,
                        'next_phase' => $responseData['next_phase'] ?? null,
                        'is_final_phase' => $responseData['is_final_phase'] ?? false
                    ];
                    $successCount++;
                } else {
                    $errorData = json_decode($endorseResponse->getContent(), true);
                    $errors[] = [
                        'level_id' => $levelGrade->level_id,
                        'level_name' => $levelGrade->level->name ?? 'Unknown',
                        'error' => $errorData['message'] ?? 'Unknown error occurred'
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'level_id' => $levelGrade->level_id,
                    'level_name' => $levelGrade->level->name ?? 'Unknown',
                    'error' => 'Unexpected error: ' . $e->getMessage()
                ];
            }
        }

        // Prepare response based on results
        $totalLevels = $levelGrades->count();
        $errorCount = count($errors);

        $responseData = [
            'message' => $successCount === $totalLevels
                ? 'All levels endorsed successfully'
                : ($successCount > 0
                    ? "Phase endorsed for {$successCount} of {$totalLevels} levels"
                    : 'No levels could be endorsed'),
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'phase_id' => $phaseId,
            'total_levels' => $totalLevels,
            'successful_endorsements' => $successCount,
            'failed_endorsements' => $errorCount,
            'results' => $results,
            'errors' => $errors,
            'status' => $successCount > 0 ? 200 : 400
        ];

        return response()->json($responseData, $successCount > 0 ? 200 : 400);
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

            if ($evaluation->classification_status==='descalificado') {
                // Skip disqualified competitors
                continue;
            }
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
            $this->assignMedals($evaluations, $levelGrade, $scoreCut);
        }

        // Save all evaluations
        foreach ($evaluations as $evaluation) {
            $evaluation->save();
        }
    }

    /**
     * Validate medal assignment for final phase
     * Checks for ties and whether they can be accommodated within medal availability
     */
    private function validateMedalAssignment($olympiadAreaId, $olympiadAreaPhaseId, $levelGrade)
    {
        // Get medal configuration for this olympiad area
        $medalConfig = OlympiadAreaMedal::where('olympiad_area_id', $olympiadAreaId)->first();

        if (!$medalConfig) {
            return [
                'can_endorse' => false,
                'errors' => [
                    [
                        'type' => 'missing_configuration',
                        'message' => 'Medal configuration not found for this olympiad area. Please configure medals before endorsing the final phase.'
                    ]
                ],
                'warnings' => []
            ];
        }

        // Get the olympiad_area_phase_level_grade to find score_cut
        $oaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhaseId)
            ->where('level_grade_id', $levelGrade->id)
            ->first();

        if (!$oaplg) {
            return [
                'can_endorse' => true,
                'errors' => [],
                'warnings' => []
            ];
        }

        $scoreCut = $oaplg->score_cut;

        // Get all evaluations from this phase for this level
        $allEvaluations = Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhaseId)
            ->whereNotNull('score')
            ->with('registration.contestant')
            ->get();

        // Filter by level
        $evaluations = $allEvaluations->filter(function ($evaluation) use ($levelGrade) {
            return DB::table('contestant_level_grades as clg')
                ->join('level_grades as lg', 'clg.level_grade_id', '=', 'lg.id')
                ->where('clg.contestant_id', $evaluation->registration->contestant_id)
                ->where('lg.level_id', $levelGrade->level_id)
                ->where('lg.olympiad_area_id', $levelGrade->olympiad_area_id)
                ->exists();
        });

        // Filter classified competitors with score >= score_cut
        $classifiedEvaluations = $evaluations
            ->where('classification_status', 'clasificado')
            ->filter(function ($evaluation) use ($scoreCut) {
                return $evaluation->score >= $scoreCut;
            })
            ->sortByDesc('score')
            ->values();

        if ($classifiedEvaluations->isEmpty()) {
            // No classified evaluations, can endorse without issues
            return [
                'can_endorse' => true,
                'errors' => [],
                'warnings' => []
            ];
        }

        // Group by score to detect ties
        $scoreGroups = $classifiedEvaluations->groupBy('score')->sortKeysDesc();

        $errors = [];
        $warnings = [];

        // Calculate medal ranges (cumulative person positions)
        $medalRanges = [
            ['type' => 'Oro', 'start' => 1, 'end' => $medalConfig->gold],
            ['type' => 'Plata', 'start' => $medalConfig->gold + 1, 'end' => $medalConfig->gold + $medalConfig->silver],
            ['type' => 'Bronce', 'start' => $medalConfig->gold + $medalConfig->silver + 1, 'end' => $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze],
            ['type' => 'Mención honorífica', 'start' => $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze + 1, 'end' => $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze + $medalConfig->honorable_mention]
        ];

        $currentPosition = 1; // Cumulative position (1-indexed)
        $totalMedalPositions = $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze + $medalConfig->honorable_mention;

        // Validate each score group by cumulative person positions
        foreach ($scoreGroups as $score => $group) {
            $groupCount = $group->count();
            $positionStart = $currentPosition;
            $positionEnd = $currentPosition + $groupCount - 1;

            // Determine which medal ranges this group affects
            $affectedMedals = [];
            foreach ($medalRanges as $range) {
                // Check if this group overlaps with this medal range
                if ($positionStart <= $range['end'] && $positionEnd >= $range['start']) {
                    $affectedMedals[] = [
                        'type' => $range['type'],
                        'start' => $range['start'],
                        'end' => $range['end']
                    ];
                }
            }

            // Check if group extends beyond all medal positions
            $extendsBeyondMedals = ($positionEnd > $totalMedalPositions);

            // Validate based on how many medal categories are affected
            if (count($affectedMedals) > 1) {
                // ERROR: Tie crosses multiple medal category boundaries
                $medalTypes = array_column($affectedMedals, 'type');
                $errors[] = [
                    'medal' => implode(', ', $medalTypes),
                    'score' => $score,
                    'count' => $groupCount,
                    'available' => $affectedMedals[0]['end'] - $positionStart + 1,
                    'position' => $positionStart,
                    'position_start' => $positionStart,
                    'position_end' => $positionEnd,
                    'medals_affected' => $medalTypes,
                    'message' => "{$groupCount} competitors tied with score {$score} span positions {$positionStart}-{$positionEnd}, crossing multiple medal categories: " . implode(', ', $medalTypes) . ". Cannot endorse until tie is resolved."
                ];
            } elseif (count($affectedMedals) === 1 && $extendsBeyondMedals) {
                // ERROR: Tie crosses from a medal category into "no medal" territory
                $medal = $affectedMedals[0];
                $availableInMedal = $medal['end'] - $positionStart + 1;
                $errors[] = [
                    'medal' => $medal['type'],
                    'score' => $score,
                    'count' => $groupCount,
                    'available' => $availableInMedal,
                    'position' => $positionStart,
                    'position_start' => $positionStart,
                    'position_end' => $positionEnd,
                    'medals_affected' => [$medal['type']],
                    'message' => "{$groupCount} competitors tied with score {$score} in positions {$positionStart}-{$positionEnd}, but only {$availableInMedal} medal(s) available in {$medal['type']}. Cannot endorse until tie is resolved."
                ];
            } elseif (count($affectedMedals) === 1 && $groupCount > 1) {
                // WARNING: Tie exists but all fit within one category
                $medal = $affectedMedals[0];
                $warnings[] = [
                    'medal' => $medal['type'],
                    'score' => $score,
                    'count' => $groupCount,
                    'available' => $medal['end'] - $medal['start'] + 1,
                    'message' => "{$groupCount} competitors tied with score {$score} for {$medal['type']}. All can be awarded within available slots."
                ];
            } elseif (count($affectedMedals) === 0 && $groupCount > 1) {
                // WARNING: Tie exists but beyond all medal positions (no medals affected)
                $warnings[] = [
                    'medal' => 'Sin medalla',
                    'score' => $score,
                    'count' => $groupCount,
                    'available' => 0,
                    'message' => "{$groupCount} competitors tied with score {$score} beyond medal positions."
                ];
            }

            // Advance position counter
            $currentPosition = $positionEnd + 1;
        }

        return [
            'can_endorse' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
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
     * Assign medals for the final phase based on medal configuration
     */
    private function assignMedals($evaluations, $levelGrade, $scoreCut)
    {
        // Get medal configuration for this olympiad area
        $medalConfig = OlympiadAreaMedal::where('olympiad_area_id', $levelGrade->olympiad_area_id)->first();

        if (!$medalConfig) {
            // If no medal configuration exists, don't assign any medals
            Log::warning("No medal configuration found for olympiad_area_id: {$levelGrade->olympiad_area_id}");
            return;
        }

        // Filter classified competitors with score >= score_cut, sorted by score descending
        $classifiedEvaluations = $evaluations
            ->where('classification_status', 'clasificado')
            ->filter(function ($evaluation) use ($scoreCut) {
                return $evaluation->score >= $scoreCut;
            })
            ->sortByDesc('score')
            ->values();

        if ($classifiedEvaluations->isEmpty()) {
            return;
        }

        // Group evaluations by unique scores to detect ties
        $scoreGroups = $classifiedEvaluations->groupBy('score')->sortKeysDesc();

        // Calculate medal ranges (cumulative person positions)
        $medalRanges = [
            ['type' => 'Oro', 'start' => 1, 'end' => $medalConfig->gold],
            ['type' => 'Plata', 'start' => $medalConfig->gold + 1, 'end' => $medalConfig->gold + $medalConfig->silver],
            ['type' => 'Bronce', 'start' => $medalConfig->gold + $medalConfig->silver + 1, 'end' => $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze],
            ['type' => 'Mención honorífica', 'start' => $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze + 1, 'end' => $medalConfig->gold + $medalConfig->silver + $medalConfig->bronze + $medalConfig->honorable_mention]
        ];

        $currentPosition = 1; // Cumulative position (1-indexed)

        // Assign medals by cumulative person positions
        foreach ($scoreGroups as $score => $group) {
            $groupCount = $group->count();
            $positionStart = $currentPosition;
            $positionEnd = $currentPosition + $groupCount - 1;

            // Determine which medal ranges this group affects
            $affectedMedals = [];
            foreach ($medalRanges as $range) {
                if ($positionStart <= $range['end'] && $positionEnd >= $range['start']) {
                    $affectedMedals[] = $range['type'];
                }
            }

            // Only assign if the entire group fits within ONE medal category
            if (count($affectedMedals) === 1) {
                $targetMedal = $affectedMedals[0];
                foreach ($group as $evaluation) {
                    $evaluation->classification_place = $targetMedal;
                }
            } elseif (count($affectedMedals) > 1) {
                // This shouldn't happen if validation passed, but log it
                Log::warning("Medal assignment mismatch: {$groupCount} competitors tied with score {$score} at positions {$positionStart}-{$positionEnd} span multiple medal categories: " . implode(', ', $affectedMedals));
            }
            // If count === 0, the group is beyond all medal positions, so classification_place remains null

            // Advance position counter
            $currentPosition = $positionEnd + 1;
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

    /**
     * Get score cut and max score for a specific olympiad area phase level
     */
    public function getPhaseScoresByLevel($olympiadId, $areaId, $phaseId, $levelId)
    {
        try {
            // Validar parámetros
            if (!is_numeric($olympiadId) || $olympiadId <= 0) {
                return response()->json([
                    'message' => 'Invalid olympiad ID',
                    'error' => 'Olympiad ID must be a positive integer',
                    'status' => 400
                ], 400);
            }

            if (!is_numeric($areaId) || $areaId <= 0) {
                return response()->json([
                    'message' => 'Invalid area ID',
                    'error' => 'Area ID must be a positive integer',
                    'status' => 400
                ], 400);
            }

            if (!is_numeric($phaseId) || $phaseId <= 0) {
                return response()->json([
                    'message' => 'Invalid phase ID',
                    'error' => 'Phase ID must be a positive integer',
                    'status' => 400
                ], 400);
            }

            if (!is_numeric($levelId) || $levelId <= 0) {
                return response()->json([
                    'message' => 'Invalid level ID',
                    'error' => 'Level ID must be a positive integer',
                    'status' => 400
                ], 400);
            }

            // Use simplified SQL query that returns only one result per level
            $result = DB::selectOne("
                SELECT oaplg.score_cut, oaplg.max_score
                FROM olympiad_area_phase_level_grades oaplg
                JOIN level_grades lg ON oaplg.level_grade_id = lg.id
                WHERE oaplg.olympiad_area_phase_id = (
                    SELECT id FROM olympiad_area_phases
                    WHERE phase_id = ? AND olympiad_area_id = (
                        SELECT id FROM olympiad_areas
                        WHERE olympiad_id = ? AND area_id = ?
                    )
                )
                AND oaplg.level_grade_id IN (
                    SELECT id FROM level_grades
                    WHERE level_id = ? AND olympiad_area_id = (
                        SELECT id FROM olympiad_areas
                        WHERE olympiad_id = ? AND area_id = ?
                    )
                )
                LIMIT 1
            ", [$phaseId, $olympiadId, $areaId, $levelId, $olympiadId, $areaId]);

            if (!$result) {
                return response()->json([
                    'message' => 'No scores found for the specified criteria',
                    'error' => "No scores configured for olympiad {$olympiadId}, area {$areaId}, phase {$phaseId}, level {$levelId}",
                    'status' => 404
                ], 404);
            }

            return response()->json([
                'score_cut' => $result->score_cut,
                'max_score' => $result->max_score,
                'olympiad_id' => (int)$olympiadId,
                'area_id' => (int)$areaId,
                'phase_id' => (int)$phaseId,
                'level_id' => (int)$levelId,
                'status' => 200
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in getPhaseScoresByLevel: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'phase_id' => $phaseId,
                'level_id' => $levelId
            ]);
            return response()->json([
                'message' => 'Database error occurred',
                'error' => 'Failed to retrieve scores',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in getPhaseScoresByLevel: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'phase_id' => $phaseId,
                'level_id' => $levelId
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => 'Internal server error',
                'status' => 500
            ], 500);
        }
    }

    public function lastPhaseStatus(string $olympiadId, string $areaId, string $levelId)
    {
        $lastPhaseId = DB::table('olympiad_area_phase_level_grades AS oapl')
            ->join('olympiad_area_phases AS oap', 'oap.id', '=', 'oapl.olympiad_area_phase_id')
            ->join('phases AS p', 'p.id', '=', 'oap.phase_id')
            ->join('level_grades AS lg', 'lg.id', '=', 'oapl.level_grade_id')
            ->join('olympiad_areas AS oa', 'oa.id', '=', 'lg.olympiad_area_id')
            ->where('oa.olympiad_id', $olympiadId)
            ->where('oa.area_id', $areaId)
            ->where('lg.level_id', $levelId)
            ->orderByDesc('p.order')
            ->select('p.order', 'oapl.status')
            ->first();

        if ($lastPhaseId->status !== 'Terminada') {
            return response()->json([
                'message' => 'Last phase not endorsed',
                'status' => 403
            ], 403);
        }

        return response()->json([
            'message' => 'Final approved phase',
            'status' => 200
        ], 200);
    }

    // ==================== NEW V2 ENDORSEMENT SYSTEM ====================
    // Sequential medal assignment based on score ranking

    /**
     * Endorse phase using V2 logic (sequential medal assignment)
     */
    public function endorsePhaseV2(Request $request, string $olympiadId, string $areaId, string $levelId, string $phaseId)
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

        // Verify that level_grades exist for this level and olympiad_area
        $levelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId)
            ->get();

        if ($levelGrades->isEmpty()) {
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

        // Check if this is the final phase and validate medal assignment using V2 logic
        $firstLevelGrade = $levelGrades->first();
        $firstOaplgForCheck = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
            ->where('level_grade_id', $firstLevelGrade->id)
            ->first();

        if ($firstOaplgForCheck && $this->isFinalPhase($firstOaplgForCheck, $firstLevelGrade)) {
            $validation = $this->validateMedalAssignmentV2($olympiadArea->id, $currentOlympiadAreaPhase->id, $firstLevelGrade);

            // If there are errors (ties that need manual resolution), don't allow endorsement
            if (!empty($validation['ties_requiring_resolution'])) {
                return response()->json([
                    'message' => 'Cannot endorse phase due to ties that exceed medal availability. Manual adjustment required.',
                    'can_endorse' => false,
                    'ties_requiring_resolution' => $validation['ties_requiring_resolution'],
                    'status' => 409
                ], 409);
            }

            // If there are configuration errors
            if (!empty($validation['errors'])) {
                return response()->json([
                    'message' => $validation['errors'][0]['message'] ?? 'Configuration error',
                    'can_endorse' => false,
                    'errors' => $validation['errors'],
                    'status' => 400
                ], 400);
            }
        }

        // Continue with normal endorsement process (same as V1)
        $processedGrades = [];
        $failedGrades = [];

        // Process each grade in this level
        foreach ($levelGrades as $levelGrade) {
            $currentOaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
                ->where('level_grade_id', $levelGrade->id)
                ->first();

            if (!$currentOaplg) {
                $olympiad = Olympiad::find($olympiadId);
                if ($olympiad && ($olympiad->default_score_cut !== null || $olympiad->default_max_score !== null)) {
                    $currentOaplg = OlympiadAreaPhaseLevelGrade::create([
                        'olympiad_area_phase_id' => $currentOlympiadAreaPhase->id,
                        'level_grade_id' => $levelGrade->id,
                        'score_cut' => $olympiad->default_score_cut ?? 0,
                        'max_score' => $olympiad->default_max_score ?? null,
                        'status' => 'Sin empezar'
                    ]);
                }

                if (!$currentOaplg) {
                    $failedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
                    continue;
                }
            }

            if ($currentOaplg->status === 'Terminada') {
                continue;
            }

            $updateRequest = new Request([
                'phase_id' => $phaseId,
                'status' => 'Terminada'
            ]);

            $gradeResponse = $this->updatePhaseStatusV2($updateRequest, $olympiadId, $areaId, $levelId, $levelGrade->grade_id);

            if ($gradeResponse->getStatusCode() === 200) {
                $processedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
            } else {
                $failedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
            }
        }

        if (empty($processedGrades)) {
            return response()->json([
                'message' => 'No grades could be endorsed for this level',
                'failed_grades' => $failedGrades,
                'status' => 400
            ], 400);
        }

        $responseData = [
            'message' => 'Phase endorsed successfully (V2)',
            'current_phase' => [
                'phase_id' => $phaseId,
                'status' => 'Terminada'
            ],
            'olympiad_id' => $olympiadId,
            'area_id' => $areaId,
            'level_id' => $levelId,
            'processed_grades' => $processedGrades,
            'status' => 200
        ];

        if (!empty($failedGrades)) {
            $responseData['failed_grades'] = $failedGrades;
            $responseData['message'] = 'Phase endorsed with some failures (V2)';
        }

        $firstOaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $currentOlympiadAreaPhase->id)
            ->where('level_grade_id', $firstLevelGrade->id)
            ->first();

        if ($firstOaplg && !$this->isFinalPhase($firstOaplg, $firstLevelGrade)) {
            $nextOaplg = $this->getNextPhase($firstOaplg, $firstLevelGrade);

            if ($nextOaplg) {
                $nextPhaseId = $nextOaplg->olympiadAreaPhase->phase_id;
                $activatedGrades = [];

                foreach ($levelGrades as $levelGrade) {
                    $nextUpdateRequest = new Request([
                        'phase_id' => $nextPhaseId,
                        'status' => 'Activa'
                    ]);

                    $nextUpdateResponse = $this->updatePhaseStatusV2($nextUpdateRequest, $olympiadId, $areaId, $levelId, $levelGrade->grade_id);

                    if ($nextUpdateResponse->getStatusCode() === 200) {
                        $activatedGrades[] = $levelGrade->grade->name ?? "Grade {$levelGrade->grade_id}";
                    }
                }

                if (!empty($activatedGrades)) {
                    $nextOaplg->load('olympiadAreaPhase.phase');
                    $responseData['next_phase'] = [
                        'phase_id' => $nextPhaseId,
                        'phase_name' => $nextOaplg->olympiadAreaPhase->phase->name,
                        'status' => 'Activa',
                        'activated_grades' => $activatedGrades
                    ];
                    $responseData['message'] = 'Phase endorsed successfully and next phase activated (V2)';
                }
            }
        } else {
            $responseData['message'] = 'Phase endorsed successfully - final phase completed for all grades (V2)';
            $responseData['is_final_phase'] = true;
        }

        return response()->json($responseData, 200);
    }

    /**
     * Update phase status for V2 endorsement system
     */
    private function updatePhaseStatusV2(Request $request, string $olympiadId, string $areaId, string $levelId, ?string $gradeId = null)
    {
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('area_id', $areaId)
            ->first();

        if (!$olympiadArea) {
            return response()->json(['message' => 'Olympiad area relationship not found', 'status' => 404], 404);
        }

        $levelGradeQuery = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId);

        if ($gradeId !== null) {
            $levelGradeQuery->where('grade_id', $gradeId);
        }

        $levelGrades = $levelGradeQuery->get();

        if ($levelGrades->isEmpty()) {
            return response()->json(['message' => 'Level not found', 'status' => 404], 404);
        }

        $olympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $request->phase_id)
            ->first();

        if (!$olympiadAreaPhase) {
            return response()->json(['message' => 'Phase not found', 'status' => 404], 404);
        }

        $updatedCount = 0;

        foreach ($levelGrades as $levelGrade) {
            $olympiadAreaPhaseLevelGrade = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                ->where('level_grade_id', $levelGrade->id)
                ->first();

            if (!$olympiadAreaPhaseLevelGrade) {
                continue;
            }

            $olympiadAreaPhaseLevelGrade->status = $request->status;

            if ($olympiadAreaPhaseLevelGrade->save()) {
                $updatedCount++;

                if ($request->status === 'Terminada') {
                    $this->processPhaseClassificationsV2($olympiadAreaPhaseLevelGrade, $levelGrade);
                }
            }
        }

        if ($updatedCount === 0) {
            return response()->json(['message' => 'Error updating phase status', 'status' => 500], 500);
        }

        return response()->json(['status' => 200], 200);
    }

    /**
     * Process phase classifications using V2 logic (sequential medal assignment)
     */
    private function processPhaseClassificationsV2($olympiadAreaPhaseLevelGrade, $levelGrade)
    {
        $allEvaluations = Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhaseLevelGrade->olympiad_area_phase_id)
            ->whereNotNull('score')
            ->with('registration.contestant')
            ->get();

        $evaluations = $allEvaluations->filter(function ($evaluation) use ($levelGrade) {
            return DB::table('contestant_level_grades as clg')
                ->join('level_grades as lg', 'clg.level_grade_id', '=', 'lg.id')
                ->where('clg.contestant_id', $evaluation->registration->contestant_id)
                ->where('lg.level_id', $levelGrade->level_id)
                ->where('lg.olympiad_area_id', $levelGrade->olympiad_area_id)
                ->exists();
        });

        $isFinalPhase = $this->isFinalPhase($olympiadAreaPhaseLevelGrade, $levelGrade);

        $nextOaplg = null;
        if (!$isFinalPhase) {
            $nextOaplg = $this->getNextPhase($olympiadAreaPhaseLevelGrade, $levelGrade);
        }

        $scoreCut = $this->getScoreCut($olympiadAreaPhaseLevelGrade, null);

        foreach ($evaluations as $evaluation) {
            if ($evaluation->classification_status === 'descalificado') {
                continue;
            }

            if ($scoreCut !== null) {
                if ($evaluation->score >= $scoreCut) {
                    $evaluation->classification_status = 'clasificado';

                    if (!$isFinalPhase && $nextOaplg) {
                        $this->createNextPhaseEvaluation($evaluation, $nextOaplg->olympiadAreaPhase);
                    }
                } else {
                    $evaluation->classification_status = 'no_clasificado';
                }
            }
        }

        if ($isFinalPhase) {
            $this->assignMedalsV2($evaluations, $levelGrade);
        }

        foreach ($evaluations as $evaluation) {
            $evaluation->save();
        }
    }

    /**
     * Validate medal assignment using V2 logic (sequential assignment)
     */
    private function validateMedalAssignmentV2($olympiadAreaId, $olympiadAreaPhaseId, $levelGrade)
    {
        $medalConfig = OlympiadAreaMedal::where('olympiad_area_id', $olympiadAreaId)->first();

        if (!$medalConfig) {
            return [
                'can_endorse' => false,
                'errors' => [
                    [
                        'type' => 'missing_configuration',
                        'message' => 'Medal configuration not found for this olympiad area. Please configure medals before endorsing the final phase.'
                    ]
                ],
                'ties_requiring_resolution' => []
            ];
        }

        $oaplg = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhaseId)
            ->where('level_grade_id', $levelGrade->id)
            ->first();

        if (!$oaplg) {
            return [
                'can_endorse' => true,
                'errors' => [],
                'ties_requiring_resolution' => []
            ];
        }

        $allEvaluations = Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhaseId)
            ->whereNotNull('score')
            ->with('registration.contestant')
            ->get();

        $evaluations = $allEvaluations->filter(function ($evaluation) use ($levelGrade) {
            return DB::table('contestant_level_grades as clg')
                ->join('level_grades as lg', 'clg.level_grade_id', '=', 'lg.id')
                ->where('clg.contestant_id', $evaluation->registration->contestant_id)
                ->where('lg.level_id', $levelGrade->level_id)
                ->where('lg.olympiad_area_id', $levelGrade->olympiad_area_id)
                ->exists();
        });

        // Use minimum_classification_score from medal config instead of score_cut
        $minimumScore = $medalConfig->minimum_classification_score;

        // Filter evaluations: classified, above minimum score, and WITHOUT manual assignment
        $classifiedEvaluations = $evaluations
            ->where('classification_status', 'clasificado')
            ->filter(function ($evaluation) use ($minimumScore) {
                // Exclude evaluations that already have a manual medal assignment
                if (!empty($evaluation->classification_place)) {
                    return false;
                }
                return $evaluation->score >= $minimumScore;
            })
            ->sortByDesc('score')
            ->values();

        if ($classifiedEvaluations->isEmpty()) {
            return [
                'can_endorse' => true,
                'errors' => [],
                'ties_requiring_resolution' => []
            ];
        }

        // Count manually assigned medals to adjust available slots
        $manuallyAssigned = $evaluations
            ->where('classification_status', 'clasificado')
            ->filter(function ($evaluation) {
                return !empty($evaluation->classification_place);
            });

        $manualGoldCount = $manuallyAssigned->where('classification_place', 'Oro')->count();
        $manualSilverCount = $manuallyAssigned->where('classification_place', 'Plata')->count();
        $manualBronzeCount = $manuallyAssigned->where('classification_place', 'Bronce')->count();
        $manualHMCount = $manuallyAssigned->where('classification_place', 'Mención honorífica')->count();

        $tiesRequiringResolution = [];

        // Track current position (only for evaluations without manual assignment)
        $currentPosition = 0;
        $goldLimit = max(0, $medalConfig->gold - $manualGoldCount);
        $silverLimit = max(0, $medalConfig->silver - $manualSilverCount);
        $bronzeLimit = max(0, $medalConfig->bronze - $manualBronzeCount);
        $hmLimit = max(0, $medalConfig->honorable_mention - $manualHMCount);

        // Group by score
        $scoreGroups = $classifiedEvaluations->groupBy('score')->sortKeysDesc();

        foreach ($scoreGroups as $score => $group) {
            $groupCount = $group->count();
            $endPosition = $currentPosition + $groupCount;

            // Determine which medal range this group falls into
            if ($currentPosition < $goldLimit) {
                // This group starts in Gold range
                if ($endPosition > $goldLimit) {
                    // Tie extends beyond gold limit
                    $tiesRequiringResolution[] = [
                        'medal_type' => 'Oro',
                        'score' => $score,
                        'tied_count' => $groupCount,
                        'positions' => [$currentPosition + 1, $endPosition],
                        'available_in_category' => $goldLimit - $currentPosition,
                        'overflow_to' => 'Plata',
                        'evaluations' => $group->map(function ($eval) {
                            $contestant = $eval->registration->contestant ?? null;
                            $fullName = $contestant
                                ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                : 'Unknown';
                            return [
                                'evaluation_id' => $eval->id,
                                'contestant_name' => $fullName ?: 'Unknown',
                                'score' => $eval->score
                            ];
                        })->values()->toArray()
                    ];

                    // CASCADE CHECK: Verify if Silver can absorb the overflow
                    $overflowCount = $endPosition - $goldLimit;

                    if ($overflowCount > $silverLimit) {
                        // Silver will also overflow to Bronze
                        $tiesRequiringResolution[] = [
                            'medal_type' => 'Plata',
                            'score' => $score,
                            'tied_count' => $groupCount,
                            'positions' => [$currentPosition + 1, $endPosition],
                            'available_in_category' => $silverLimit,
                            'overflow_to' => 'Bronce',
                            'cascade_from' => 'Oro',
                            'evaluations' => $group->map(function ($eval) {
                                $contestant = $eval->registration->contestant ?? null;
                                $fullName = $contestant
                                    ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                    : 'Unknown';
                                return [
                                    'evaluation_id' => $eval->id,
                                    'contestant_name' => $fullName ?: 'Unknown',
                                    'score' => $eval->score
                                ];
                            })->values()->toArray()
                        ];

                        // Check if Bronze can absorb the remaining overflow
                        $remainingOverflow = $overflowCount - $silverLimit;
                        if ($remainingOverflow > $bronzeLimit) {
                            $tiesRequiringResolution[] = [
                                'medal_type' => 'Bronce',
                                'score' => $score,
                                'tied_count' => $groupCount,
                                'positions' => [$currentPosition + 1, $endPosition],
                                'available_in_category' => $bronzeLimit,
                                'overflow_to' => 'Mención honorífica',
                                'cascade_from' => 'Plata',
                                'evaluations' => $group->map(function ($eval) {
                                    $contestant = $eval->registration->contestant ?? null;
                                    $fullName = $contestant
                                        ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                        : 'Unknown';
                                    return [
                                        'evaluation_id' => $eval->id,
                                        'contestant_name' => $fullName ?: 'Unknown',
                                        'score' => $eval->score
                                    ];
                                })->values()->toArray()
                            ];

                            // Check if HM can absorb the final overflow
                            $finalOverflow = $remainingOverflow - $bronzeLimit;
                            if ($finalOverflow > $hmLimit) {
                                $tiesRequiringResolution[] = [
                                    'medal_type' => 'Mención honorífica',
                                    'score' => $score,
                                    'tied_count' => $groupCount,
                                    'positions' => [$currentPosition + 1, $endPosition],
                                    'available_in_category' => $hmLimit,
                                    'overflow_to' => 'Sin medalla',
                                    'cascade_from' => 'Bronce',
                                    'evaluations' => $group->map(function ($eval) {
                                        $contestant = $eval->registration->contestant ?? null;
                                        $fullName = $contestant
                                            ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                            : 'Unknown';
                                        return [
                                            'evaluation_id' => $eval->id,
                                            'contestant_name' => $fullName ?: 'Unknown',
                                            'score' => $eval->score
                                        ];
                                    })->values()->toArray()
                                ];
                            }
                        }
                    }
                }
            } elseif ($currentPosition < $goldLimit + $silverLimit) {
                // This group starts in Silver range
                if ($endPosition > $goldLimit + $silverLimit) {
                    $tiesRequiringResolution[] = [
                        'medal_type' => 'Plata',
                        'score' => $score,
                        'tied_count' => $groupCount,
                        'positions' => [$currentPosition + 1, $endPosition],
                        'available_in_category' => ($goldLimit + $silverLimit) - $currentPosition,
                        'overflow_to' => 'Bronce',
                        'evaluations' => $group->map(function ($eval) {
                            $contestant = $eval->registration->contestant ?? null;
                            $fullName = $contestant
                                ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                : 'Unknown';
                            return [
                                'evaluation_id' => $eval->id,
                                'contestant_name' => $fullName ?: 'Unknown',
                                'score' => $eval->score
                            ];
                        })->values()->toArray()
                    ];

                    // CASCADE CHECK: Verify if Bronze can absorb the overflow from Silver
                    $overflowCount = $endPosition - ($goldLimit + $silverLimit);

                    if ($overflowCount > $bronzeLimit) {
                        // Bronze will also overflow to HM
                        $tiesRequiringResolution[] = [
                            'medal_type' => 'Bronce',
                            'score' => $score,
                            'tied_count' => $groupCount,
                            'positions' => [$currentPosition + 1, $endPosition],
                            'available_in_category' => $bronzeLimit,
                            'overflow_to' => 'Mención honorífica',
                            'cascade_from' => 'Plata',
                            'evaluations' => $group->map(function ($eval) {
                                $contestant = $eval->registration->contestant ?? null;
                                $fullName = $contestant
                                    ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                    : 'Unknown';
                                return [
                                    'evaluation_id' => $eval->id,
                                    'contestant_name' => $fullName ?: 'Unknown',
                                    'score' => $eval->score
                                ];
                            })->values()->toArray()
                        ];

                        // Check if HM can absorb the remaining overflow
                        $remainingOverflow = $overflowCount - $bronzeLimit;
                        if ($remainingOverflow > $hmLimit) {
                            $tiesRequiringResolution[] = [
                                'medal_type' => 'Mención honorífica',
                                'score' => $score,
                                'tied_count' => $groupCount,
                                'positions' => [$currentPosition + 1, $endPosition],
                                'available_in_category' => $hmLimit,
                                'overflow_to' => 'Sin medalla',
                                'cascade_from' => 'Bronce',
                                'evaluations' => $group->map(function ($eval) {
                                    $contestant = $eval->registration->contestant ?? null;
                                    $fullName = $contestant
                                        ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                        : 'Unknown';
                                    return [
                                        'evaluation_id' => $eval->id,
                                        'contestant_name' => $fullName ?: 'Unknown',
                                        'score' => $eval->score
                                    ];
                                })->values()->toArray()
                            ];
                        }
                    }
                }
            } elseif ($currentPosition < $goldLimit + $silverLimit + $bronzeLimit) {
                // This group starts in Bronze range
                if ($endPosition > $goldLimit + $silverLimit + $bronzeLimit) {
                    $tiesRequiringResolution[] = [
                        'medal_type' => 'Bronce',
                        'score' => $score,
                        'tied_count' => $groupCount,
                        'positions' => [$currentPosition + 1, $endPosition],
                        'available_in_category' => ($goldLimit + $silverLimit + $bronzeLimit) - $currentPosition,
                        'overflow_to' => 'Mención honorífica',
                        'evaluations' => $group->map(function ($eval) {
                            $contestant = $eval->registration->contestant ?? null;
                            $fullName = $contestant
                                ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                : 'Unknown';
                            return [
                                'evaluation_id' => $eval->id,
                                'contestant_name' => $fullName ?: 'Unknown',
                                'score' => $eval->score
                            ];
                        })->values()->toArray()
                    ];

                    // CASCADE CHECK: Verify if HM can absorb the overflow from Bronze
                    $overflowCount = $endPosition - ($goldLimit + $silverLimit + $bronzeLimit);

                    if ($overflowCount > $hmLimit) {
                        // HM will overflow (no more medals available)
                        $tiesRequiringResolution[] = [
                            'medal_type' => 'Mención honorífica',
                            'score' => $score,
                            'tied_count' => $groupCount,
                            'positions' => [$currentPosition + 1, $endPosition],
                            'available_in_category' => $hmLimit,
                            'overflow_to' => 'Sin medalla',
                            'cascade_from' => 'Bronce',
                            'evaluations' => $group->map(function ($eval) {
                                $contestant = $eval->registration->contestant ?? null;
                                $fullName = $contestant
                                    ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                    : 'Unknown';
                                return [
                                    'evaluation_id' => $eval->id,
                                    'contestant_name' => $fullName ?: 'Unknown',
                                    'score' => $eval->score
                                ];
                            })->values()->toArray()
                        ];
                    }
                }
            } elseif ($currentPosition < $goldLimit + $silverLimit + $bronzeLimit + $hmLimit) {
                // This group starts in HM range
                if ($endPosition > $goldLimit + $silverLimit + $bronzeLimit + $hmLimit) {
                    $tiesRequiringResolution[] = [
                        'medal_type' => 'Mención honorífica',
                        'score' => $score,
                        'tied_count' => $groupCount,
                        'positions' => [$currentPosition + 1, $endPosition],
                        'available_in_category' => ($goldLimit + $silverLimit + $bronzeLimit + $hmLimit) - $currentPosition,
                        'overflow_to' => 'Sin medalla',
                        'evaluations' => $group->map(function ($eval) {
                            $contestant = $eval->registration->contestant ?? null;
                            $fullName = $contestant
                                ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                                : 'Unknown';
                            return [
                                'evaluation_id' => $eval->id,
                                'contestant_name' => $fullName ?: 'Unknown',
                                'score' => $eval->score
                            ];
                        })->values()->toArray()
                    ];
                }
            }

            $currentPosition = $endPosition;
        }

        return [
            'can_endorse' => empty($tiesRequiringResolution),
            'errors' => [],
            'ties_requiring_resolution' => $tiesRequiringResolution
        ];
    }

    /**
     * Assign medals using V2 logic (sequential assignment by score ranking)
     */
    private function assignMedalsV2($evaluations, $levelGrade)
    {
        $medalConfig = OlympiadAreaMedal::where('olympiad_area_id', $levelGrade->olympiad_area_id)->first();

        if (!$medalConfig) {
            Log::warning("No medal configuration found for olympiad_area_id: {$levelGrade->olympiad_area_id}");
            return;
        }

        // Use minimum_classification_score from medal config
        $minimumScore = $medalConfig->minimum_classification_score;

        // Separate evaluations into manual and automatic assignments
        $manuallyAssigned = $evaluations
            ->where('classification_status', 'clasificado')
            ->filter(function ($evaluation) use ($minimumScore) {
                return $evaluation->score >= $minimumScore && !empty($evaluation->classification_place);
            });

        $autoAssignEvaluations = $evaluations
            ->where('classification_status', 'clasificado')
            ->filter(function ($evaluation) use ($minimumScore) {
                return $evaluation->score >= $minimumScore && empty($evaluation->classification_place);
            })
            ->sortByDesc('score')
            ->values();

        if ($autoAssignEvaluations->isEmpty()) {
            // All medals were assigned manually, nothing to do
            return;
        }

        // Count manually assigned medals
        $manualGoldCount = $manuallyAssigned->where('classification_place', 'Oro')->count();
        $manualSilverCount = $manuallyAssigned->where('classification_place', 'Plata')->count();
        $manualBronzeCount = $manuallyAssigned->where('classification_place', 'Bronce')->count();
        $manualHMCount = $manuallyAssigned->where('classification_place', 'Mención honorífica')->count();

        // Calculate remaining slots
        $goldLimit = max(0, $medalConfig->gold - $manualGoldCount);
        $silverLimit = max(0, $medalConfig->silver - $manualSilverCount);
        $bronzeLimit = max(0, $medalConfig->bronze - $manualBronzeCount);
        $hmLimit = max(0, $medalConfig->honorable_mention - $manualHMCount);

        // Assign medals sequentially based on position (only to evaluations without manual assignment)
        foreach ($autoAssignEvaluations as $index => $evaluation) {
            $position = $index; // 0-based index

            if ($position < $goldLimit) {
                $evaluation->classification_place = 'Oro';
            } elseif ($position < $goldLimit + $silverLimit) {
                $evaluation->classification_place = 'Plata';
            } elseif ($position < $goldLimit + $silverLimit + $bronzeLimit) {
                $evaluation->classification_place = 'Bronce';
            } elseif ($position < $goldLimit + $silverLimit + $bronzeLimit + $hmLimit) {
                $evaluation->classification_place = 'Mención honorífica';
            }
            // If beyond all limits, no medal is assigned (classification_place remains null)
        }
    }

    /**
     * Manually adjust medal assignments for tied competitors
     */
    public function manuallyAdjustMedalAssignment(Request $request, string $olympiadId, string $areaId, string $levelId, string $phaseId)
    {
        $validator = Validator::make($request->all(), [
            'adjustments' => 'required|array|min:1',
            'adjustments.*.evaluation_id' => 'required|integer|exists:evaluations,id',
            'adjustments.*.new_medal' => 'required|string|in:Oro,Plata,Bronce,Mención honorífica',
            'adjustments.*.justification' => 'required|string|min:10|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'errors' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        // Verify olympiad area
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('area_id', $areaId)
            ->first();

        if (!$olympiadArea) {
            return response()->json([
                'message' => 'Olympiad area relationship not found',
                'status' => 404
            ], 404);
        }

        // Get medal configuration
        $medalConfig = OlympiadAreaMedal::where('olympiad_area_id', $olympiadArea->id)->first();

        if (!$medalConfig) {
            return response()->json([
                'message' => 'Medal configuration not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Get level grade
        $levelGrade = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
            ->where('level_id', $levelId)
            ->first();

        if (!$levelGrade) {
            return response()->json([
                'message' => 'Level not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Get olympiad area phase
        $olympiadAreaPhase = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
            ->where('phase_id', $phaseId)
            ->first();

        if (!$olympiadAreaPhase) {
            return response()->json([
                'message' => 'Phase not found for this olympiad area',
                'status' => 404
            ], 404);
        }

        // Get all evaluations for this phase and level to count existing medals
        $allEvaluations = Evaluation::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
            ->whereNotNull('score')
            ->with('registration.contestant')
            ->get();

        $evaluations = $allEvaluations->filter(function ($evaluation) use ($levelGrade) {
            return DB::table('contestant_level_grades as clg')
                ->join('level_grades as lg', 'clg.level_grade_id', '=', 'lg.id')
                ->where('clg.contestant_id', $evaluation->registration->contestant_id)
                ->where('lg.level_id', $levelGrade->level_id)
                ->where('lg.olympiad_area_id', $levelGrade->olympiad_area_id)
                ->exists();
        });

        // Count currently assigned medals (excluding the ones we're about to change)
        $evaluationIdsToChange = collect($request->adjustments)->pluck('evaluation_id')->toArray();

        $currentMedals = $evaluations
            ->whereNotIn('id', $evaluationIdsToChange)
            ->filter(function ($evaluation) {
                return !empty($evaluation->classification_place);
            });

        $currentGoldCount = $currentMedals->where('classification_place', 'Oro')->count();
        $currentSilverCount = $currentMedals->where('classification_place', 'Plata')->count();
        $currentBronzeCount = $currentMedals->where('classification_place', 'Bronce')->count();
        $currentHMCount = $currentMedals->where('classification_place', 'Mención honorífica')->count();

        // Count medals in this request
        $requestMedals = collect($request->adjustments);
        $requestGoldCount = $requestMedals->where('new_medal', 'Oro')->count();
        $requestSilverCount = $requestMedals->where('new_medal', 'Plata')->count();
        $requestBronzeCount = $requestMedals->where('new_medal', 'Bronce')->count();
        $requestHMCount = $requestMedals->where('new_medal', 'Mención honorífica')->count();

        // Calculate total medals after this adjustment
        $totalGold = $currentGoldCount + $requestGoldCount;
        $totalSilver = $currentSilverCount + $requestSilverCount;
        $totalBronze = $currentBronzeCount + $requestBronzeCount;
        $totalHM = $currentHMCount + $requestHMCount;

        // Validate against medal limits
        $errors = [];

        if ($totalGold > $medalConfig->gold) {
            $errors[] = [
                'medal' => 'Oro',
                'current' => $currentGoldCount,
                'requested' => $requestGoldCount,
                'total' => $totalGold,
                'limit' => $medalConfig->gold,
                'message' => "Cannot assign {$requestGoldCount} Oro medal(s). Current: {$currentGoldCount}, Limit: {$medalConfig->gold}. Would exceed by " . ($totalGold - $medalConfig->gold)
            ];
        }

        if ($totalSilver > $medalConfig->silver) {
            $errors[] = [
                'medal' => 'Plata',
                'current' => $currentSilverCount,
                'requested' => $requestSilverCount,
                'total' => $totalSilver,
                'limit' => $medalConfig->silver,
                'message' => "Cannot assign {$requestSilverCount} Plata medal(s). Current: {$currentSilverCount}, Limit: {$medalConfig->silver}. Would exceed by " . ($totalSilver - $medalConfig->silver)
            ];
        }

        if ($totalBronze > $medalConfig->bronze) {
            $errors[] = [
                'medal' => 'Bronce',
                'current' => $currentBronzeCount,
                'requested' => $requestBronzeCount,
                'total' => $totalBronze,
                'limit' => $medalConfig->bronze,
                'message' => "Cannot assign {$requestBronzeCount} Bronce medal(s). Current: {$currentBronzeCount}, Limit: {$medalConfig->bronze}. Would exceed by " . ($totalBronze - $medalConfig->bronze)
            ];
        }

        if ($totalHM > $medalConfig->honorable_mention) {
            $errors[] = [
                'medal' => 'Mención honorífica',
                'current' => $currentHMCount,
                'requested' => $requestHMCount,
                'total' => $totalHM,
                'limit' => $medalConfig->honorable_mention,
                'message' => "Cannot assign {$requestHMCount} Mención honorífica medal(s). Current: {$currentHMCount}, Limit: {$medalConfig->honorable_mention}. Would exceed by " . ($totalHM - $medalConfig->honorable_mention)
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Medal assignment would exceed configured limits',
                'errors' => $errors,
                'medal_limits' => [
                    'gold' => $medalConfig->gold,
                    'silver' => $medalConfig->silver,
                    'bronze' => $medalConfig->bronze,
                    'honorable_mention' => $medalConfig->honorable_mention
                ],
                'current_assignments' => [
                    'gold' => $currentGoldCount,
                    'silver' => $currentSilverCount,
                    'bronze' => $currentBronzeCount,
                    'honorable_mention' => $currentHMCount
                ],
                'status' => 400
            ], 400);
        }

        $adjustedCount = 0;
        $adjustedEvaluations = [];

        foreach ($request->adjustments as $adjustment) {
            $evaluation = Evaluation::find($adjustment['evaluation_id']);

            if (!$evaluation) {
                continue;
            }

            // Update classification_place and add justification to description
            $evaluation->classification_place = $adjustment['new_medal'];
            $justificationNote = "AJUSTE MANUAL: {$adjustment['justification']}";

            if ($evaluation->description) {
                $evaluation->description .= " | " . $justificationNote;
            } else {
                $evaluation->description = $justificationNote;
            }

            if ($evaluation->save()) {
                $adjustedCount++;
                $contestant = $evaluation->registration->contestant ?? null;
                $fullName = $contestant
                    ? trim(($contestant->first_name ?? '') . ' ' . ($contestant->last_name ?? ''))
                    : 'Unknown';
                $adjustedEvaluations[] = [
                    'evaluation_id' => $evaluation->id,
                    'contestant_name' => $fullName ?: 'Unknown',
                    'new_medal' => $evaluation->classification_place,
                    'justification' => $adjustment['justification']
                ];
            }
        }

        if ($adjustedCount === 0) {
            return response()->json([
                'message' => 'No evaluations were adjusted',
                'status' => 400
            ], 400);
        }

        return response()->json([
            'message' => 'Medal assignments adjusted successfully',
            'adjusted_count' => $adjustedCount,
            'adjusted_evaluations' => $adjustedEvaluations,
            'status' => 200
        ], 200);
    }
}
