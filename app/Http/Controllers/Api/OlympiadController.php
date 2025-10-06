<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            'edition' => 'required|string|max:10|unique:olympiads,edition',
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
            'edition' => $request->edition,
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
            'edition' => [
                'required',
                'string',
                'max:20',
                Rule::unique('olympiads', 'edition')->ignore($olympiad->id),
            ],
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
        $olympiad->edition = $request->edition;
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
    public function assignLevelGradesToArea(Request $request, $olympiadId, $areaId)
    {
        $request->validate([
            'level_name' => 'required|string',
            'grade_ids' => 'required|array',
            'grade_ids.*' => 'exists:grades,id'
        ]);

        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('id', $areaId)
            ->firstOrFail();

        // Create the level
        $level = Level::create([
            'name' => $request->level_name
        ]);

        // Create the level_grades relationships
        $levelGrades = [];
        foreach ($request->grade_ids as $gradeId) {
            $levelGrade = LevelGrade::create([
                'level_id' => $level->id,
                'grade_id' => $gradeId
            ]);
            $levelGrades[] = $levelGrade->id;
        }

        // Assign the level_grades to the olympiad area
        $olympiadArea->levelGrades()->attach($levelGrades);

        return response()->json([
            'message' => 'Level and grades assigned successfully',
            'level' => $level->load('grades'),
        ]);
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
    public function getLevelGradesFromArea($olympiadId, $areaId)
    {
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('id', $areaId)
            ->firstOrFail();

        return response()->json([
            'level_grades' => $olympiadArea->levelGrades()->with(['level', 'grade'])->get()
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/level-grades",
     *     summary="Remove level-grade associations from an olympiad area",
     *     description="Detaches one or more level-grade relationships from the specified olympiad area without deleting the underlying data.",
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
     *             required={"level_grade_ids"},
     *             @OA\Property(
     *                 property="level_grade_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Level grades removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Level grades removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad area not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function removeLevelGradesFromArea(Request $request, $olympiadId, $areaId)
    {
        $request->validate([
            'level_grade_ids' => 'required|array',
            'level_grade_ids.*' => 'exists:level_grades,id'
        ]);

        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('id', $areaId)
            ->firstOrFail();

        $olympiadArea->levelGrades()->detach($request->level_grade_ids);

        return response()->json([
            'message' => 'Level grades removed successfully'
        ]);
    }

    // <--- Score cuts per phase/area/level-grade --->

    /**
     * @OA\Post(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/score-cuts",
     *     summary="Assign score cuts to level-grades for a specific phase in an olympiad area",
     *     description="Creates or updates the score cut (minimum passing score) for each level-grade within a specific phase of an olympiad area.",
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
     *         description="Phase ID and list of score cuts per level-grade",
     *         @OA\JsonContent(
     *             required={"phase_id", "score_cuts"},
     *             @OA\Property(property="phase_id", type="integer", example=3),
     *             @OA\Property(
     *                 property="score_cuts",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"level_grade_id", "score_cut"},
     *                     @OA\Property(property="level_grade_id", type="integer", example=12),
     *                     @OA\Property(property="score_cut", type="number", format="float", example=75.5)
     *                 )
     *             )
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
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=21
     *                         ),
     *                         @OA\Property(
     *                             property="score_cut",
     *                             type="number",
     *                             format="float",
     *                             example=80.0
     *                         ),
     *                         @OA\Property(
     *                             property="olympiad_area_level_grade",
     *                             type="object",
     *                             @OA\Property(
     *                                 property="level_grade",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=12),
     *                                 @OA\Property(property="level_id", type="integer", example=5),
     *                                 @OA\Property(property="grade_id", type="integer", example=9)
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad area or level-grade not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (e.g. invalid phase_id or score_cut out of range)"
     *     )
     * )
     */
    public function assignScoreCuts(Request $request, $olympiadId, $areaId)
    {
        $request->validate([
            'phase_id' => 'required|exists:phases,id',
            'score_cuts' => 'required|array',
            'score_cuts.*.level_grade_id' => 'required|exists:level_grades,id',
            'score_cuts.*.score_cut' => 'required|numeric|min:0|max:100'
        ]);

        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('id', $areaId)
            ->firstOrFail();

        // Get or create OlympiadAreaPhase
        $olympiadAreaPhase = OlympiadAreaPhase::firstOrCreate([
            'olympiad_area_id' => $olympiadArea->id,
            'phase_id' => $request->phase_id
        ]);

        foreach ($request->score_cuts as $scoreCut) {
            // Validate that the level_grade is assigned to this area
            $olympiadAreaLevelGrade = OlympiadAreaLevelGrade::where('olympiad_area_id', $areaId)
                ->where('level_grade_id', $scoreCut['level_grade_id'])
                ->firstOrFail();

            // Create or update the score cut
            OlympiadAreaPhaseLevelGrade::updateOrCreate(
                [
                    'olympiad_area_phase_id' => $olympiadAreaPhase->id,
                    'olympiad_area_level_grade_id' => $olympiadAreaLevelGrade->id,
                ],
                [
                    'score_cut' => $scoreCut['score_cut']
                ]
            );
        }

        return response()->json([
            'message' => 'Score cuts assigned successfully',
            'data' => $olympiadAreaPhase->load([
                'olympiadAreaPhaseLevelGrades.olympiadAreaLevelGrade.levelGrade',
                'phase'
            ])
        ]);
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
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiadId)
            ->where('id', $areaId)
            ->firstOrFail();

        $data = OlympiadAreaPhase::where('olympiad_area_id', $areaId)
            ->with(['phase', 'olympiadAreaPhaseLevelGrades.olympiadAreaLevelGrade.levelGrade'])
            ->get();

        return response()->json(['data' => $data]);
    }
}
