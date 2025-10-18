<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\Models\Olympiad;
use App\Models\Area;
use App\Models\Level;
use App\Models\LevelGrade;
use App\Models\Phase;
use App\Models\OlympiadArea;
use App\Models\OlympiadAreaPhase;
use App\Models\OlympiadAreaLevelGrade;
use App\Models\OlympiadAreaPhaseLevelGrade;

/**
 * @OA\Tag(
 *     name="Olympiads",
 *     description="Endpoints for Olympiad management"
 * )
 */
class OlympiadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/olympiads",
     *     summary="Get list of olympiads",
     *     tags={"Olympiads"},
     *     @OA\Response(
     *         response=200,
     *         description="Returns a list of all olympiads with their associated areas",
     *     )
     * )
     */
    public function index()
    {
        $olympiads = Olympiad::all();

        if ($olympiads->isEmpty()) {
            $data = [
                'message' => 'No olympiads found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Mapping olympiads and merging areas names
        $data = [
            'olympiads' => $olympiads->map(function ($olympiad) {
                return array_merge(
                    $olympiad->toArray(),
                    ['areas' => $olympiad->areas->pluck('name')->toArray()]
                );
            }),
            'stauts' => 200

        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/olympiads",
     *     summary="Create a new olympiad",
     *     tags={"Olympiads"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description","start_date","end_date"},
     *             @OA\Property(property="name", type="string", example="Olimpiada de Matemáticas 2025"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Olympiad created successfully",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error in data validation",
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Data validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:30',
            // 'edition' => 'required|string|max:25', //|unique:olympiads,edition',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'number_of_phases' => 'required|integer|min:1',
            'status' => 'in:En planificación,Activa,Terminada',
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:25|exists:areas,name',
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $olympiad = Olympiad::create([
            'name' => $request->name,
            //'edition' => $request->edition,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'number_of_phases' => $request->number_of_phases,
            'status' => $request->status ?? 'En planificación',
        ]);

        // If the Olympiad creation fails
        if (!$olympiad) {
            $data = [
                'message' => 'Error creating the Olympiad',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        // Assign areas
        $olympiad = $olympiad->assignAreas($request->areas);

        return response()->json([
            'message' => 'Olympiad created successfully with specific areas and phases',
            'data' => $olympiad->load('areas', 'phases'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/olympiads/{id}",
     *     summary="Get a specific olympiad by ID",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Olympiad ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Olympiad found and returned successfully",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad not found",
     *     )
     * )
     */
    public function show(string $id)
    {
        $olympiad = Olympiad::find($id);

        if (!$olympiad) {
            $data = [
                'message' => 'Olympiad not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        };

        $data = [
            'olympiad' => array_merge(
                $olympiad->toArray(),
                ['areas' => $olympiad->areas->pluck('name')->toArray()]
            ),
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/olympiads/{id}",
     *     summary="Update an existing olympiad",
     *     tags={"Olympiads"},
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
     *         description="Olympiad updated successfully",
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $olympiad = Olympiad::find($id);

        if (!$olympiad) {
            $data = [
                'message' => 'Olympiad not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        };

        // Rule set to ignore the edition if it is the same as the one sent
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // 'edition' => 'required|string|max:25',
            // 'edition' => [
            //     'required',
            //     'string',
            //     'max:20',
            //     Rule::unique('olympiads', 'edition')->ignore($olympiad->id),
            // ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'in:En planificación,Activa,Terminada',
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $olympiad->name = $request->name;
        // $olympiad->edition = $request->edition;
        $olympiad->start_date = $request->start_date;
        $olympiad->end_date = $request->end_date;
        $olympiad->status = $request->status ?? $olympiad->status;

        $olympiad->save();

        $data = [
            'message' => 'Olympiad updated',
            'olympiad' => $olympiad,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/olympiads/{id}",
     *     summary="Delete an olympiad by ID",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Olympiad deleted successfully",
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $olympiad = Olympiad::find($id);

        if (!$olympiad) {
            $data = [
                'message' => 'Olympiad not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        };

        $olympiad->delete();

        $data = [
            'message' => 'Olympiad deleted',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/olympiads/{id}/areas",
     *     summary="Assign areas to an olympiad",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="areas",
     *                 type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Areas assigned successfully",
     *     )
     * )
     */
    public function assignAreas(Request $request, $id)
    {
        $olympiad = Olympiad::find($id);

        if (!$olympiad) {
            return response()->json([
                'message' => 'Olympiad not found',
                'status' => 404
            ], 404);
        }

        // Data validation
        $validator = Validator::make($request->all(), [
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:25|exists:areas,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ], 400);
        }

        $olympiad = $olympiad->assignAreas($request->areas);

        return response()->json([
            'message' => 'Areas assigned successfully',
            'data' => $olympiad,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/olympiads/{id}/areas",
     *     summary="Get areas of a specific olympiad",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of areas for the specified olympiad",
     *     )
     * )
     */
    public function getAreas(string $id)
    {
        $olympiad = Olympiad::find($id);

        if (!$olympiad) {
            return response()->json([
                'message' => 'Olympiad not found',
                'status' => 404
            ], 404);
        }

        $areas = $olympiad->areas()->select('areas.id', 'areas.name')->get();

        if ($areas->isEmpty()) {
            return response()->json([
                'message' => 'No areas found for this olympiad',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'areas' => $areas,
            'status' => 200
        ], 200);
    }

    // <--- Level-Grades to Areas in Olympiads --->

    /**
     * @OA\Post(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/level-grades",
     *     summary="Assign a level and its grades to an olympiad area",
     *     description="Creates a new level, associates it with the given grades, and attaches the resulting level-grade relationships to the specified olympiad area.",
     *     tags={"Level (with Grades) - Olympiad Areas"},
     *     @OA\Parameter(
     *         name="olympiadId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad area",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"level_name", "grade_ids"},
     *             @OA\Property(property="level_name", type="string", example="Primary Level"),
     *             @OA\Property(
     *                 property="grade_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Level and grades assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Level and grades assigned successfully"),
     *             @OA\Property(property="level", type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="Primary Level"),
     *                 @OA\Property(
     *                     property="grades",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="3rd Grade")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad area not found"
     *     )
     * )
     */
    // public function assignLevelGradesToArea(Request $request, $olympiadId, $areaId)
    // {
    //     $request->validate([
    //         'level_name' => 'required|string',
    //         'grade_ids' => 'required|array',
    //         'grade_ids.*' => 'exists:grades,id'
    //     ]);

    //     $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
    //         ->where('area_id', $areaId)
    //         ->firstOrFail();

    //     // Create the level
    //     $level = Level::create([
    //         'name' => $request->level_name
    //     ]);

    //     // Create the level_grades relationships
    //     $levelGrades = [];
    //     foreach ($request->grade_ids as $gradeId) {
    //         $levelGrade = LevelGrade::create([
    //             'level_id' => $level->id,
    //             'grade_id' => $gradeId
    //         ]);
    //         $levelGrades[] = $levelGrade->id;
    //     }

    //     // Assign the level_grades to the olympiad area
    //     $olympiadArea->levelGrades()->attach($levelGrades);

    //     return response()->json([
    //         'message' => 'Level and grades assigned successfully',
    //         'level' => $level->load('grades'),
    //     ]);
    // }

    public function assignLevelGradesToArea(Request $request, $olympiadId, $areaId)
    {
        try {
            // Input validation
            $validator = Validator::make($request->all(), [
                'level_name' => 'required|string|max:255',
                'grade_ids' => 'required|array|min:1',
                'grade_ids.*' => 'exists:grades,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'status' => 422
                ], 422);
            }

            // Verify olympiad exists
            $olympiad = Olympiad::find($olympiadId);
            if (!$olympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$olympiadId}",
                    'status' => 404
                ], 404);
            }

            // Verify area exists
            $area = Area::find($areaId);
            if (!$area) {
                return response()->json([
                    'message' => 'Area not found',
                    'error' => "No area found with ID: {$areaId}",
                    'status' => 404
                ], 404);
            }

            // Verify olympiad-area relationship exists
            $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                ->where('area_id', $areaId)
                ->first();

            if (!$olympiadArea) {
                return response()->json([
                    'message' => 'Olympiad-Area relationship not found',
                    'error' => "The area with ID {$areaId} is not associated with olympiad ID {$olympiadId}. Please verify the area is assigned to this olympiad.",
                    'status' => 404
                ], 404);
            }

            // Check if level name already exists (optional validation)
            $existingLevel = Level::where('name', $request->level_name)->first();
            if ($existingLevel) {
                return response()->json([
                    'message' => 'Level name already exists',
                    'error' => "A level with the name '{$request->level_name}' already exists with ID: {$existingLevel->id}",
                    'status' => 409
                ], 409);
            }

            // Verify all grade IDs exist (additional check beyond validation)
            $existingGrades = \App\Models\Grade::whereIn('id', $request->grade_ids)->pluck('id')->toArray();
            $missingGradeIds = array_diff($request->grade_ids, $existingGrades);

            if (!empty($missingGradeIds)) {
                return response()->json([
                    'message' => 'Some grades not found',
                    'error' => 'The following grade IDs do not exist: ' . implode(', ', $missingGradeIds),
                    'status' => 404
                ], 404);
            }

            // Check for duplicate grade assignments in this olympiad area
            $existingLevelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
                ->whereIn('grade_id', $request->grade_ids)
                ->with('level')
                ->get();

            if ($existingLevelGrades->isNotEmpty()) {
                $duplicateInfo = $existingLevelGrades->map(function ($levelGrade) {
                    return "Grade ID {$levelGrade->grade_id} (Level: {$levelGrade->level->name})";
                })->toArray();

                return response()->json([
                    'message' => 'Duplicate grade assignments found',
                    'error' => 'The following grades are already assigned to levels in this olympiad area: ' . implode(', ', $duplicateInfo),
                    'status' => 409
                ], 409);
            }

            // Create the level
            $level = Level::create([
                'name' => $request->level_name
            ]);

            if (!$level) {
                return response()->json([
                    'message' => 'Failed to create level',
                    'error' => 'Database error occurred while creating the level. Please try again.',
                    'status' => 500
                ], 500);
            }

            // Create the level_grades relationships
            $createdLevelGrades = [];
            $failedGrades = [];

            foreach ($request->grade_ids as $gradeId) {
                try {
                    $levelGrade = LevelGrade::create([
                        'olympiad_area_id' => $olympiadArea->id,
                        'level_id' => $level->id,
                        'grade_id' => $gradeId
                    ]);

                    if ($levelGrade) {
                        $createdLevelGrades[] = $levelGrade;
                    } else {
                        $failedGrades[] = $gradeId;
                    }
                } catch (\Exception $e) {
                    $failedGrades[] = $gradeId;
                    Log::error("Failed to create LevelGrade for grade ID {$gradeId}: " . $e->getMessage());
                }
            }

            // Check if any grade assignments failed
            if (!empty($failedGrades)) {
                // Rollback: delete the level if some grades failed
                $level->delete();

                return response()->json([
                    'message' => 'Failed to assign some grades to level',
                    'error' => 'Failed to create level-grade relationships for grade IDs: ' . implode(', ', $failedGrades),
                    'status' => 500
                ], 500);
            }

            // Verify all level-grades were created
            if (count($createdLevelGrades) !== count($request->grade_ids)) {
                return response()->json([
                    'message' => 'Incomplete level-grade creation',
                    'error' => sprintf(
                        'Expected to create %d level-grade relationships but only created %d. This may indicate a database constraint issue.',
                        count($request->grade_ids),
                        count($createdLevelGrades)
                    ),
                    'status' => 500
                ], 500);
            }

            return response()->json([
                'message' => 'Level and grades assigned successfully',
                'level' => $level->load('grades'),
                'created_relationships' => count($createdLevelGrades),
                'status' => 201
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested olympiad area relationship could not be found. Please verify the olympiad and area IDs.',
                'status' => 404
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in assignLevelGradesToArea: ' . $e->getMessage());
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database constraint or connection error occurred. Please check your data and try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in assignLevelGradesToArea: ' . $e->getMessage());
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while processing your request. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/level-grades",
     *     summary="Get all level-grade associations from an olympiad area",
     *     description="Retrieves all level-grade relationships attached to the specified olympiad area, including their associated level and grade data.",
     *     tags={"Level (with Grades) - Olympiad Areas"},
     *     @OA\Parameter(
     *         name="olympiadId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad area",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of level-grade relationships retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="level_grades",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=25),
     *                     @OA\Property(
     *                         property="level",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="Primary Level")
     *                     ),
     *                     @OA\Property(
     *                         property="grade",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="4th Grade")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad area not found"
     *     )
     * )
     */
    // public function getLevelGradesFromArea($olympiadId, $areaId)
    // {
    //     $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
    //         ->where('id', $areaId)
    //         ->firstOrFail();

    //     return response()->json([
    //         'level_grades' => $olympiadArea->levelGrades()->with(['level', 'grade'])->get()
    //     ]);
    // }
    public function getLevelGradesFromArea($olympiadId, $areaId)
    {
        try {
            // Validate input parameters
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

            // Verify olympiad exists
            $olympiad = Olympiad::find($olympiadId);
            if (!$olympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$olympiadId}",
                    'status' => 404
                ], 404);
            }

            // Verify area exists
            $area = Area::find($areaId);
            if (!$area) {
                return response()->json([
                    'message' => 'Area not found',
                    'error' => "No area found with ID: {$areaId}",
                    'status' => 404
                ], 404);
            }

            // Find the olympiad-area relationship
            $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                ->where('area_id', $areaId)
                ->first();

            if (!$olympiadArea) {
                return response()->json([
                    'message' => 'Olympiad-Area relationship not found',
                    'error' => "The area with ID {$areaId} is not associated with olympiad ID {$olympiadId}. Please verify the area is assigned to this olympiad.",
                    'status' => 404
                ], 404);
            }

            // Get level-grades with their relationships
            $levelGrades = $olympiadArea->levelGrades()->with(['level', 'grade'])->get();

            // Check if any level-grades exist
            if ($levelGrades->isEmpty()) {
                return response()->json([
                    'message' => 'No level-grades found',
                    'error' => "No level-grade relationships found for olympiad area. The area '{$area->name}' in olympiad '{$olympiad->name}' has no assigned level-grades.",
                    'level_grades' => [],
                    'status' => 200
                ], 200);
            }

            // Validate data integrity
            $invalidLevelGrades = [];
            $validLevelGrades = [];

            foreach ($levelGrades as $levelGrade) {
                if (!$levelGrade->level) {
                    $invalidLevelGrades[] = "Level-Grade ID {$levelGrade->id} has missing level relationship";
                    continue;
                }

                if (!$levelGrade->grade) {
                    $invalidLevelGrades[] = "Level-Grade ID {$levelGrade->id} has missing grade relationship";
                    continue;
                }

                $validLevelGrades[] = $levelGrade;
            }

            // If there are data integrity issues, log them but still return valid data
            if (!empty($invalidLevelGrades)) {
                Log::warning('Data integrity issues found in getLevelGradesFromArea', [
                    'olympiad_id' => $olympiadId,
                    'area_id' => $areaId,
                    'issues' => $invalidLevelGrades
                ]);
            }

            // Prepare response data with additional context
            $responseData = [
                'level_grades' => $validLevelGrades,
                'olympiad' => [
                    'id' => $olympiad->id,
                    'name' => $olympiad->name
                ],
                'area' => [
                    'id' => $area->id,
                    'name' => $area->name
                ],
                'total_count' => count($validLevelGrades),
                'status' => 200
            ];

            // Add warnings if there were invalid records
            if (!empty($invalidLevelGrades)) {
                $responseData['warnings'] = [
                    'message' => 'Some level-grade relationships have missing data',
                    'details' => $invalidLevelGrades,
                    'total_invalid' => count($invalidLevelGrades)
                ];
            }

            return response()->json($responseData, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested olympiad area relationship could not be found. Please verify the olympiad and area IDs.',
                'status' => 404
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in getLevelGradesFromArea: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while retrieving level-grade relationships. Please try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in getLevelGradesFromArea: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while retrieving level-grade relationships. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    /* public function removeLevelGradesFromArea(Request $request, $olympiadId, $areaId)
    {
        $request->validate([
            'level_id' => 'required|exists:levels,id'
        ]);

        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('id', $areaId)
            ->firstOrFail();

        $levelGradeIds = LevelGrade::where('level_id', $request->level_id)->pluck('id');

        $olympiadArea->levelGrades()->detach($levelGradeIds);

        return response()->json([
            'message' => 'Level and all its grades removed from area successfully'
        ]);
    } */
    public function removeLevelGradesFromArea(Request $request, $olympiadId, $areaId)
    {
        try {
            // Validate input parameters
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

            // Input validation
            $validator = Validator::make($request->all(), [
                'level_id' => 'required|integer|exists:levels,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'status' => 422
                ], 422);
            }

            // Verify olympiad exists
            $olympiad = Olympiad::find($olympiadId);
            if (!$olympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$olympiadId}",
                    'status' => 404
                ], 404);
            }

            // Verify area exists
            $area = Area::find($areaId);
            if (!$area) {
                return response()->json([
                    'message' => 'Area not found',
                    'error' => "No area found with ID: {$areaId}",
                    'status' => 404
                ], 404);
            }

            // Verify level exists
            $level = Level::find($request->level_id);
            if (!$level) {
                return response()->json([
                    'message' => 'Level not found',
                    'error' => "No level found with ID: {$request->level_id}",
                    'status' => 404
                ], 404);
            }

            // Find the olympiad-area relationship
            $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                ->where('area_id', $areaId)
                ->first();

            if (!$olympiadArea) {
                return response()->json([
                    'message' => 'Olympiad-Area relationship not found',
                    'error' => "The area with ID {$areaId} is not associated with olympiad ID {$olympiadId}. Please verify the area is assigned to this olympiad.",
                    'status' => 404
                ], 404);
            }

            // Find level-grades for the specified level in this olympiad area
            $levelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
                ->where('level_id', $request->level_id)
                ->with(['grade'])
                ->get();

            if ($levelGrades->isEmpty()) {
                return response()->json([
                    'message' => 'No level-grades found to remove',
                    'error' => "The level '{$level->name}' (ID: {$level->id}) has no grade associations in the area '{$area->name}' for olympiad '{$olympiad->name}'. There might be nothing to remove or the level was never assigned to this area.",
                    'status' => 404
                ], 404);
            }

            // Check for related data that might be affected
            $levelGradeIds = $levelGrades->pluck('id')->toArray();

            // Check if there are score cuts using these level-grades
            $relatedScoreCuts = OlympiadAreaPhaseLevelGrade::whereIn('level_grade_id', $levelGradeIds)->count();

            // Collect information about what will be removed
            $removalInfo = [
                'level_name' => $level->name,
                'level_id' => $level->id,
                'area_name' => $area->name,
                'olympiad_name' => $olympiad->name,
                'grades_to_remove' => $levelGrades->map(function ($lg) {
                    return [
                        'id' => $lg->id,
                        'grade_name' => $lg->grade ? $lg->grade->name : 'Unknown Grade',
                        'grade_id' => $lg->grade_id
                    ];
                })->toArray(),
                'total_relationships' => $levelGrades->count(),
                'related_score_cuts' => $relatedScoreCuts
            ];

            // Warning if there are related score cuts
            $warnings = [];
            if ($relatedScoreCuts > 0) {
                $warnings[] = "This action will affect {$relatedScoreCuts} score cut configuration(s) that reference these level-grade relationships.";
            }

            try {
                // Begin transaction to ensure data consistency
                DB::beginTransaction();

                // Remove related score cuts first (if any)
                if ($relatedScoreCuts > 0) {
                    $deletedScoreCuts = OlympiadAreaPhaseLevelGrade::whereIn('level_grade_id', $levelGradeIds)->delete();
                    Log::info("Removed {$deletedScoreCuts} score cuts related to level-grades being removed", [
                        'olympiad_id' => $olympiadId,
                        'area_id' => $areaId,
                        'level_id' => $request->level_id
                    ]);
                }

                // Remove the level-grade relationships from this olympiad area
                $removedCount = 0;
                $failedRemovals = [];

                foreach ($levelGrades as $levelGrade) {
                    try {
                        if ($levelGrade->delete()) {
                            $removedCount++;
                        } else {
                            $failedRemovals[] = $levelGrade->id;
                        }
                    } catch (\Exception $e) {
                        $failedRemovals[] = $levelGrade->id;
                        Log::error("Failed to delete LevelGrade ID {$levelGrade->id}: " . $e->getMessage());
                    }
                }

                // Check if all removals were successful
                if (!empty($failedRemovals)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Failed to remove some level-grade relationships',
                        'error' => 'Failed to remove level-grade relationship IDs: ' . implode(', ', $failedRemovals),
                        'partially_removed' => $removedCount,
                        'total_expected' => $levelGrades->count(),
                        'status' => 500
                    ], 500);
                }

                // Verify all were removed
                if ($removedCount !== $levelGrades->count()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Incomplete removal of level-grade relationships',
                        'error' => sprintf(
                            'Expected to remove %d level-grade relationships but only removed %d. This may indicate a database constraint issue.',
                            $levelGrades->count(),
                            $removedCount
                        ),
                        'status' => 500
                    ], 500);
                }

                DB::commit();

                // Prepare success response
                $response = [
                    'message' => 'Level and all its grades removed from area successfully',
                    'removed_count' => $removedCount,
                    'level_name' => $level->name,
                    'level_id' => $level->id,
                    'area_name' => $area->name,
                    'olympiad_name' => $olympiad->name,
                    'removed_relationships' => $removalInfo['grades_to_remove'],
                    'status' => 200
                ];

                // Add warnings if any
                if (!empty($warnings)) {
                    $response['warnings'] = $warnings;
                }

                // Add info about score cuts if they were removed
                if ($relatedScoreCuts > 0) {
                    $response['additional_actions'] = [
                        'score_cuts_removed' => $relatedScoreCuts,
                        'message' => 'Related score cut configurations were also removed to maintain data consistency.'
                    ];
                }

                return response()->json($response, 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; // Re-throw to be caught by outer catch block
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'One of the requested resources (olympiad, area, or level) could not be found. Please verify the IDs.',
                'status' => 404
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in removeLevelGradesFromArea: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'level_id' => $request->level_id ?? null
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while removing level-grade relationships. This might be due to foreign key constraints or connection issues.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in removeLevelGradesFromArea: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'level_id' => $request->level_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while removing level-grade relationships. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    // <--- Score cuts per phase/area/level-grade --->

    /**
     * @OA\Post(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/score-cuts",
     *     summary="Assign score cut to all grades of a level for a specific phase in an olympiad area",
     *     description="Creates or updates the score cut (minimum passing score) for all level-grades of a specific level within a specific phase of an olympiad area.",
     *     tags={"Score cuts"},
     *     @OA\Parameter(
     *         name="olympiadId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad area",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Phase ID, level ID and score cut to apply to all grades of that level",
     *         @OA\JsonContent(
     *             required={"phase_id", "level_id", "score_cut"},
     *             @OA\Property(property="phase_id", type="integer", example=3),
     *             @OA\Property(property="level_id", type="integer", example=5),
     *             @OA\Property(property="score_cut", type="number", format="float", example=75.5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Score cuts assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Score cuts assigned successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="The created or updated OlympiadAreaPhase with its related phase and score cuts",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="phase", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Final Phase")
     *                 ),
     *                 @OA\Property(
     *                     property="olympiad_area_phase_level_grades",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=21),
     *                         @OA\Property(property="score_cut", type="number", format="float", example=80.0),
     *                         @OA\Property(
     *                             property="level_grade",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=12),
     *                             @OA\Property(property="level_id", type="integer", example=5),
     *                             @OA\Property(property="grade_id", type="integer", example=9)
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="level_name", type="string", example="Primary Level"),
     *             @OA\Property(property="affected_grades_count", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad area, level, or level-grades not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (e.g. invalid phase_id or score_cut out of range)"
     *     )
     * )
     */
    public function assignScoreCuts(Request $request, $olympiadId, $areaId)
    {
        try {
            // Input validation
            $validator = Validator::make($request->all(), [
                'phase_id' => 'required|integer|exists:phases,id',
                'level_id' => 'required|integer|exists:levels,id',
                'score_cut' => 'required|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'status' => 422
                ], 422);
            }

            // Verify olympiad exists
            $olympiad = Olympiad::find($olympiadId);
            if (!$olympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$olympiadId}",
                    'status' => 404
                ], 404);
            }

            // Verify area exists
            $area = Area::find($areaId);
            if (!$area) {
                return response()->json([
                    'message' => 'Area not found',
                    'error' => "No area found with ID: {$areaId}",
                    'status' => 404
                ], 404);
            }

            // Verify level exists
            $level = Level::find($request->level_id);
            if (!$level) {
                return response()->json([
                    'message' => 'Level not found',
                    'error' => "No level found with ID: {$request->level_id}",
                    'status' => 404
                ], 404);
            }

            // Verify olympiad-area relationship exists
            $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                ->where('area_id', $areaId)
                ->first();

            if (!$olympiadArea) {
                return response()->json([
                    'message' => 'Olympiad-Area relationship not found',
                    'error' => "The area with ID {$areaId} is not associated with olympiad ID {$olympiadId}. Please verify the area is assigned to this olympiad.",
                    'status' => 404
                ], 404);
            }

            // Find all level-grades for this level in this olympiad area
            $levelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
                ->where('level_id', $request->level_id)
                ->with(['grade'])
                ->get();

            if ($levelGrades->isEmpty()) {
                return response()->json([
                    'message' => 'No level-grades found',
                    'error' => "The level '{$level->name}' (ID: {$level->id}) has no grade associations in the area '{$area->name}' for olympiad '{$olympiad->name}'. Please assign grades to this level first.",
                    'status' => 404
                ], 404);
            }

            // Get or create OlympiadAreaPhase
            $olympiadAreaPhase = OlympiadAreaPhase::firstOrCreate([
                'olympiad_area_id' => $olympiadArea->id,
                'phase_id' => $request->phase_id
            ]);

            if (!$olympiadAreaPhase) {
                return response()->json([
                    'message' => 'Failed to create or find olympiad area phase',
                    'error' => 'Database error occurred while creating the olympiad area phase relationship.',
                    'status' => 500
                ], 500);
            }

            // Begin transaction to ensure data consistency
            DB::beginTransaction();

            try {
                $createdCount = 0;
                $updatedCount = 0;
                $failedCount = 0;

                // Assign the same score cut to all level-grades of this level
                foreach ($levelGrades as $levelGrade) {
                    $scoreCutRecord = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                        ->where('level_grade_id', $levelGrade->id)
                        ->first();

                    if ($scoreCutRecord) {
                        // Update existing score cut
                        $scoreCutRecord->score_cut = $request->score_cut;
                        if ($scoreCutRecord->save()) {
                            $updatedCount++;
                        } else {
                            $failedCount++;
                        }
                    } else {
                        // Create new score cut
                        $newScoreCut = OlympiadAreaPhaseLevelGrade::create([
                            'olympiad_area_phase_id' => $olympiadAreaPhase->id,
                            'level_grade_id' => $levelGrade->id,
                            'score_cut' => $request->score_cut
                        ]);

                        if ($newScoreCut) {
                            $createdCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                }

                // Check if any operations failed
                if ($failedCount > 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Failed to assign score cuts to some level-grades',
                        'error' => "Failed to process {$failedCount} out of {$levelGrades->count()} level-grade relationships.",
                        'created' => $createdCount,
                        'updated' => $updatedCount,
                        'failed' => $failedCount,
                        'status' => 500
                    ], 500);
                }

                DB::commit();

                // Load the updated data for response
                $olympiadAreaPhase->load([
                    'olympiadAreaPhaseLevelGrades.levelGrade.level',
                    'olympiadAreaPhaseLevelGrades.levelGrade.grade',
                    'phase'
                ]);

                return response()->json([
                    'message' => 'Score cuts assigned successfully',
                    'data' => $olympiadAreaPhase,
                    'level_name' => $level->name,
                    'level_id' => $level->id,
                    'score_cut' => $request->score_cut,
                    'affected_grades_count' => $levelGrades->count(),
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                    'status' => 200
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'One of the requested resources could not be found. Please verify the IDs.',
                'status' => 404
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in assignScoreCuts: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'level_id' => $request->level_id ?? null,
                'phase_id' => $request->phase_id ?? null
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while assigning score cuts. Please try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in assignScoreCuts: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'level_id' => $request->level_id ?? null,
                'phase_id' => $request->phase_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while assigning score cuts. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/score-cuts",
     *     summary="Get all score cuts for an olympiad area",
     *     description="Retrieves all phases of the specified olympiad area, along with their associated level-grades and score cuts.",
     *     tags={"Score cuts"},
     *     @OA\Parameter(
     *         name="olympiadId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad area",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of score cuts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=15),
     *                     @OA\Property(property="phase", type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Final Phase")
     *                     ),
     *                     @OA\Property(
     *                         property="olympiad_area_phase_level_grades",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=22),
     *                             @OA\Property(property="score_cut", type="number", format="float", example=75.0),
     *                             @OA\Property(
     *                                 property="olympiad_area_level_grade",
     *                                 type="object",
     *                                 @OA\Property(
     *                                     property="level_grade",
     *                                     type="object",
     *                                     @OA\Property(property="id", type="integer", example=12),
     *                                     @OA\Property(property="level_id", type="integer", example=5),
     *                                     @OA\Property(property="grade_id", type="integer", example=9)
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad area not found"
     *     )
     * )
     */
    public function getScoreCuts($olympiadId, $areaId)
    {
        try {
            // Validate input parameters
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

            // Verify olympiad exists
            $olympiad = Olympiad::find($olympiadId);
            if (!$olympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$olympiadId}",
                    'status' => 404
                ], 404);
            }

            // Verify area exists
            $area = Area::find($areaId);
            if (!$area) {
                return response()->json([
                    'message' => 'Area not found',
                    'error' => "No area found with ID: {$areaId}",
                    'status' => 404
                ], 404);
            }

            // Verify olympiad-area relationship exists
            $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
                ->where('area_id', $areaId)
                ->first();

            if (!$olympiadArea) {
                return response()->json([
                    'message' => 'Olympiad-Area relationship not found',
                    'error' => "The area with ID {$areaId} is not associated with olympiad ID {$olympiadId}. Please verify the area is assigned to this olympiad.",
                    'status' => 404
                ], 404);
            }

            // Get all phases for this olympiad area with their score cuts
            $data = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
                ->with([
                    'phase',
                    'olympiadAreaPhaseLevelGrades.levelGrade.level',
                    'olympiadAreaPhaseLevelGrades.levelGrade.grade'
                ])
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'No score cuts found',
                    'error' => "No score cuts have been configured for the area '{$area->name}' in olympiad '{$olympiad->name}'.",
                    'data' => [],
                    'status' => 200
                ], 200);
            }

            // Add context information to the response
            $responseData = [
                'data' => $data,
                'olympiad' => [
                    'id' => $olympiad->id,
                    'name' => $olympiad->name
                ],
                'area' => [
                    'id' => $area->id,
                    'name' => $area->name
                ],
                'total_phases' => $data->count(),
                'status' => 200
            ];

            return response()->json($responseData, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'One of the requested resources could not be found. Please verify the olympiad and area IDs.',
                'status' => 404
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in getScoreCuts: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while retrieving score cuts. Please try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in getScoreCuts: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while retrieving score cuts. Please try again later.',
                'status' => 500
            ], 500);
        }
    }
}
