<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Contestant;

class ContestantController extends Controller
{
    // Get contestants for a specific phase, olympiad, area and level
    public function showContestant(string $phase_id, string $olympiad_id, string $area_id, string $level_id): JsonResponse
    {
        // Complex query to get contestants with evaluations and grade info
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
            ->orderBy('contestants.last_name', 'ASC')
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

    // Get all contestants for a specific olympiad with basic info
    public function showContestantsOlympiad($olympiad_id): JsonResponse
    {
        // Get contestants with evaluation data for entire olympiad
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
     * Get ranked contestants for a specific phase/olympiad/area/level with classification logic
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

            // Determine classification status based on score and description
            if (is_null($score) || $desc !== '') {
                $estado = 'Descalificado';
            } else {
                // Compare score against threshold numerically
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

    // Get statistics counts for contestants by area, phase and level
    public function countsByAreaPhaseLevel(string $olympiad_id, string $area_id, string $phase_id, string $level_id)
    {
        // Base query for all competitors in this level
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

        // Count total unique competitors
        $total = $competitorsCount->count(DB::raw('DISTINCT c.id'));

        // Query for evaluated competitors with classification status
        $evaluatedQuery = (clone $competitorsCount)
            ->leftJoin('evaluations as e', 'e.registration_id', '=', 'r.id')
            ->select(DB::raw('COUNT(DISTINCT c.id)'));

        // Count classified competitors
        $classified = (clone $evaluatedQuery)
            ->where('e.classification_status', 'clasificado')
            ->first()->count;

        // Count not classified competitors
        $disclassified = (clone $evaluatedQuery)
            ->where('e.classification_status', 'no_clasificado')
            ->first()->count;

        // Count disqualified (descalificado) or not yet evaluated (NULL status)
        $disqualified = (clone $evaluatedQuery)
            ->where(function ($query) {
                $query->where('e.classification_status', 'descalificado') // It is 'descalificado'
                    ->orWhereNull('e.classification_status');          // Or NULL (not yet evaluated)
            })
            ->first()->count;

        return response()->json([
            'total' => $total,
            'classified' => $classified,
            'no_classified' => $disclassified,
            'disqualified' => $disqualified,
        ]);
    }

    // Get contestants eligible for awards in the final phase
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
            ->where('oapl.status', 'Terminada')
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
            ->where('e.classification_status', 'clasificado')
            ->where('e.olympiad_area_phase_id', $lastPhaseId->id)
            ->where('lg.level_id', $level_id)
            ->select(
                'c.id AS contestant_id',
                'c.first_name',
                'c.last_name',
                'c.school_name',
                'c.ci_document',
                'c.department',
                'c.tutor_name',
                'a.name AS area_name',
                'l.name AS level_name',
                'e.score',
                'e.id AS evaluation_id',
                'e.classification_place'
            )
            ->orderBy('e.score', 'desc')
            ->get();

        return response()->json($listWinners, 200);
    }

    // Get classified contestants for a specific phase/area/level
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

    // Get award winners for entire area (all levels must be endorsed)
    public function getAwardWinningContestantsArea(string $olympiad_id, string $area_id)
    {
        // Get olympiad area ID
        $olympiadAreaId = DB::table('olympiad_areas')
            ->where('olympiad_id', $olympiad_id)
            ->where('area_id', $area_id)
            ->value('id');

        // Count total levels in this area
        $cantLevelArea = DB::table('level_grades')
            ->where('olympiad_area_id', $olympiadAreaId)
            ->distinct('level_id')
            ->count('level_id');

        $levelIds = DB::table('level_grades')
            ->where('olympiad_area_id', $olympiadAreaId)
            ->distinct()
            ->pluck('level_id');

        // Check how many levels are fully endorsed
        $cont = 0;

        for ($i=0; $i < $cantLevelArea; $i++) {
            if ($this->checkLevel($olympiad_id, $area_id, $levelIds[$i]) !== null) {
                $cont++;
            }
        }

        // All levels must be endorsed to proceed
        if ($cont !== $cantLevelArea) {
            return response()->json([
                'message' => 'levels not fully endorsed',
            ], 404);
        }

        // Get the last completed phase for awards
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
                'c.first_name AS first_name',
                'c.last_name AS last_name',
                'c.school_name AS school_name',
                'a.name AS area_name',
                'c.department AS department',
                'l.name AS level_name',
                'e.classification_place AS classification_place'
            )
            ->distinct()
            ->get();

        return response()->json([
            'contestants' => $results
        ], 200);
    }

    // Helper method to check if a level is fully endorsed
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
            ->where('oapl.status', 'Terminada') // To endorse by level
            // ->where('oap.status', 'Terminada') to endorse by area
            ->orderByDesc('p.order')
            ->select('oap.id')
            ->first();

        if (!$lastPhaseId) {
            return null;
        }

        return $lastPhaseId;
    }

