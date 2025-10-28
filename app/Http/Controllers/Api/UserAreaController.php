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
    public function getUserAreas(Request $request): JsonResponse
    {
        $user = $request->user();

        // Search olympiad active
        $activeOlympiad = DB::table('olympiads')
            ->where('olympiads.status', 'Activa')
            ->first();

        if (!$activeOlympiad) {
            $data = [
                'message' => 'There are no active Olympiads at the moment.',
                'status' => 404
            ];

            return response()->json($data, 404);
        }

        // Get the user areas in the active olympiad
        $areas = DB::table('areas as a')
            ->join('user_areas as ua', 'ua.area_id', '=', 'a.id')
            ->join('olympiad_areas as oa', 'oa.area_id', '=', 'a.id')
            ->where('ua.user_id', $user->id)
            ->where('oa.olympiad_id', $activeOlympiad->id)
            ->select('a.id', 'a.name', 'oa.olympiad_id')
            ->get();

        // if no areas found for the user
        if ($areas->isEmpty()) {

            $data = [
                'message' => 'No registered areas were found for this user in the active Olympiad.',
                'status' => 404
            ];

            return response()->json($data, 404);
        }

        $data = [
            'id_user' => $user->id,
            'olympiad' => $activeOlympiad->name,
            'olympiad_id' => $activeOlympiad->id,
            'areas' => $areas,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
