<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Models\Area;

class AreaController extends Controller
{
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
            'status' => 200
        ];

        return response()->json($data, 200);
    }

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

    /* ========================================
     * USER MANAGEMENT METHODS
     * Methods for managing user assignments
     * and relationships within areas.
     * ======================================== */
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

    public function assignUsers(Request $request, $areaId): JsonResponse
    {
        // Validate that user IDs exist in the database
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $area = Area::findOrFail($areaId);
        // Create many-to-many relationships between area and users
        $area->users()->attach($request->user_ids);

        return response()->json([
            'message' => 'Users assigned successfully',
            'users' => $area->users()->get()
        ], 200);
    }

    public function removeUsers(Request $request, $areaId): JsonResponse
    {
        // Validate that user IDs exist before attempting removal
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $area = Area::findOrFail($areaId);
        // Remove many-to-many relationships between area and users
        $area->users()->detach($request->user_ids);

        return response()->json([
            'message' => 'Users removed successfully',
            'users' => $area->users()->get()
        ], 200);
    }
}
