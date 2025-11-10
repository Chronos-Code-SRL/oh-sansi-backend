<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;

class ContestantController extends Controller
{
    public function showContestant(string $phase_id, string $olympiad_id, string $area_id, string $level_id): JsonResponse
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
            ->where('levels.id', $level_id)
            ->whereColumn('olympiad_area_phases.olympiad_area_id', 'olympiad_areas.id')
            ->distinct()
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

    public function showContestantsOlympiad(): JsonResponse
    {

        $activeOlympiad = DB::table('olympiads')
            ->where('status', '=', 'Activa')
            ->first();

        if (!$activeOlympiad) {
            return response()->json([
                'message' => 'There are no active Olympiads currently',
                'status' => 404
            ], 404);
        }

        $contestantsOlympiad = DB::table('contestants')
            ->join('registrations', 'registrations.contestant_id', '=', 'contestants.id')
            ->join('evaluations', 'evaluations.registration_id', '=', 'registrations.id')
            ->join('olympiad_areas', 'registrations.olympiad_area_id', '=', 'olympiad_areas.id')
            ->join('areas', 'olympiad_areas.area_id', '=', 'areas.id')
            ->leftJoin('contestant_level_grades', 'contestant_level_grades.contestant_id', '=', 'contestants.id')
            ->leftJoin('level_grades', 'contestant_level_grades.level_grade_id', '=', 'level_grades.id')
            ->leftJoin('grades', 'level_grades.grade_id', '=', 'grades.id')
            ->leftJoin('levels', 'level_grades.level_id', '=', 'levels.id')
            ->where('olympiad_areas.olympiad_id', $activeOlympiad->id)
            ->select(
                'contestants.id AS contestant_id',
                'evaluations.id AS evaluation_id',
                'contestants.first_name',
                'contestants.last_name',
                'contestants.ci_document',
                'contestants.gender',
                'contestants.department',
                'evaluations.score',
                'evaluations.status',
                'areas.name AS area_name',
                'grades.name AS grade_name',
                'levels.name AS level_name'
            )
            ->get();

        if ($contestantsOlympiad->isEmpty()) {
            return response()->json([
                'message' => 'No contestants found for this Olympiad',
                'status' => 404
            ], 404);
        }

        $result = $contestantsOlympiad->map(function ($item) {
            return [
                'contestant_id' => $item->contestant_id,
                'evaluation_id' => $item->evaluation_id,
                'first_name' => $item->first_name,
                'last_name' => $item->last_name,
                'ci_document' => $item->ci_document,
                'gender' => $item->gender,
                'department' => $item->department,
                'score' => $item->score,
                'status' => (bool)$item->status,
                'area_name' => $item->area_name,
                'grade_name' => $item->grade_name,
                'level_name' => $item->level_name,
            ];
        });

        return response()->json($result, 200);
    }

    /**
     * Get ranked contestants for a specific phase/olympiad/area/level
     * Estado rules:
     *  - "Clasificado": score >= score_cut (or olympiad default)
     *  - "Desclasificado": score < score_cut
     *  - "Descalificado": score is null OR has a special observation (description not empty)
     */
    public function getRankedContestants(string $phase_id, string $olympiad_id, string $area_id, string $level_id): JsonResponse
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
                'levels.name AS level_name',
                'olympiad_area_phase_level_grades.score_cut AS score_cut',
                'olympiads.default_score_cut AS olympiad_default_score_cut'
            )
            ->join('registrations', 'evaluations.registration_id', '=', 'registrations.id')
            ->join('contestants', 'registrations.contestant_id', '=', 'contestants.id')
            ->leftJoin('contestant_level_grades', 'contestants.id', '=', 'contestant_level_grades.contestant_id')
            ->leftJoin('level_grades', 'contestant_level_grades.level_grade_id', '=', 'level_grades.id')
            ->leftJoin('grades', 'level_grades.grade_id', '=', 'grades.id')
            ->leftJoin('levels', 'level_grades.level_id', '=', 'levels.id')
            ->join('olympiad_area_phases', 'evaluations.olympiad_area_phase_id', '=', 'olympiad_area_phases.id')
            ->join('olympiad_areas', 'registrations.olympiad_area_id', '=', 'olympiad_areas.id')
            ->leftJoin('olympiads', 'olympiad_areas.olympiad_id', '=', 'olympiads.id')
            // join score cuts matching both the olympiad_area_phase and the contestant's level_grade
            ->leftJoin('olympiad_area_phase_level_grades', function ($join) {
                $join->on('olympiad_area_phase_level_grades.olympiad_area_phase_id', '=', 'olympiad_area_phases.id')
                    ->on('olympiad_area_phase_level_grades.level_grade_id', '=', 'contestant_level_grades.level_grade_id');
            })
            ->where('olympiad_areas.olympiad_id', $olympiad_id)
            ->where('olympiad_areas.area_id', $area_id)
            ->where('olympiad_area_phases.phase_id', $phase_id)
            ->where('levels.id', $level_id)
            ->whereColumn('olympiad_area_phases.olympiad_area_id', 'olympiad_areas.id')
            ->distinct()
            ->get();

        if ($contestants->isEmpty()) {
            $data = [
                'message' => 'Error in recovering competitors',
                'status' => 404
            ];

            return response()->json($data, 404);
        }

        $result = $contestants->map(function ($item) {
            $score = $item->score;
            $desc = trim((string)($item->description ?? ''));

            // Prefer specific score_cut, fallback to olympiad default, otherwise null
            $scoreCut = null;
            if (!is_null($item->score_cut)) {
                $scoreCut = $item->score_cut;
            } elseif (!is_null($item->olympiad_default_score_cut)) {
                $scoreCut = $item->olympiad_default_score_cut;
            }

            // Determine estado
            if (is_null($score) || $desc !== '') {
                $estado = 'Descalificado';
            } else {
                if (is_null($scoreCut)) {
                    // If no score cut is configured, treat as Desclasificado by default when score exists
                    $estado = 'Desclasificado';
                } else {
                    // compare numerically
                    $estado = ((float)$score >= (float)$scoreCut) ? 'Clasificado' : 'Desclasificado';
                }
            }

            return [
                'contestant_id' => $item->contestant_id,
                'evaluation_id' => $item->evaluation_id,
                'first_name' => $item->first_name,
                'last_name' => $item->last_name,
                'gender' => $item->gender,
                'ci_document' => $item->ci_document,
                'school_name' => $item->school_name,
                'department' => $item->department,
                'grade_name' => $item->grade_name,
                'level_name' => $item->level_name,
                'score' => $item->score,
                'score_cut' => $scoreCut,
                'description' => $item->description,
                'estado' => $estado,
                'status' => (bool)$item->status,
            ];
        });

        return response()->json($result);
    }
}
