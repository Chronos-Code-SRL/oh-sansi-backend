<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $levels = Level::all();

        if ($levels->isEmpty()) {
            $data = [
                'message' => 'No levels found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'levels' => $levels,
            'stauts' => 200

        ];

        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:25|unique:levels,name'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $level = Level::create([
            'name' => $request->name
        ]);

        $data = [
            'message' => 'Level created successfully',
            'level' => $level,
            'status' => 201
        ];

        return response()->json($data, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $level = Level::find($id);

        if (!$level) {
            $data = [
                'message' => 'Level not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'level' => $level,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $level = Level::find($id);

        if (!$level) {
            $data = [
                'message' => 'Level not found',
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
                Rule::unique('levels', 'name')->ignore($level->id),
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

        $level->name = $request->name;
        $level->save();

        $data = [
            'message' => 'Level updated successfully',
            'level' => $level,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $level = Level::find($id);

        if (!$level) {
            $data = [
                'message' => 'Level not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $level->delete();

        $data = [
            'message' => 'Level deleted',
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
