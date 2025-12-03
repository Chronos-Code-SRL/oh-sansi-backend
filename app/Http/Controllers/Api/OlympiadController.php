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
use App\Models\Evaluation;

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

        /* $olympiads->each(function ($olympiad) {
            $olympiad->updateStatus();
        }); */

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
     *     description="Creates a new olympiad with areas and automatically generates the specified number of phases. Areas are automatically assigned to the olympiad.",
     *     tags={"Olympiads"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Olympiad data including basic info, areas, phases and default score cut",
     *         @OA\JsonContent(
     *             required={"name", "start_date", "end_date", "number_of_phases", "default_score_cut", "areas"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 maxLength=30,
     *                 example="OHSansi 2025",
     *                 description="Name of the olympiad"
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2025-01-15",
     *                 description="Start date of the olympiad"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 example="2025-06-15",
     *                 description="End date of the olympiad (must be after start_date)"
     *             ),
     *             @OA\Property(
     *                 property="number_of_phases",
     *                 type="integer",
     *                 minimum=1,
     *                 example=3,
     *                 description="Number of phases to create for this olympiad"
     *             ),
     *             @OA\Property(
     *                 property="default_score_cut",
     *                 type="integer",
     *                 minimum=0,
     *                 maximum=100,
     *                 example=75,
     *                 description="Default score cut that will be automatically assigned when creating level-grades"
     *             ),
     *             @OA\Property(
     *                 property="default_max_score",
     *                 type="integer",
     *                 minimum=0,
     *                 maximum=100,
     *                 example=100,
     *                 description="Default max score that will be automatically assigned when creating level-grades (optional)"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"En planificación", "Activa", "Terminada"},
     *                 example="En planificación",
     *                 description="Status of the olympiad (optional, defaults to 'En planificación')"
     *             ),
     *             @OA\Property(
     *                 property="areas",
     *                 type="array",
     *                 minItems=1,
     *                 description="Array of area names to assign to this olympiad",
     *                 example={"Matemáticas", "Informática"},
     *                 @OA\Items(
     *                     type="string",
     *                     maxLength=25
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Olympiad created successfully with areas and phases",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Olympiad created successfully with specific areas and phases"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="The created olympiad with loaded relationships",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="OHSansi 2025"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-06-15"),
     *                 @OA\Property(property="number_of_phases", type="integer", example=3),
     *                 @OA\Property(property="default_score_cut", type="integer", example=75),
     *                 @OA\Property(property="default_max_score", type="integer", example=100),
     *                 @OA\Property(property="status", type="string", example="En planificación"),
     *                 @OA\Property(
     *                     property="areas",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     example={
     *                         {"id": 1, "name": "Matemáticas"},
     *                         {"id": 2, "name": "Informática"}
     *                     }
     *                 ),
     *                 @OA\Property(
     *                     property="phases",
     *                     type="array",
     *                     description="Automatically created phases based on number_of_phases",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Fase 1"),
     *                         @OA\Property(property="order", type="integer", example=1)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error in data validation"),
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 description="Validation errors details"
     *             ),
     *             @OA\Property(property="status", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during olympiad creation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error creating the Olympiad"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
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
            'default_score_cut' => 'nullable|integer|default:51',
            'default_max_score' => 'nullable|integer|default:100',
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
            'default_score_cut' => $request->default_score_cut ?? 51,
            'default_max_score' => $request->default_max_score ?? 100,
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
        // $olympiad->updateStatus();

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
            'default_score_cut' => 'nullable|integer|min:0|max:100',
            'default_max_score' => 'nullable|integer|min:0|max:100',
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
        $olympiad->default_score_cut = $request->default_score_cut ?? $olympiad->default_score_cut;
        $olympiad->default_max_score = $request->default_max_score ?? $olympiad->default_max_score;
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

    /**
     * @OA\Get(
     *     path="/api/olympiads/{id}/phases",
     *     summary="Get all phases related to a specific olympiad",
     *     description="Retrieves all phases that are associated with any area of the specified olympiad. When an olympiad is created, it generates a specified number of phases that are automatically related to each area through the olympiad_area_phases table.",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Olympiad ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of phases for the specified olympiad retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Phases retrieved successfully"),
     *             @OA\Property(
     *                 property="olympiad",
     *                 type="object",
     *                 description="Basic olympiad information",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="OHSansi 2025"),
     *                 @OA\Property(property="number_of_phases", type="integer", example=3)
     *             ),
     *             @OA\Property(
     *                 property="phases",
     *                 type="array",
     *                 description="List of phases associated with the olympiad",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Fase 1"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(
     *                         property="areas_count",
     *                         type="integer",
     *                         example=2,
     *                         description="Number of areas this phase is associated with in this olympiad"
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="total_phases", type="integer", example=3, description="Total number of phases found"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad not found or no phases found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Olympiad not found"),
     *             @OA\Property(property="error", type="string", example="No olympiad found with ID: 1"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during phase retrieval",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Internal server error"),
     *             @OA\Property(property="error", type="string", example="An unexpected error occurred while retrieving phases"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function getPhases(string $id)
    {
        try {
            // Validate input parameter
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'message' => 'Invalid olympiad ID',
                    'error' => 'Olympiad ID must be a positive integer',
                    'status' => 400
                ], 400);
            }

            // Verify olympiad exists
            $olympiad = Olympiad::find($id);
            if (!$olympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$id}",
                    'status' => 404
                ], 404);
            }

            // Get all phases related to this olympiad through olympiad_area_phases
            // We need to get unique phases that are related to any area of this olympiad
            $phases = Phase::whereHas('olympiadAreaPhases', function ($query) use ($id) {
                $query->whereHas('olympiadArea', function ($subQuery) use ($id) {
                    $subQuery->where('olympiad_id', $id);
                });
            })
            ->withCount(['olympiadAreaPhases as areas_count' => function ($query) use ($id) {
                $query->whereHas('olympiadArea', function ($subQuery) use ($id) {
                    $subQuery->where('olympiad_id', $id);
                });
            }])
            ->orderBy('order')
            ->get();

            if ($phases->isEmpty()) {
                return response()->json([
                    'message' => 'No phases found for this olympiad',
                    'error' => "The olympiad '{$olympiad->name}' (ID: {$olympiad->id}) has no phases associated with its areas. This might indicate that the olympiad was not properly configured during creation.",
                    'olympiad' => [
                        'id' => $olympiad->id,
                        'name' => $olympiad->name,
                        'number_of_phases' => $olympiad->number_of_phases
                    ],
                    'phases' => [],
                    'total_phases' => 0,
                    'status' => 200
                ], 200);
            }

            return response()->json([
                'message' => 'Phases retrieved successfully',
                'olympiad' => [
                    'id' => $olympiad->id,
                    'name' => $olympiad->name,
                    'number_of_phases' => $olympiad->number_of_phases
                ],
                'phases' => $phases,
                'total_phases' => $phases->count(),
                'status' => 200
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in getPhases: ' . $e->getMessage(), [
                'olympiad_id' => $id
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while retrieving phases. Please try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in getPhases: ' . $e->getMessage(), [
                'olympiad_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while retrieving phases. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    // <--- Level-Grades to Areas in Olympiads --->

    /**
     * @OA\Post(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/level-grades",
     *     summary="Assign a level and its grades to an olympiad area with smart level reuse",
     *     description="Assigns grades to a level within an olympiad area. Reuses existing levels with the same name or creates new ones. Automatically assigns default score cuts to all phases if the olympiad has a default_score_cut configured. Prevents duplicate grade assignments within the same olympiad area.",
     *     tags={"Level (with Grades) - Olympiad Areas"},
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
     *         description="ID of the area within the olympiad",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Level name and grade IDs to assign",
     *         @OA\JsonContent(
     *             required={"level_name", "grade_ids"},
     *             @OA\Property(
     *                 property="level_name",
     *                 type="string",
     *                 maxLength=255,
     *                 example="Booster",
     *                 description="Name of the level. If a level with this name already exists, it will be reused instead of creating a new one."
     *             ),
     *             @OA\Property(
     *                 property="grade_ids",
     *                 type="array",
     *                 minItems=1,
     *                 description="Array of grade IDs to assign to this level. Each grade can only be assigned to one level per olympiad area.",
     *                 @OA\Items(type="integer", example={1,2,3})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Level and grades assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Level and grades assigned successfully"),
     *             @OA\Property(
     *                 property="level",
     *                 type="object",
     *                 description="The level with its assigned grades",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Booster"),
     *                 @OA\Property(
     *                     property="grades",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                    example={
     *                         {"id": 1, "name": "1° Grado"},
     *                         {"id": 2, "name": "2° Grado"},
     *                         {"id": 3, "name": "3° Grado"}
     *                     }
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="level_was_created",
     *                 type="boolean",
     *                 example=false,
     *                 description="Indicates if a new level was created (true) or an existing one was reused (false)"
     *             ),
     *             @OA\Property(
     *                 property="created_relationships",
     *                 type="integer",
     *                 example=3,
     *                 description="Number of level-grade relationships created"
     *             ),
     *             @OA\Property(
     *                 property="default_score_cut_assignments",
     *                 type="object",
     *                 description="Information about automatic score cut assignments (only present if olympiad has default_score_cut)",
     *                 @OA\Property(property="olympiad_default_score_cut", type="integer", example=75),
     *                 @OA\Property(
     *                     property="phases",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="phase_id", type="integer", example=1),
     *                         @OA\Property(property="phase_name", type="string", example="Fase 1"),
     *                         @OA\Property(property="score_cuts_assigned", type="integer", example=3)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_phases_processed", type="integer", example=2)
     *             ),
     *             @OA\Property(property="status", type="integer", example=201)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Detailed validation errors"
     *             ),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Olympiad not found"),
     *             @OA\Property(property="error", type="string", example="No olympiad found with ID: 1"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Duplicate grade assignment conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Duplicate grade assignments found"),
     *             @OA\Property(property="error", type="string", example="The following grades are already assigned to levels in this olympiad area: Grade ID 3 (Level: Nivel Secundario)"),
     *             @OA\Property(property="status", type="integer", example=409)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during level-grade assignment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Database error"),
     *             @OA\Property(property="error", type="string", example="A database constraint or connection error occurred. Please check your data and try again."),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
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

            // Find existing level or create new one
            $existingLevel = Level::where('name', $request->level_name)->first();

            if ($existingLevel) {
                // Use existing level
                $level = $existingLevel;
                $levelWasCreated = false;
            } else {
                // Create new level
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
                $levelWasCreated = true;
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

            // Auto-assign default score cuts and max scores to all phases for this area if olympiad has defaults
            $defaultScoreCutAssignments = [];
            if ($olympiad->default_score_cut !== null || $olympiad->default_max_score !== null) {
                try {
                    // Get all existing olympiad_area_phases for this olympiad area
                    $olympiadAreaPhases = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
                        ->with('phase')
                        ->get();

                    if ($olympiadAreaPhases->isNotEmpty()) {
                        foreach ($olympiadAreaPhases as $olympiadAreaPhase) {
                            // Only proceed if the phase actually exists
                            if (!$olympiadAreaPhase->phase) {
                                Log::warning("Phase not found for olympiad_area_phase_id: {$olympiadAreaPhase->id}");
                                continue;
                            }

                            // Assign default score cut, max score to all newly created level-grades for this phase
                            $scoreCutsCreated = 0;
                            foreach ($createdLevelGrades as $levelGrade) {
                                // Determine status based on phase order - first phase (order = 1) should be 'Activa'
                                $phaseStatus = ($olympiadAreaPhase->phase->order === 1) ? 'Activa' : 'Sin empezar';

                                // Prepare default data - ensure score_cut is always provided
                                $defaultData = [
                                    'score_cut' => $olympiad->default_score_cut ?? 0, // Fallback to 0 if null
                                    'status' => $phaseStatus // Set status based on phase order
                                ];

                                if ($olympiad->default_max_score !== null) {
                                    $defaultData['max_score'] = $olympiad->default_max_score;
                                }

                                // Check if the record already exists
                                $existingRecord = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                                    ->where('level_grade_id', $levelGrade->id)
                                    ->first();

                                if (!$existingRecord) {
                                    $scoreCutRecord = OlympiadAreaPhaseLevelGrade::create([
                                        'olympiad_area_phase_id' => $olympiadAreaPhase->id,
                                        'level_grade_id' => $levelGrade->id,
                                        'score_cut' => $defaultData['score_cut'],
                                        'max_score' => $defaultData['max_score'] ?? null,
                                        'status' => $defaultData['status']
                                    ]);

                                    if ($scoreCutRecord) {
                                        $scoreCutsCreated++;
                                    }
                                }
                            }

                            $phaseAssignment = [
                                'phase_name' => $olympiadAreaPhase->phase->name,
                                'phase_id' => $olympiadAreaPhase->phase->id,
                                'score_cuts_created' => $scoreCutsCreated
                            ];

                            if ($olympiad->default_score_cut !== null) {
                                $phaseAssignment['default_score_cut'] = $olympiad->default_score_cut;
                            }
                            if ($olympiad->default_max_score !== null) {
                                $phaseAssignment['default_max_score'] = $olympiad->default_max_score;
                            }

                            $defaultScoreCutAssignments[] = $phaseAssignment;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to assign default score cuts and max scores: ' . $e->getMessage(), [
                        'olympiad_id' => $olympiad->id,
                        'area_id' => $areaId,
                        'level_id' => $level->id
                    ]);
                    // Don't fail the entire operation, just log the warning
                }
            }

            $response = [
                'message' => 'Level and grades assigned successfully',
                'level' => $level->load('grades'),
                'level_was_created' => $levelWasCreated,
                'created_relationships' => count($createdLevelGrades),
                'status' => 201
            ];

            // Add default score cut and max score info if applicable
            if (!empty($defaultScoreCutAssignments)) {
                $responseData = [
                    'phases' => $defaultScoreCutAssignments,
                    'total_phases_processed' => count($defaultScoreCutAssignments)
                ];

                if ($olympiad->default_score_cut !== null) {
                    $responseData['olympiad_default_score_cut'] = $olympiad->default_score_cut;
                }
                if ($olympiad->default_max_score !== null) {
                    $responseData['olympiad_default_max_score'] = $olympiad->default_max_score;
                }

                $response['default_assignments'] = $responseData;
            }

            return response()->json($response, 201);
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
     *     summary="Assign score cut to all grades of a level for a specific phase",
     *     description="Assigns the same score cut (minimum passing score) to ALL grades within a specific level for a given phase in an olympiad area. This simplified approach applies the score cut to every grade in the level at once, making it much easier to manage score cuts by educational level rather than individual grades.",
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
     *         description="ID of the area within the olympiad",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Phase, level and score cut configuration",
     *         @OA\JsonContent(
     *             required={"phase_id", "level_id", "score_cut"},
     *             @OA\Property(
     *                 property="phase_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the phase where the score cut will be applied"
     *             ),
     *             @OA\Property(
     *                 property="level_id",
     *                 type="integer",
     *                 example=3,
     *                 description="ID of the level. Score cut will be applied to ALL grades within this level."
     *             ),
     *             @OA\Property(
     *                 property="score_cut",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 maximum=100,
     *                 example=75.5,
     *                 description="Minimum score required to pass this phase (0-100)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Score cuts assigned successfully to all grades in the level",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Score cuts assigned successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="The olympiad area phase with its score cut assignments",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(
     *                     property="phase",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Fase 1")
     *                 ),
     *                 @OA\Property(
     *                     property="olympiad_area_phase_level_grades",
     *                     type="array",
     *                     description="All level-grade relationships with their assigned score cuts",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=21),
     *                         @OA\Property(property="score_cut", type="number", format="float", example=75.5),
     *                         @OA\Property(
     *                             property="level_grade",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=12),
     *                             @OA\Property(property="level_id", type="integer", example=3),
     *                             @OA\Property(property="grade_id", type="integer", example=9)
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="level_name", type="string", example="Booster"),
     *             @OA\Property(property="level_id", type="integer", example=3),
     *             @OA\Property(property="score_cut", type="number", format="float", example=75.5),
     *             @OA\Property(
     *                 property="affected_grades_count",
     *                 type="integer",
     *                 example=4,
     *                 description="Number of grades affected by this score cut assignment"
     *             ),
     *             @OA\Property(property="created_count", type="integer", example=2, description="Number of new score cut records created"),
     *             @OA\Property(property="updated_count", type="integer", example=2, description="Number of existing score cut records updated"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Detailed validation errors"
     *             ),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Level not found"),
     *             @OA\Property(property="error", type="string", example="The level 'Booster' (ID: 3) has no grade associations in the area 'Matemáticas' for olympiad 'Olimpiada 2025'. Please assign grades to this level first."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during score cut assignment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Database error"),
     *             @OA\Property(property="error", type="string", example="A database error occurred while assigning score cuts. Please try again."),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
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
                'score_cut' => 'required|numeric'
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
            $olympiadAreaPhase = OlympiadAreaPhase::with('phase')->firstOrCreate([
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

            // Determine status based on phase order - first phase (order = 1) should be 'Activa'
            $phaseStatus = ($olympiadAreaPhase->phase && $olympiadAreaPhase->phase->order === 1) ? 'Activa' : 'Sin empezar';

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
                        // Create new score cut with proper defaults
                        $newScoreCut = OlympiadAreaPhaseLevelGrade::create([
                            'olympiad_area_phase_id' => $olympiadAreaPhase->id,
                            'level_grade_id' => $levelGrade->id,
                            'score_cut' => $request->score_cut,
                            'max_score' => $olympiad->default_max_score ?? null,
                            'status' => $phaseStatus
                        ]);

                        if ($newScoreCut) {
                            $createdCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                }
                //sofia
                $contestantIds = DB::table('contestant_level_grades')
                    ->whereIn('level_grade_id', $levelGrades->pluck('id'))
                    ->pluck('contestant_id');

                $registrationIds = DB::table('registrations')
                    ->whereIn('contestant_id', $contestantIds)
                    ->where('olympiad_area_id', $olympiadArea->id)
                    ->pluck('id');

                $evaluations = Evaluation::whereIn('registration_id', $registrationIds)
                    ->where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                    ->get();

                $reclassified = 0;
                foreach ($evaluations as $evaluation) {
                    if ($evaluation->score === null) {
                        continue;
                    }

                    $oldStatus = $evaluation->classification_status;
                    $evaluation->classification_status = $evaluation->score >= $request->score_cut 
                        ? 'clasificado' 
                        : 'no_clasificado';

                    if ($evaluation->classification_status !== $oldStatus) $reclassified++;
                    $evaluation->save();
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
     * @OA\Post(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/max-scores",
     *     summary="Assign max score to all grades of a level for a specific phase",
     *     description="Assigns the same max score (maximum achievable score) to ALL grades within a specific level for a given phase in an olympiad area. This simplified approach applies the max score to every grade in the level at once, making it much easier to manage maximum scores by educational level rather than individual grades.",
     *     tags={"Max scores"},
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
     *         description="ID of the area within the olympiad",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Phase, level and max score configuration",
     *         @OA\JsonContent(
     *             required={"phase_id", "level_id", "max_score"},
     *             @OA\Property(
     *                 property="phase_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the phase where the max score will be applied"
     *             ),
     *             @OA\Property(
     *                 property="level_id",
     *                 type="integer",
     *                 example=3,
     *                 description="ID of the level. Max score will be applied to ALL grades within this level."
     *             ),
     *             @OA\Property(
     *                 property="max_score",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 maximum=100,
     *                 example=100,
     *                 description="Maximum score achievable in this phase (0-100)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Max scores assigned successfully to all grades in the level",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Max scores assigned successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="The olympiad area phase with its max score assignments",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(
     *                     property="phase",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Fase 1")
     *                 ),
     *                 @OA\Property(
     *                     property="olympiad_area_phase_level_grades",
     *                     type="array",
     *                     description="All level-grade relationships with their assigned max scores",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=21),
     *                         @OA\Property(property="max_score", type="number", format="float", example=100),
     *                         @OA\Property(
     *                             property="level_grade",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=12),
     *                             @OA\Property(property="level_id", type="integer", example=3),
     *                             @OA\Property(property="grade_id", type="integer", example=9)
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="level_name", type="string", example="Booster"),
     *             @OA\Property(property="level_id", type="integer", example=3),
     *             @OA\Property(property="max_score", type="number", format="float", example=100),
     *             @OA\Property(
     *                 property="affected_grades_count",
     *                 type="integer",
     *                 example=4,
     *                 description="Number of grades affected by this max score assignment"
     *             ),
     *             @OA\Property(property="created_count", type="integer", example=2, description="Number of new max score records created"),
     *             @OA\Property(property="updated_count", type="integer", example=2, description="Number of existing max score records updated"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Detailed validation errors"
     *             ),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Level not found"),
     *             @OA\Property(property="error", type="string", example="The level 'Booster' (ID: 3) has no grade associations in the area 'Matemáticas' for olympiad 'Olimpiada 2025'. Please assign grades to this level first."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during max score assignment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Database error"),
     *             @OA\Property(property="error", type="string", example="A database error occurred while assigning max scores. Please try again."),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function assignMaxScores(Request $request, $olympiadId, $areaId)
    {
        try {
            // Input validation
            $validator = Validator::make($request->all(), [
                'phase_id' => 'required|integer|exists:phases,id',
                'level_id' => 'required|integer|exists:levels,id',
                'max_score' => 'required|numeric'
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
                    'error' => "The area '{$area->name}' is not assigned to olympiad '{$olympiad->name}'. Please assign the area to the olympiad first.",
                    'status' => 404
                ], 404);
            }

            // Find all level-grades for this level in this olympiad area
            $levelGrades = LevelGrade::where('olympiad_area_id', $olympiadArea->id)
                ->where('level_id', $request->level_id)
                ->with(['level', 'grade'])
                ->get();

            if ($levelGrades->isEmpty()) {
                return response()->json([
                    'message' => 'Level not found in area',
                    'error' => "The level '{$level->name}' (ID: {$level->id}) has no grade associations in the area '{$area->name}' for olympiad '{$olympiad->name}'. Please assign grades to this level first.",
                    'status' => 404
                ], 404);
            }

            // Get or create OlympiadAreaPhase
            $olympiadAreaPhase = OlympiadAreaPhase::with('phase')->firstOrCreate([
                'olympiad_area_id' => $olympiadArea->id,
                'phase_id' => $request->phase_id
            ]);

            if (!$olympiadAreaPhase) {
                return response()->json([
                    'message' => 'Failed to create olympiad area phase',
                    'error' => 'Could not create or find the olympiad area phase relationship.',
                    'status' => 500
                ], 500);
            }

            // Determine status based on phase order - first phase (order = 1) should be 'Activa'
            $phaseStatus = ($olympiadAreaPhase->phase && $olympiadAreaPhase->phase->order === 1) ? 'Activa' : 'Sin empezar';

            // Begin transaction to ensure data consistency
            DB::beginTransaction();

            try {
                $createdCount = 0;
                $updatedCount = 0;

                // Assign max score to all level-grades for this level in this phase
                foreach ($levelGrades as $levelGrade) {
                    $existing = OlympiadAreaPhaseLevelGrade::where('olympiad_area_phase_id', $olympiadAreaPhase->id)
                        ->where('level_grade_id', $levelGrade->id)
                        ->first();

                    if ($existing) {
                        // Only update max_score, leave score_cut and status unchanged
                        $existing->max_score = $request->max_score;
                        $existing->save();
                        $updatedCount++;
                    } else {
                        // Create new record with default score_cut
                        OlympiadAreaPhaseLevelGrade::create([
                            'olympiad_area_phase_id' => $olympiadAreaPhase->id,
                            'level_grade_id' => $levelGrade->id,
                            'max_score' => $request->max_score,
                            'score_cut' => $olympiad->default_score_cut ?? 0,
                            'status' => $phaseStatus
                        ]);
                        $createdCount++;
                    }
                }

                DB::commit();

                // Load the updated data for response
                $olympiadAreaPhase->load([
                    'phase',
                    'olympiadAreaPhaseLevelGrades.levelGrade'
                ]);

                return response()->json([
                    'message' => 'Max scores assigned successfully',
                    'data' => $olympiadAreaPhase,
                    'level_name' => $level->name,
                    'level_id' => $level->id,
                    'max_score' => $request->max_score,
                    'affected_grades_count' => count($levelGrades),
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
                'error' => 'The requested resource could not be found.',
                'status' => 404
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in assignMaxScores: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'level_id' => $request->level_id ?? null,
                'phase_id' => $request->phase_id ?? null
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while assigning max scores. Please try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in assignMaxScores: ' . $e->getMessage(), [
                'olympiad_id' => $olympiadId,
                'area_id' => $areaId,
                'level_id' => $request->level_id ?? null,
                'phase_id' => $request->phase_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while assigning max scores. Please try again later.',
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
                    'error' => "The area {$area->name} is not assigned to olympiad {$olympiad->name}. Verify the area is assigned to this olympiad.",
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

    /**
     * @OA\Get(
     *     path="/api/olympiads/{olympiadId}/areas/{areaId}/max-scores",
     *     summary="Get all max scores for an olympiad area",
     *     description="Retrieves all phases of the specified olympiad area, along with their associated level-grades and max scores.",
     *     tags={"Max scores"},
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
     *         description="List of max scores retrieved successfully",
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
     *                             @OA\Property(property="max_score", type="number", format="float", example=100.0),
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
    public function getMaxScores($olympiadId, $areaId)
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
                    'error' => "The area '{$area->name}' is not assigned to olympiad '{$olympiad->name}'. Verify the area is assigned to this olympiad.",
                    'status' => 404
                ], 404);
            }

            // Get all phases for this olympiad area with their max scores
            $data = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)
                ->with([
                    'phase',
                    'olympiadAreaPhaseLevelGrades.levelGrade.level',
                    'olympiadAreaPhaseLevelGrades.levelGrade.grade'
                ])
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'No max scores found',
                    'error' => "No max scores have been configured for the area '{$area->name}' in olympiad '{$olympiad->name}'.",
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
                'error' => 'A database error occurred while retrieving max scores. Please try again.',
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
                'error' => 'An unexpected error occurred while retrieving max scores. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/olympiads/{id}/activate",
     *     summary="Activate an olympiad and update status of related olympiads",
     *     description="Sets the specified olympiad as 'Activa' and automatically updates the status of other olympiads based on their date ranges relative to the selected olympiad. Olympiads with dates before the selected range become 'Terminada', those after become 'En planificación' (if not already), and the selected olympiad becomes 'Activa'.",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the olympiad to activate",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Olympiad activated and related olympiads updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Olympiad activated and related olympiads updated successfully"),
     *             @OA\Property(
     *                 property="activated_olympiad",
     *                 type="object",
     *                 description="The olympiad that was activated",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="OHSansi 2025"),
     *                 @OA\Property(property="status", type="string", example="Activa"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-06-15")
     *             ),
     *             @OA\Property(
     *                 property="updated_olympiads",
     *                 type="object",
     *                 description="Summary of olympiads that were updated",
     *                 @OA\Property(
     *                     property="terminated",
     *                     type="array",
     *                     description="Olympiads set to 'Terminada' (dates before selected range)",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="OHSansi 2024"),
     *                         @OA\Property(property="previous_status", type="string", example="Activa"),
     *                         @OA\Property(property="new_status", type="string", example="Terminada")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="planned",
     *                     type="array",
     *                     description="Olympiads set to 'En planificación' (dates after selected range)",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="OHSansi 2026"),
     *                         @OA\Property(property="previous_status", type="string", example="Activa"),
     *                         @OA\Property(property="new_status", type="string", example="En planificación")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Olympiad not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Olympiad not found"),
     *             @OA\Property(property="error", type="string", example="No olympiad found with ID: 1"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during olympiad activation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Database error"),
     *             @OA\Property(property="error", type="string", example="A database error occurred while updating olympiad statuses. Please try again."),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function activateOlympiad($id)
    {
        try {
            // Validate input parameter
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'message' => 'Invalid olympiad ID',
                    'error' => 'Olympiad ID must be a positive integer',
                    'status' => 400
                ], 400);
            }

            // Find the selected olympiad
            $selectedOlympiad = Olympiad::find($id);
            if (!$selectedOlympiad) {
                return response()->json([
                    'message' => 'Olympiad not found',
                    'error' => "No olympiad found with ID: {$id}",
                    'status' => 404
                ], 404);
            }

            // Begin transaction to ensure data consistency
            DB::beginTransaction();

            try {

                $updated = Olympiad::where('id', '!=', $id)
                ->where('status', '!=', 'Terminada')
                ->update(['status' => 'Terminada']);

                $previousStatus = $selectedOlympiad->status;
                $selectedOlympiad->status = 'Activa';
                $selectedOlympiad->save();

                DB::commit();

                return response()->json([
                    'message' => 'Olympiad successfully activated. The others were marked as completed.',
                    'activated_olympiad' => [
                        'id' => $selectedOlympiad->id,
                        'name' => $selectedOlympiad->name,
                        'previous_status' => $previousStatus,
                        'new_status' => 'Activa'
                    ],
                    'summary' => [
                        'total_terminated' => $updated,
                        'total_updated' => $updated + 1
                    ],
                    'status' => 200
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in activateOlympiad: ' . $e->getMessage(), [
                'olympiad_id' => $id
            ]);
            return response()->json([
                'message' => 'Database error',
                'error' => 'A database error occurred while updating olympiad statuses. Please try again.',
                'status' => 500
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in activateOlympiad: ' . $e->getMessage(), [
                'olympiad_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Internal server error',
                'error' => 'An unexpected error occurred while activating the olympiad. Please try again later.',
                'status' => 500
            ], 500);
        }
    }

    public function getUserOlympiads(Request $request){
        $user = $request->user();

        $userOlympics = DB::table('users as u')
            ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->join('user_area_olympiads as uao', 'ur.id', '=', 'uao.user_role_id')
            ->join('olympiads as o', 'uao.olympiad_id', '=', 'o.id')
            ->join('areas as a', 'uao.area_id', '=', 'a.id')
            ->where('u.id', $user->id)
            ->select(
                'o.id as olympiad_id',
                'o.name as olympiad_name',
                'o.default_score_cut',
                'o.start_date',
                'o.end_date',
                'o.number_of_phases',
                'o.status',
                'a.id as area_id',
                'a.name as area_name'
            )
            ->orderBy('o.id')
            ->get();

        if ($userOlympics->isEmpty()) {
            return response()->json([
                'message' => 'The user is not associated with any olympiads.',
                'status' => 404
            ], 404);
        }

        $grouped = $userOlympics->groupBy('olympiad_id')->map(function ($items) {
        $olympiad = $items->first();
            return [
                'id' => $olympiad->olympiad_id,
                'name' => $olympiad->olympiad_name,
                'default_score_cut' => $olympiad->default_score_cut,
                'start_date' => $olympiad->start_date,
                'end_date' => $olympiad->end_date,
                'number_of_phases' => $olympiad->number_of_phases,
                'status' => $olympiad->status,
                'areas' => $items->map(function ($area) {
                    return [
                        'id' => $area->area_id,
                        'name' => $area->area_name
                    ];
                })->unique('id')->values()
            ];
        })->values();

        return response()->json($grouped, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/olympiads/{id}/areas/{areaId}/levels",
     *     summary="Get levels for a specific olympiad and area",
     *     description="Retrieve all unique levels associated with a given olympiad and area.",
     *     tags={"Olympiads"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Olympiad ID",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         required=true,
     *         description="Area ID",
     *         @OA\Schema(type="string", example="3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Levels retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Levels retrieved successfully."),
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Intermediate")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No levels found for the specified olympiad and area",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No levels found for the specified olympiad and area."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */

    public function getLevels(string $id, string $areaId){
        $levels = DB::table('olympiads as o')
            ->join('olympiad_areas as oa', 'oa.olympiad_id', '=', 'o.id')
            ->join('level_grades as lg', 'lg.olympiad_area_id', '=', 'oa.id')
            ->join('areas as a', 'oa.area_id', '=', 'a.id')
            ->join('levels as l', 'lg.level_id', '=', 'l.id')
            ->where('a.id', $areaId)
            ->where('o.id', $id)
            ->select('l.id', 'l.name')
            ->distinct()
            ->get();

        if ($levels->isEmpty()) {
            return response()->json([
                'message' => 'No levels found for the specified olympiad and area.',
                'status' => 404
            ], 404);

        }

        return response()->json(
            [
                'message' => 'Levels retrieved successfully.',
                'data' => $levels,
                'status' => 200
            ], 200);
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

            // Usar consulta SQL simplificada que devuelve solo un resultado por nivel
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
    /**
     * @OA\Get(
     *     path="/api/olympiads/active-or-planned",
     *     summary="To obtain active or planned Olympics",
     *     description="Return all Olympics whose status is 'Active' or 'In planning'.",
     *     tags={"Olympiads"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Olympiads retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Olympiads retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Olimpiada Nacional de Matemáticas"),
     *                     @OA\Property(property="status", type="string", example="Activa"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-04-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-04-15")
     *                 )
     *             ),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="There are no active or planned Olympics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="There are no active or planned Olympics."),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */
    public function activeOrPlannedOlympics()
    {
        $olympiads = Olympiad::whereIn('status', ['Activa', 'En planificación'])->get();

        if($olympiads->isEmpty()){
            return response()->json([
                'message' => 'There are no active or planned Olympics.',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'message' => 'Olympiads retrieved successfully.',
            'data' => $olympiads,
            'status' => 200
        ], 200);
    }
}
