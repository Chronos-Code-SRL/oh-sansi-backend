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

    public function endorsePhaseAllLevels(string $olympiadId, string $areaId, string $phaseId)
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
                $endorseResponse = $this->endorsePhase($olympiadId, $areaId, $levelGrade->level_id, $phaseId);

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
}
