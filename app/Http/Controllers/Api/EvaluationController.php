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
     /**
     * @OA\Post(
     *     path="/api/evaluations",
     *     summary="Registrar una evaluación",
     *     description="Registra una nueva evaluación con puntaje y descripción.",
     *     tags={"Evaluations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"score", "description", "registration_id", "olympiad_area_phase_id"},
     *             @OA\Property(property="score", type="integer", example=85),
     *             @OA\Property(property="description", type="string", example="Buen desempeño general"),
     *             @OA\Property(property="registration_id", type="integer", example=1),
     *             @OA\Property(property="olympiad_area_phase_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evaluación registrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Evaluation registered successfully"),
     *             @OA\Property(property="status", type="integer", example=201)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la validación de datos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al registrar la evaluación"
     *     )
     * )
     */
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
    
    /**
     * @OA\Put(
     *     path="/api/evaluations/{id}",
     *     summary="Actualizar una evaluación",
     *     description="Actualiza completamente una evaluación existente.",
     *     tags={"Evaluations"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID de la evaluación",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="score", type="integer", example=90),
     *             @OA\Property(property="description", type="string", example="Evaluación actualizada")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Evaluación actualizada correctamente"),
     *     @OA\Response(response=400, description="Error en la validación"),
     *     @OA\Response(response=404, description="Evaluación no encontrada")
     * )
     */
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
    
    /**
     * @OA\Patch(
     *     path="/api/evaluations/{id}",
     *     summary="Actualizar parcialmente una evaluación",
     *     description="Permite actualizar solo algunos campos de una evaluación.",
     *     tags={"Evaluations"},
     *     @OA\Parameter(
     *         name="id",
     *         description="ID de la evaluación",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="score", type="integer", example=95),
     *             @OA\Property(property="description", type="string", example="Actualización parcial")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Evaluación actualizada parcialmente"),
     *     @OA\Response(response=400, description="Error en la validación"),
     *     @OA\Response(response=404, description="Evaluación no encontrada")
     * )
     */
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
    
    /**
     * @OA\Get(
     *     path="/api/evaluations/checks-updates/{lastUpdateAt}",
     *     summary="Verificar actualizaciones",
     *     description="Obtiene las evaluaciones actualizadas después de una fecha específica.",
     *     tags={"Evaluations"},
     *     @OA\Parameter(
     *         name="lastUpdateAt",
     *         description="Fecha y hora de la última actualización (formato ISO 8601)",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string", example="2025-10-20T10:00:00Z")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluaciones actualizadas encontradas",
     *         @OA\JsonContent(
     *             @OA\Property(property="new_evaluations", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="last_updated_at", type="string", example="2025-10-20T13:45:00Z"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     )
     * )
     */
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
