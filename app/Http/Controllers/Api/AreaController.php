<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Area;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
}
