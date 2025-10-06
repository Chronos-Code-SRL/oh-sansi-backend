<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grades = Grade::all();

        if ($grades->isEmpty()) {
            $data = [
                'message' => 'No grades found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'grades' => $grades,
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
            'name' => 'required|string|max:25|unique:grades,name'
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $grade = Grade::create([
            'name' => $request->name
        ]);

        $data = [
            'message' => 'Grade created successfully',
            'grade' => $grade,
            'status' => 201
        ];

        return response()->json($data, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $grade = Grade::find($id);

        if (!$grade) {
            $data = [
                'message' => 'Grade not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'grade' => $grade,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $grade = Grade::find($id);

        if (!$grade) {
            $data = [
                'message' => 'Grade not found',
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
                Rule::unique('grades', 'name')->ignore($grade->id),
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

        $grade->name = $request->name;
        $grade->save();

        $data = [
            'message' => 'Grade updated successfully',
            'grade' => $grade,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $grade = Grade::find($id);

        if (!$grade) {
            $data = [
                'message' => 'Grade not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $grade->delete();

        $data = [
            'message' => 'Grade deleted',
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
