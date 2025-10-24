<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Evaluation;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EvaluationController extends Controller
{
    
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
            'score' => 'integer|nullable',
            'description' => 'string|nullable',
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
            $evaluation->status = true;
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
    
    public function checksUpdates(Request $request): JsonResponse
    {
        
        $lastUpdateAt = $request->query('lastUpdateAt');
        $lastUpdate = $lastUpdateAt 
            ? Carbon::parse($lastUpdateAt) 
            : Carbon::createFromTimestamp(0);

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
