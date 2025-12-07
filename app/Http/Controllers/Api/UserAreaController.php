<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Message;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\UserRole;

class UserAreaController extends Controller
{
    public function getUserAreas(Request $request, $olympiad_id): JsonResponse
    {
        $user = $request->user();

        $roles = UserRole::with(['role', 'areas' => function ($q) use ($olympiad_id) {
            $q->where('user_area_olympiads.olympiad_id', $olympiad_id);
        }])
        ->where('user_id', $user->id)
        ->get();

        if ($roles->isEmpty()) {
            return response()->json([
                'message' => 'User does not have any assigned role.',
                'status' => 404
            ], 404);
        }

        $data = $roles->map(function ($ur) {
            return [
                'role_id'   => $ur->role->id,
                'role_name' => $ur->role->name,
                'areas'     => $ur->areas->map(fn($a) => [
                    'id'   => $a->id,
                    'name' => $a->name
                ])
            ];
        });

        return response()->json([
            'message' => 'User areas retrieved successfully.',
            'data' => $data,
            'status' => 200
        ], 200);
    }
}
