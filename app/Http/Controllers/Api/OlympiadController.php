<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Olympiad;
use App\Models\Area;
use App\Models\Phase;
use App\Models\OlympiadArea;
use App\Models\OlympiadAreaPhase;

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

        $areas = $olympiad->areas;

        if ($areas->isEmpty()) {
            return response()->json([
                'message' => 'No areas found for this olympiad',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'areas' => $areas->pluck('name')->toArray(),
            'status' => 200
        ], 200);
    }
}