    // Apply medal/award classification to competitors based on their ranking
    public function competitorsReadjustment(Request $request, string $olympiadId, string $areaId, string $levelId)
    {
        $lastPhaseId = DB::table('olympiad_area_phases AS oap')
            ->join('olympiad_areas AS oa', 'oap.olympiad_area_id', '=', 'oa.id')
            ->join('phases AS p', 'oap.phase_id', '=', 'p.id')
            ->join('olympiad_area_phase_level_grades AS oapl', 'oapl.olympiad_area_phase_id', '=', 'oap.id')
            ->join('level_grades AS lg', 'lg.id', '=', 'oapl.level_grade_id')
            ->where('oa.olympiad_id', $olympiadId)
            ->where('oa.area_id', $areaId)
            ->where('lg.level_id', $levelId)
            ->where('oapl.status', 'Terminada')
            ->orderByDesc('p.order')
            ->select('oap.id')
            ->first();

        if (!$lastPhaseId) {
            return response()->json([
                'message' => 'No phases found for the specified olympiad and area',
            ], 404);
        }

        // Validate medal distribution input
        $validator = Validator::make($request->all(), [
            'gold' => 'required|string',
            'silver' => 'required|string',
            'bronze' => 'required|string',
            'honorable_mention' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in data validation'
            ], 404);
        }

        // Define medal positions and their quantities
        $positions = [
            'Oro' => $request->gold,
            'Plata' => $request->silver,
            'Bronce' => $request->bronze,
            'Mención honorífica' => $request->honorable_mention,
        ];

        // Get competitors ordered by score for medal assignment
        $competitors = DB::table('evaluations AS e')
            ->join('registrations AS r', 'e.registration_id', '=', 'r.id')
            ->join('contestants AS c', 'r.contestant_id', '=', 'c.id')
            ->join('contestant_level_grades AS clg', 'clg.contestant_id', '=', 'c.id')
            ->join('level_grades AS lg', 'lg.id', '=', 'clg.level_grade_id')
            ->join('olympiad_areas AS oa', 'r.olympiad_area_id', '=', 'oa.id')
            ->where('e.olympiad_area_phase_id', $lastPhaseId->id)
            ->where('oa.olympiad_id', $olympiadId)
            ->where('oa.area_id', $areaId)
            ->where('lg.level_id', $levelId)
            ->where ('e.classification_status', 'clasificado')
            ->orderByDesc('e.score')
            ->select('e.id AS evaluation_id', 'e.score')
            ->get();

        $numberMedals = $request->gold + $request->silver + $request->bronze + $request->honorable_mention;
        $totalCompetitors = $competitors->count();

        // Validate that medal count doesn't exceed competitor count
        if ($numberMedals > $totalCompetitors) {
            return response()->json([
                'message' => 'La cantidad de medallas no puede ser mayor que el número de competidores premiados',
            ], 422);
        }

        $index = 0;

        DB::beginTransaction();

        try {
            // Assign medals based on ranking order
            foreach ($positions as $medal => $count) {
                for ($i = 0; $i < $count; $i++) {
                    if (!isset($competitors[$index])) break;

                    DB::table('evaluations')
                        ->where('id', $competitors[$index]->evaluation_id)
                        ->update([
                            'classification_place' => $medal,
                        ]);
                    $index++;
                }
            }

            // Clear medal positions for remaining competitors
            for ($j = $index; $j < $competitors->count(); $j++) {
                DB::table('evaluations')
                    ->where('id', $competitors[$j]->evaluation_id)
                    ->update([
                        'classification_place' => null,
                    ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal Server Error'
            ], 500);
        }

        return response()->json([
            'message' => 'Clasificación de medallero aplicada correctamente'
        ], 200);
    }

