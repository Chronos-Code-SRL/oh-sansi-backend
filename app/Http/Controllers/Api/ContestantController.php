<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Evaluation;

class ContestantController extends Controller
{
    public function showContestant(string $phase_id, string $olympiad_id, string $area_id): JsonResponse
    {

        $contestants = Evaluation::query()
            ->select(
                'evaluations.id AS evaluation_id',
                'contestants.id AS contestant_id',
                'contestants.first_name',
                'contestants.last_name',
                'contestants.gender',
                'contestants.ci_document',
                'contestants.school_name',
                'contestants.department',
                'evaluations.score',
                'evaluations.description',
                'evaluations.status',
                'grades.name AS grade_name',
                'levels.name AS level_name'
            )
            ->join('registrations', 'evaluations.registration_id', '=', 'registrations.id')
            ->join('contestants', 'registrations.contestant_id', '=', 'contestants.id')
            ->leftJoin('contestant_level_grades', 'contestants.id', '=', 'contestant_level_grades.contestant_id')
            ->leftJoin('level_grades', 'contestant_level_grades.level_grade_id', '=', 'level_grades.id')
            ->leftJoin('grades', 'level_grades.grade_id', '=', 'grades.id')
            ->leftJoin('levels', 'level_grades.level_id', '=', 'levels.id')
            ->join('olympiad_area_phases', 'evaluations.olympiad_area_phase_id', '=', 'olympiad_area_phases.id')
            ->join('olympiad_areas', 'registrations.olympiad_area_id', '=', 'olympiad_areas.id')
            ->where('olympiad_areas.olympiad_id', $olympiad_id)
            ->where('olympiad_areas.area_id', $area_id)
            ->where('olympiad_area_phases.phase_id', $phase_id)
            ->get();

        if ($contestants->isEmpty()) {
            $data = [
                'message' => 'Error in recovering competitors',
                'status' => 404
            ];

            return response()->json($data, 404);
        }

        $result = $contestants->map(function ($item) {
            return [
                'contestant_id' => $item->contestant_id,
                'first_name' => $item->first_name,
                'last_name' => $item->last_name,
                'gender' => $item->gender,
                'ci_document' => $item->ci_document,
                'school_name' => $item->school_name,
                'department' => $item->department,
                'grade_name' => $item->grade_name,
                'level_name' => $item->level_name,
                'score' => $item->score,
                'description' => $item->description,
                'status' => (bool)$item->status,
            ];
        });

        return response()->json($result);
    }
}