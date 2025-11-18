<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use App\Models\Contestant;

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
                'levels.name AS level_name',
                'evaluations.classification_status',
                'evaluations.classification_place'
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
                'description' => $item->description,
                'status' => (bool)$item->status,
                'classification_status' => $item->classification_status,
                'classification_place' => $item->classification_place,
            ];
        });

        return response()->json($result);
    }

    public function showContestantsOlympiad($olympiad_id): JsonResponse
    {

        $contestantsOlympiad = DB::table('contestants')
            ->join('registrations', 'registrations.contestant_id', '=', 'contestants.id')
            ->join('evaluations', 'evaluations.registration_id', '=', 'registrations.id')
            ->join('olympiad_areas', 'registrations.olympiad_area_id', '=', 'olympiad_areas.id')
            ->join('areas', 'olympiad_areas.area_id', '=', 'areas.id')
            ->leftJoin('contestant_level_grades', 'contestant_level_grades.contestant_id', '=', 'contestants.id')
            ->leftJoin('level_grades', 'contestant_level_grades.level_grade_id', '=', 'level_grades.id')
            ->leftJoin('grades', 'level_grades.grade_id', '=', 'grades.id')
            ->leftJoin('levels', 'level_grades.level_id', '=', 'levels.id')
            ->where('olympiad_areas.olympiad_id', $olympiad_id)
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

            // Determine applied score cut (threshold): prefer specific score_cut, then olympiad default, then fallback 51
            $scoreCut = $item->score_cut ?? $item->olympiad_default_score_cut ?? 51;

            // Determine estado
            if (is_null($score) || $desc !== '') {
                $estado = 'Descalificado';
            } else {
                // compare numerically using the applied threshold
                $estado = ((float)$score >= (float)$scoreCut) ? 'Clasificado' : 'Desclasificado';
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

    public function countsByAreaPhaseLevel(string $olympiad_id, string $area_id, string $phase_id, string $level_id)
    {
        $competitorsCount = DB::table('contestants as c')
            ->join('contestant_level_grades as clg', 'c.id', '=', 'clg.contestant_id')
            ->join('level_grades as lg', 'clg.level_grade_id', '=', 'lg.id')
            ->join('registrations as r', 'c.id', '=', 'r.contestant_id')
            ->join('olympiad_areas as oa', 'r.olympiad_area_id', '=', 'oa.id')
            ->join('olympiad_area_phases as oap', 'oa.id', '=', 'oap.olympiad_area_id')

            ->where('oa.olympiad_id', $olympiad_id)
            ->where('oa.area_id', $area_id)
            ->where('oap.phase_id', $phase_id)
            ->where('lg.level_id', $level_id);

        $total = $competitorsCount->count(DB::raw('DISTINCT c.id'));

        $evaluatedQuery = (clone $competitorsCount)
            ->leftJoin('evaluations as e', 'e.registration_id', '=', 'r.id')
            ->select(DB::raw('COUNT(DISTINCT c.id)'));
        
        $classified = (clone $evaluatedQuery)
            ->where('e.classification_status', 'clasificado')
            ->first()->count;
            
        $disclassified = (clone $evaluatedQuery)
            ->where('e.classification_status', 'no_clasificado')
            ->first()->count;
            
        $disqualified = (clone $evaluatedQuery)
            ->where(function ($query) {
                $query->where('e.classification_status', 'descalificado') // Es 'descalificado'
                    ->orWhereNull('e.classification_status');          // O es NULL (aún no evaluado)
            })
            ->first()->count;
            
        return response()->json([
            'total' => $total,
            'classified' => $classified,
            'no_classified' => $disclassified,
            'disqualified' => $disqualified,
        ]);
    }

    public function getAwardWinningContestants(string $olympiad_id, string $area_id, string $level_id)
    {
        $lastPhaseId = DB::table('olympiad_area_phases AS oap')
            ->join('olympiad_areas AS oa', 'oap.olympiad_area_id', '=', 'oa.id')
            ->join('phases AS p', 'oap.phase_id', '=', 'p.id')
            ->join('olympiad_area_phase_level_grades AS oapl', 'oapl.olympiad_area_phase_id', '=', 'oap.id')
            ->join('level_grades AS lg', 'lg.id', '=', 'oapl.level_grade_id')
            ->where('oa.olympiad_id', $olympiad_id)
            ->where('oa.area_id', $area_id)
            ->where('lg.level_id', $level_id)
            ->where('oapl.status', 'Terminada') // para avalar por nivel
            // ->where('oap.status', 'Terminada') para avalar por area
            ->orderByDesc('p.order')
            ->select('oap.id')
            ->first();

        if (!$lastPhaseId) {
            return response()->json([
                'message' => 'No phases found for the specified olympiad and area',
            ], 404);
        }

        $listWinners = DB::table('evaluations AS e')
            ->join('registrations AS r', 'e.registration_id', '=', 'r.id')
            ->join('contestants AS c', 'r.contestant_id', '=', 'c.id')
            ->join('contestant_level_grades AS clg', 'clg.contestant_id', '=', 'c.id')
            ->join('level_grades AS lg', 'clg.level_grade_id', '=', 'lg.id')
            ->join('olympiad_areas AS oa', 'r.olympiad_area_id', '=', 'oa.id')
            ->join('areas AS a', 'oa.area_id', '=', 'a.id')
            ->join('levels AS l', 'lg.level_id', '=', 'l.id')
            ->where('e.olympiad_area_phase_id', $lastPhaseId->id)
            ->where('lg.level_id', $level_id)
            ->where('e.classification_place', '!=', null)
            ->select(
                'c.id AS contestant_id',
                'c.first_name',
                'c.last_name',
                'c.school_name',
                'c.ci_document',
                'a.name AS area_name',
                'l.name AS level_name',
                'e.score',
                'e.id AS evaluation_id',
                'e.classification_place'
            )
            ->orderBy('e.classification_place')
            ->get();

        return response()->json($listWinners, 200);
    }

    public function getContestantsClassifieds(string $olympiad_id, string $area_id, string $phase_id, string $level_id)
    {
        $classifieds = Contestant::select(
            'contestants.first_name as first_name',
            'contestants.last_name as last_name',
            'contestants.ci_document as ci_document',
            'contestants.grade as grade',
            'e.classification_status as classification_status',
            'e.score as score'
        )
        ->join('registrations as r', 'r.contestant_id', '=', 'contestants.id')
        ->join('evaluations as e', 'e.registration_id', '=', 'r.id')
        ->join('olympiad_area_phases as oap', 'e.olympiad_area_phase_id', '=', 'oap.id')
        ->join('olympiad_areas as oa', 'oap.olympiad_area_id', '=', 'oa.id')
        ->join('contestant_level_grades as clg', 'clg.contestant_id', '=', 'contestants.id')
        ->join('level_grades as lg', 'lg.id', '=', 'clg.level_grade_id')
        ->join('olympiad_area_phase_level_grades AS oapl', 'oapl.olympiad_area_phase_id', '=', 'oap.id')
        ->where('oa.olympiad_id', $olympiad_id)
        ->where('oa.area_id', $area_id)
        ->where('oap.phase_id', $phase_id)
        ->where('oapl.status', 'Terminada') // para avalar por nivel
        // ->where('oap.status', 'Terminada') para avalar por area
        ->where('lg.level_id', $level_id)
        ->distinct()
        ->get();
        
        if ($classifieds->isEmpty()) {
            return response()->json([
                'message' => 'No classified contestants found',
                'status' => 404
            ], 404);
        }

        return response()->json($classifieds, 200);
    }

    public function getAwardWinningContestantsArea(string $olympiad_id, string $area_id)
    {

        $olympiadAreaId = DB::table('olympiad_areas')
            ->where('olympiad_id', $olympiad_id)
            ->where('area_id', $area_id)
            ->value('id');

        $cantLevelArea = DB::table('level_grades')
            ->where('olympiad_area_id', $olympiadAreaId)
            ->distinct('level_id')
            ->count('level_id');

        $levelIds = DB::table('level_grades')
            ->where('olympiad_area_id', $olympiadAreaId)
            ->distinct()
            ->pluck('level_id');

        $cont = 0;

        for ($i=0; $i < $cantLevelArea; $i++) { 
            if ($this->checkLevel($olympiad_id, $area_id, $levelIds[$i]) !== null) {
                $cont++;
            }
        }
        
        if ($cont !== $cantLevelArea) {
            return response()->json([
                'message' => 'levels not fully endorsed',
            ], 404);
        }

        $lastPhase = $lastPhase = DB::table('olympiad_area_phases AS oap')
            ->join('phases AS p', 'oap.phase_id', '=', 'p.id')
            ->where('oap.olympiad_area_id', $olympiadAreaId)
            ->orderByDesc('p.order')
            ->select('oap.id')
            ->first();

        $results = DB::table('evaluations AS e')
            ->join('registrations AS r', 'e.registration_id', '=', 'r.id')
            ->join('contestants AS c', 'r.contestant_id', '=', 'c.id')
            ->join('contestant_level_grades AS clg', 'clg.contestant_id', '=', 'c.id')
            ->join('level_grades AS lg', 'clg.level_grade_id', '=', 'lg.id')
            ->join('levels AS l', 'lg.level_id', '=', 'l.id')
            ->join('olympiad_areas AS oa', 'r.olympiad_area_id', '=', 'oa.id')
            ->join('areas AS a', 'oa.area_id', '=', 'a.id')
            ->where('e.olympiad_area_phase_id', $lastPhase->id)
            ->where('oa.olympiad_id', $olympiad_id)
            ->where('oa.area_id', $area_id)
            ->select(
                'c.first_name AS nombre',
                'c.last_name AS apellido',
                'c.school_name AS unidad_educativa',
                'a.name AS nombre_area',
                'c.first_name AS departamento',
                'l.name AS nombre_nivel',
                'e.classification_place AS lugar'
            )

            ->orderByRaw("CASE 
                WHEN e.classification_place = 'Oro' THEN 1
                WHEN e.classification_place = 'Plata' THEN 2
                WHEN e.classification_place = 'Bronce' THEN 3
                ELSE 4
            END")

            ->get();

        return response()->json([
            'contestants' => $results
        ], 200);
    }

    private function checkLevel(string $olympiad_id, string $area_id, string $level_id)
    {
        $lastPhaseId = DB::table('olympiad_area_phases AS oap')
            ->join('olympiad_areas AS oa', 'oap.olympiad_area_id', '=', 'oa.id')
            ->join('phases AS p', 'oap.phase_id', '=', 'p.id')
            ->join('olympiad_area_phase_level_grades AS oapl', 'oapl.olympiad_area_phase_id', '=', 'oap.id')
            ->join('level_grades AS lg', 'lg.id', '=', 'oapl.level_grade_id')
            ->where('oa.olympiad_id', $olympiad_id)
            ->where('oa.area_id', $area_id)
            ->where('lg.level_id', $level_id)
            ->where('oapl.status', 'Terminada') // para avalar por nivel
            // ->where('oap.status', 'Terminada') para avalar por area
            ->orderByDesc('p.order')
            ->select('oap.id')
            ->first();
        
        if (!$lastPhaseId) {
            return null;
        }

        return $lastPhaseId;
    }
}