    // Get list of competitors for certificate generation
    public function getListCompetitorsCertificate(string $olympiadId, string $areaId)
    {
        $olympiadAreaId = DB::table('olympiad_areas')
        ->where('olympiad_id', $olympiadId)
        ->where('area_id', $areaId)
        ->value('id');

        if (!$olympiadAreaId) {
            return response()->json([
                'message' => 'Olympiad and area not found'
            ], 404);
        }

        $levelIds = DB::table('level_grades')
            ->where('olympiad_area_id', $olympiadAreaId)
            ->distinct()
            ->pluck('level_id');

        $lastPhase = DB::table('olympiad_area_phases AS oap')
        ->join('phases AS p', 'oap.phase_id', '=', 'p.id')
        ->where('oap.olympiad_area_id', $olympiadAreaId)
        ->orderByDesc('p.order')
        ->select('oap.id')
        ->first();

    if (!$lastPhase) {
        return response()->json([
            'message' => 'This area has no phases (not in last phase)',
        ], 404);
    }

    foreach ($levelIds as $levelId) {
        // Check if this level is endorsed (Terminada status)
        $endorsed = DB::table('olympiad_area_phase_level_grades as oapl')
            ->join('level_grades as lg', 'oapl.level_grade_id', '=', 'lg.id')
            ->where('oapl.olympiad_area_phase_id', $lastPhase->id)
            ->where('lg.level_id', $levelId)
            ->where('lg.olympiad_area_id', $olympiadAreaId)
            ->where('oapl.status', 'Terminada')
            ->exists();

        if (!$endorsed) {
            return response()->json([
                'message' => "Los niveles del area no están completamente avalados",
            ], 404);
        }
    }

        $responsibleName = DB::table('user_area_olympiads as uao')
            ->join('user_roles as ur', 'uao.user_role_id', '=', 'ur.id')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->join('users as u', 'ur.user_id', '=', 'u.id')
            ->where('uao.area_id', $areaId)
            ->where('uao.olympiad_id', $olympiadId)
            ->where('r.name', 'responsable_academico')
            ->select(DB::raw("CONCAT(u.first_name, ' ', u.last_name) AS full_name"))
            ->value('full_name');

        $rows = DB::table('evaluations AS e')
            ->join('registrations AS r', 'e.registration_id', '=', 'r.id')
            ->join('contestants AS c', 'r.contestant_id', '=', 'c.id')
            ->leftJoin('contestant_level_grades AS clg', 'clg.contestant_id', '=', 'c.id')
            ->leftJoin('level_grades AS lg', 'clg.level_grade_id', '=', 'lg.id')
            ->leftJoin('levels AS l', 'lg.level_id', '=', 'l.id')
            ->join('olympiad_areas AS oa', 'r.olympiad_area_id', '=', 'oa.id')
            ->join('areas AS a', 'oa.area_id', '=', 'a.id')
            ->where('e.olympiad_area_phase_id', $lastPhase->id)
            ->where('oa.olympiad_id', $olympiadId)
            ->where('oa.area_id', $areaId)
            ->select(
                'c.first_name AS Nombre',
                'c.last_name AS Apellido',
                'c.school_name AS Unidad_Educativa',
                'c.department AS Departamento',
                'a.name AS Area',
                'l.name AS Nivel',
                'e.score AS Nota',
                'e.classification_place AS Posicion_obtenida',
                'c.tutor_name AS Profesor'
            )
            ->orderBy('c.last_name')
            ->get();

        $result = $rows->map(function ($r) use ($responsibleName) {
            return [
                    'name' => $r->Nombre,
                    'last_name' => $r->Apellido,
                    'school_name' => $r->Unidad_Educativa,
                    'department' => $r->Departamento,
                    'area' => $r->Area,
                    'level' => $r->Nivel,
                    'score' => $r->Nota,
                    'classification_place' => $r->Posicion_obtenida,
                    'teacher' => $r->Profesor,
                    'area_responsible' => $responsibleName,
                ];
            });

        return response()->json($result, 200);
    }

    // Get medal count distribution for a specific level
    public function getMedals(string $olympiadId, string $areaId, string $levelId)
    {
        $lastPhaseId = DB::table('olympiad_area_phases AS oap')
            ->join('olympiad_areas AS oa', 'oap.olympiad_area_id', '=', 'oa.id')
            ->join('phases AS p', 'oap.phase_id', '=', 'p.id')
            ->join('olympiad_area_phase_level_grades AS oapl', 'oapl.olympiad_area_phase_id', '=', 'oap.id')
            ->join('level_grades AS lg', 'lg.id', '=', 'oapl.level_grade_id')
            ->where('oa.olympiad_id', $olympiadId)
            ->where('oa.area_id', $areaId)
            ->where('lg.level_id', $levelId)
            ->where('oapl.status', 'Terminada')
            ->orderByDesc('p.order')
            ->select('oap.id')
            ->first();

        if (!$lastPhaseId) {
            return response()->json([
                'message' => 'No phases found for the specified olympiad, area and level',
            ], 404);
        }

        // Get medal distribution from database
        $medals = DB::table('evaluations AS e')
            ->join('registrations AS r', 'e.registration_id', '=', 'r.id')
            ->join('olympiad_areas AS oa', 'r.olympiad_area_id', '=', 'oa.id')
            ->join('contestant_level_grades AS clg', 'clg.contestant_id', '=', 'r.contestant_id')
            ->join('level_grades AS lg', 'clg.level_grade_id', '=', 'lg.id')
            ->where('e.olympiad_area_phase_id', $lastPhaseId->id)
            ->where('oa.olympiad_id', $olympiadId)
            ->where('oa.area_id', $areaId)
            ->where('lg.level_id', $levelId)
            ->whereNotNull('e.classification_place')
            ->select('e.classification_place')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('e.classification_place')
            ->get();

        if ($medals->isEmpty()) {
            return response()->json([
                'number_gold' => 0,
                'number_silver' => 0,
                'number_bronze' => 0,
                'number_honorable_mention' => 0,
            ], 200);
        }

        // Process medal counts into structured response
        $result = [
            'number_gold' => 0,
            'number_silver' => 0,
            'number_bronze' => 0,
            'number_honorable_mention' => 0,
        ];

        // Map database results to response structure
        foreach ($medals as $medal) {
            if ($medal->classification_place === 'Oro') {
                $result['number_gold'] = $medal->cantidad;
            } elseif ($medal->classification_place === 'Plata') {
                $result['number_silver'] = $medal->cantidad;
            } elseif ($medal->classification_place === 'Bronce') {
                $result['number_bronze'] = $medal->cantidad;
            } elseif ($medal->classification_place === 'Mención honorífica') {
                $result['number_honorable_mention'] = $medal->cantidad;
            }
        }

        return response()->json($result, 200);
    }
}
