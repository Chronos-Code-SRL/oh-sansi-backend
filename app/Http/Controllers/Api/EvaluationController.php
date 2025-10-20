<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Evaluation;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EvaluationController extends Controller
{
    public function registerEvaluation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|integer',
            'description' => 'required|string',
            'registration_id' => 'required',
            'olympiad_area_phase_id' => 'required',
        ]);
        //If validation fails
        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        //create evaluation
        $evaluation = Evaluation::create([
            'score' => $request->score,
            'description' => $request->description,
        ]);
        //if evaluation not created
        if (!$evaluation) {
            $data = [
                'message' => 'Error registering evaluation',
                'status' => 500
            ];
            return response()->json($data, 500);
        }
        //message of successfull registration
        $data = [
            'message' => 'Evaluation registered successfully',
            'status' => 201
        ];

        return response()->json($data, 201);
    }
    
    
    public function updateEvaluation(Request $request, $id): JsonResponse
    {
        // search evaluation by id
        $evaluation = Evaluation::find($id);
        
        if(!$evaluation){
            $data = [
                'message' => 'Evaluation not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        
        $validator = Validator::make($request->all(), [
            'score' => 'integer',
            'description' => 'string',
        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        
        //update evaluation
        $evaluation->score = $request->score;
        $evaluation->description = $request->description;
        $evaluation->save();
        $data = [
            'message' => 'Evaluation updated successfully',
            'status' => 200
        ];
        
        return response()->json($data, 200);
    }   
    
    public function updatePartialEvaluation(Request $request, $id): JsonResponse
    {
        // search evaluation by id
        $evaluation = Evaluation::find($id);
        
        if(!$evaluation){
            $data = [
                'message' => 'Evaluation not found',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $validator = Validator::make($request->all(), [
            'score' => 'integer',
            'description' => 'string',
        ]);
        
        //If validation fails
        if ($validator->fails()) {
            $data = [
                'message' => 'Error in data validation',
                'error' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        
        if ($request->has('score')) {
            $evaluation->score = $request->score;
        }
        
        if ($request->has('description')) {
            $evaluation->description = $request->description;
        }
        
        $evaluation->save();
        
        $data = [
            'message' => 'Evaluation updated successfully',
            'status' => 200
        ];
        
        return response()->json($data, 200);
    }
    
    public function checksUpdates($lastUpdateAt): JsonResponse
    {
        $lastUpdate = Carbon::parse($lastUpdateAt);

        $updatedEvaluations = Evaluation::where('updated_at', '>', $lastUpdate)
        ->orderBy('updated_at', 'asc')
        ->get();

        $maxUpdatedAt = $updatedEvaluations->max('updated_at') ?? $lastUpdate;

        $data = [
            'new_evaluations' => $updatedEvaluations,
            'last_updated_at' => $maxUpdatedAt,
            'status' => 200
        ];
        
        return response()->json($data, 200);
    }
}
