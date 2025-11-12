<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Message;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class UserAreaController extends Controller
{
    public function getUserAreas(Request $request, $olympiad_id): JsonResponse
    {
        $user = $request->user();

        $userRole = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->first();

        if (!$userRole) {
            return response()->json([
                'message' => 'User does not have any assigned role.',
                'status' => 404
            ], 404);
        }

        // Search olympiad active
        $userAreaOlympiad = DB::table('user_area_olympiads as uao')
            ->join('areas as a', 'uao.area_id', '=', 'a.id')
            ->join('olympiads as o', 'uao.olympiad_id', '=', 'o.id')
            ->where('uao.user_role_id', $userRole->id)
            ->where('uao.olympiad_id', $olympiad_id)
            ->select('a.id', 'a.name')
            ->distinct()
            ->get();

        if ($userAreaOlympiad->isEmpty()) {
            $data = [
                'message' => 'User is not registered in any area for this olympiad.',
                'status' => 404
            ];

            return response()->json($data, 404);
        }

        return response()->json([
            'message' => 'User areas retrieved successfully.',
            'data' => $userAreaOlympiad,
            'status' => 200
        ], 200);
    }
}
