<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Olympiad;

class OlympiadController extends Controller
{
    /**
     * Display a listing of the resource.
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

        $data = [
            'olympiads' => $olympiads,
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
            'name' => 'required|string|max:255',
            'edition' => 'required|string|max:20|unique:olympiads,edition',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
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
            'end_date' => $request->end_date
        ]);

        // If the Olympiad creation fails
        if (!$olympiad) {
            $data = [
                'message' => 'Error creating the Olympiad',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        // If the Olympiad creation is successful
        $data = [
            'olympiad' => $olympiad,
            'status' => 201
        ];

        return response()->json($data, 201);
    }

    /**
     * Display the specified resource.
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
            'olympiad' => $olympiad,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Update the specified resource in storage.
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

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'edition' => 'required|string|max:20|unique:olympiads,edition',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
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

        $olympiad->save();

        $data = [
            'message' => 'Olympiad updated',
            'olympiad' => $olympiad,
            'status' => 200
        ];

        return response()->json($data, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
