<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Contestant;
use App\Models\Registration;
use App\Models\OlympiadArea;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;

class ContestantController extends Controller
{
    public function showContestant(string $phase_id, string $olympiad_id, string $area_id): JsonResponse
    {

        $evaluations = Evaluation::with([
            'registration.contestant',
        ])
            ->whereHas('olympiadAreaPhase', function ($query) use ($phase_id, $area_id, $olympiad_id) {
                $query->when($phase_id, fn($q) => $q->where('phase_id', $phase_id))
                    ->whereHas('olympiadArea', function ($q) use ($area_id, $olympiad_id) {
                        $q->when($area_id, fn($q2) => $q2->where('area_id', $area_id))
                            ->when($olympiad_id, fn($q2) => $q2->where('olympiad_id', $olympiad_id));
                    });
            })
            ->get();

        $result = $evaluations->map(function ($e) {
            $contestant = $e->registration->contestant;

            return [
                'contestant_id' => $contestant->id,
                'first_name' => $contestant->first_name,
                'gender' => $contestant->gender,
                'last_name' => $contestant->last_name,
                'ci_document' => $contestant->ci_document,
                'school_name' => $contestant->school_name,
                'department' => $contestant->department,
                
                'score' => $e->score,
                'description' => $e->description,
                'status' => (bool)$e->status,
            ];
        });

        return response()->json($result);
    }
}
