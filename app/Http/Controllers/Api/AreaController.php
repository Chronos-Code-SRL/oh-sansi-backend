<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Models\Area;

/**
 * @OA\Tag(
 *     name="Areas",
 *     description="Endpoints for Areas management"
 * )
 */
class AreaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/areas",
     *     summary="Get list of areas",
     *     tags={"Areas"},
     *     @OA\Response(
     *         response=200,
     *         description="List of areas retrieved successfully",
     *     )
     * )
     */
    public function index()
    {
        $areas = Area::all();

        if ($areas->isEmpty()) {
            $data = [
                'message' => 'No areas found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'areas' => $areas,
            'stauts' => 200

        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/areas",
     *     summary="Create a new area",
     *     tags={"Areas"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", example="Matemáticas"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Area created successfully",
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Data validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:25|unique:areas,name'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $area = Area::create([
            'name' => $request->name
        ]);

        $data = [
            'message' => 'Area created successfully',
            'area' => $area,
            'status' => 201
        ];

        return response()->json($data, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/areas/{id}",
     *     summary="Get specific area by ID",
     *     tags={"Areas"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area found"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $area = Area::find($id);

        if (!$area) {
            $data = [
                'message' => 'Area not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'area' => $area,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/areas/{id}",
     *     summary="Update an area by ID",
     *     tags={"Areas"},
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
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area updated successfully",
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $area = Area::find($id);

        if (!$area) {
            $data = [
                'message' => 'Area not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        // Data validation
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:25',
                Rule::unique('areas', 'name')->ignore($area->id),
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

        $area->name = $request->name;
        $area->save();

        $data = [
            'message' => 'Area updated successfully',
            'area' => $area,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/areas/{id}",
     *     summary="Delete an area by ID",
     *     tags={"Areas"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area deleted successfully",
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $area = Area::find($id);

        if (!$area) {
            $data = [
                'message' => 'Area not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $area->delete();

        $data = [
            'message' => 'Area deleted',
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    // <--- Manage users assigned to an area --->

    /**
     * @OA\Get(
     *     path="/api/areas/{id}/users",
     *     summary="Get users assigned to an area",
     *     tags={"Areas"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of users assigned to the area"
     *     )
     * )
     */
    public function getUsers(string $id): JsonResponse
    {
        $area = Area::find($id);

        if (!$area) {
            $data = [
                'message' => 'Area not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'area' => $area->name,
            'users' => $area->users,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/areas/{id}/users",
     *     summary="Assign users to an area",
     *     tags={"Areas"},
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
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users assigned successfully",
     *     )
     * )
     */
    public function assignUsers(Request $request, $areaId): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $area = Area::findOrFail($areaId);
        $area->users()->attach($request->user_ids);

        return response()->json([
            'message' => 'Users assigned successfully',
            'users' => $area->users()->get()
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/areas/{id}/users",
     *     summary="Remove users from an area",
     *     tags={"Areas"},
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
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users removed successfully",
     *     )
     * )
     */
    public function removeUsers(Request $request, $areaId): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $area = Area::findOrFail($areaId);
        $area->users()->detach($request->user_ids);

        return response()->json([
            'message' => 'Users removed successfully',
            'users' => $area->users()->get()
        ], 200);
    }
}
