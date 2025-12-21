<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\OlympiadAreaMedal;
use App\Models\OlympiadArea;

class MedalController extends Controller
{

    public function store(Request $request, string $olympiad_id, string $area_id)
    {
        // validate input data
        $validator = Validator::make(
            $request->all(),
            [
                'gold' => 'required|integer|min:0',
                'silver' => 'required|integer|min:0',
                'bronze' => 'required|integer|min:0',
                'honorable_mention' => 'required|integer|min:0',
                'minimum_classification_score' => 'required|integer|min:0',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'status' => 400
            ], 400);
        }

        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiad_id)
        ->where('area_id', $area_id)
            ->first();

            if (!$olympiadArea) {
                return response()->json([
                    'message' => 'Olympiad Area not found',
                ], 404);
        }

        // create medal table
        $medals = OlympiadAreaMedal::create([
            'olympiad_area_id' => $olympiadArea->id,
            'gold' => $request->gold,
            'silver' => $request->silver,
            'bronze' => $request->bronze,
            'honorable_mention' => $request->honorable_mention,
            'minimum_classification_score' => $request->minimum_classification_score,
        ]);

        if (!$medals) {
            return response()->json([
                'message' => 'Error creating medal table',
                'status' => 500
            ], 500);
        }

        return response()->json([
            'message' => 'Medal table created successfully',
            'status' => 201
        ], 201);
    }

    public function show(string $olympiad_id, string $area_id)
    {
        //search medal table by olympiad_area_id
        $olympiadAreaId = OlympiadAreaMedal::whereHas('olympiadArea', function ($query) use ($olympiad_id, $area_id) {
            $query->where('olympiad_id', $olympiad_id)
                ->where('area_id', $area_id);
        })->first();

        if (!$olympiadAreaId) {
            return response()->json([
                'message' => 'Medal table not found',
                'status' => 404
            ], 404);
        }

        // get the medals
        $medals = OlympiadAreaMedal::where('olympiad_area_id', $olympiadAreaId->olympiad_area_id)
        ->first();

        return response()->json($medals, 200);
    }

    public function update(Request $request, string $id)
    {
        // validate input data
        $validator = Validator::make(
            $request->all(),
            [
                'gold' => 'required|integer|min:0',
                'silver' => 'required|integer|min:0',
                'bronze' => 'required|integer|min:0',
                'honorable_mention' => 'required|integer|min:0',
                'minimum_classification_score' => 'required|integer|min:0',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation',
                'status' => 400
            ], 400);
        }

        $medals = OlympiadAreaMedal::find($id);

        if (!$medals) {
            return response()->json([
                'message' => 'Medal table not found',
                'status' => 404
            ], 404);
        }

        // update medals
        $medals->gold = $request->gold;
        $medals->silver = $request->silver;
        $medals->bronze = $request->bronze;
        $medals->honorable_mention = $request->honorable_mention;
        $medals->minimum_classification_score = $request->minimum_classification_score;
        $medals->save();

        return response()->json([
            'message' => 'Medal table updated successfully',
            'status' => 200
        ], 200);
    }

    public function destroy(string $id)
    {
        //
    }

    public function index() {
        //
    }
}
