    <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers here:
use App\Http\Controllers\Api\OlympiadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\PhaseController;
use App\Http\Controllers\Api\CompetitorUploadController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\CsvUploadController;
use App\Http\Controllers\CompetitorRegistrationController;
use App\Http\Controllers\Api\UserAreaController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\ContestantController;
use App\Http\Controllers\Api\LevelController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// <--- CRUD Olympiad --->
Route::get('/olympiads', [OlympiadController::class, 'index']);
Route::get('/olympiads/{id}', [OlympiadController::class, 'show']);
Route::post('/olympiads', [OlympiadController::class, 'store']);
Route::put('/olympiads/{id}', [OlympiadController::class, 'update']);
Route::put('/olympiads/{id}/activate', [OlympiadController::class, 'activateOlympiad']);
Route::delete('/olympiads/{id}', [OlympiadController::class, 'destroy']);
Route::get('/olympiads/status/active-or-planning', [OlympiadController::class, 'activeOrPlannedOlympics']);

// <--- CRUD Olympiad-Areas --->
Route::post('/olympiads/{id}/areas', [OlympiadController::class, 'assignAreas']);
Route::get('/olympiads/{id}/areas', [OlympiadController::class, 'getAreas']);
Route::get('/olympiads/{id}/area/{areaId}', [OlympiadController::class, 'getLevels']);

// <--- CRUD Olympiad-Phases --->
Route::get('/olympiads/{id}/phases', [OlympiadController::class, 'getPhases']);

// <--- CRUD Area --->
Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{id}', [AreaController::class, 'show']);
Route::post('/areas', [AreaController::class, 'store']);
Route::put('/areas/{id}', [AreaController::class, 'update']);
Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

// <--- CRUD Area-Users --->
Route::get('/areas/{id}/users', [AreaController::class, 'getUsers']);
Route::post('/areas/{id}/users', [AreaController::class, 'assignUsers']);
Route::delete('/areas/{id}/users', [AreaController::class, 'removeUsers']);

// <--- CRUD Phase --->
Route::get('/phases', [PhaseController::class, 'index']);
Route::get('/phases/{id}', [PhaseController::class, 'show']);
Route::post('/phases', [PhaseController::class, 'store']);
Route::put('/phases/{id}', [PhaseController::class, 'update']);
Route::delete('/phases/{id}', [PhaseController::class, 'destroy']);

// <--- CRUD Olympiad-Area-Phases (Status) --->
Route::get('/olympiads/{olympiadId}/areas/{areaId}/levels/{levelId}/phase-status', [PhaseController::class, 'getPhaseStatus']);
Route::put('/olympiads/{olympiadId}/areas/{areaId}/levels/{levelId}/phase-status', [PhaseController::class, 'updatePhaseStatus']);
Route::put('/olympiads/{olympiadId}/areas/{areaId}/levels/{levelId}/phases/{phaseId}/endorse', [PhaseController::class, 'endorsePhase']);

// <--- CRUD Olympiad-Area-Level-Grades --->
Route::post('/olympiads/{olympiadId}/areas/{areaId}/level-grades', [OlympiadController::class, 'assignLevelGradesToArea']);
Route::get('/olympiads/{olympiadId}/areas/{areaId}/level-grades', [OlympiadController::class, 'getLevelGradesFromArea']);
Route::delete('/olympiads/{olympiadId}/areas/{areaId}/level-grades', [OlympiadController::class, 'removeLevelGradesFromArea']);

// <--- CRUD Grade --->
Route::get('/grades', [GradeController::class, 'index']);
Route::get('/grades/{id}', [GradeController::class, 'show']);
Route::post('/grades', [GradeController::class, 'store']);
Route::put('/grades/{id}', [GradeController::class, 'update']);
Route::delete('/grades/{id}', [GradeController::class, 'destroy']);

// <--- CRUD Olympiad-Area-Phase-Level-Grades (Score Cuts) --->
Route::post('/olympiads/{olympiadId}/areas/{areaId}/score-cuts', [OlympiadController::class, 'assignScoreCuts']);
Route::get('/olympiads/{olympiadId}/areas/{areaId}/score-cuts', [OlympiadController::class, 'getScoreCuts']);

// <--- CRUD Olympiad-Area-Phase-Level-Grades (Max Scores) --->
Route::post('/olympiads/{olympiadId}/areas/{areaId}/max-scores', [OlympiadController::class, 'assignMaxScores']);
Route::get('/olympiads/{olympiadId}/areas/{areaId}/max-scores', [OlympiadController::class, 'getMaxScores']);

// <--- Get Specific Phase Scores by Level --->
Route::get('/olympiads/{olympiadId}/areas/{areaId}/phases/{phaseId}/level/{levelId}/scores', [OlympiadController::class, 'getPhaseScoresByLevel']);

// login
Route::post('/login', [AuthController::class, 'login']);

//POST register evaluator or responsible academic
Route::post('/register', [AuthController::class, 'register']);
Route::get('/search-user/olympiad/{olympiadId}/ci/{ci}/role/{roleId}', [AuthController::class, 'searchUser']);

//admin routes
Route::middleware(['auth:sanctum', 'admin'])->group(function(){
    // // <--- CRUD Olympiad --->
    // Route::get('/olympiads', [OlympiadController::class, 'index']);
    // Route::get('/olympiads/{id}', [OlympiadController::class, 'show']);
    // Route::post('/olympiads', [OlympiadController::class, 'store']);
    // Route::put('/olympiads/{id}', [OlympiadController::class, 'update']);
    // Route::delete('/olympiads/{id}', [OlympiadController::class, 'destroy']);
    //GET all users
    Route::get('/users', [AdminController::class, 'index']);
});

// evaluator routes
Route::middleware(['auth:sanctum', 'evaluator'])->group(function(){

});

// Areas and Olympics for a logged-in user
Route::middleware(['auth:sanctum', 'evaluator_or_academic'])->group(function () {
    Route::get('/user/olympiads', [OlympiadController::class, 'getUserOlympiads']);
    Route::get('/user/areas/{olympiad_id}', [UserAreaController::class, 'getUserAreas']);
});

// academic responsible routes
//Route::middleware(['auth:sanctum', 'academic_responsible'])->group(function(){
    // Competitor registration routes
    Route::post('/competitors/upload-csv', [CompetitorRegistrationController::class, 'uploadCsv']);
    // Download a CSV template containing only headers (no data rows)
    Route::get('/competitors/download-template', [CompetitorRegistrationController::class, 'downloadTemplateCsv']);
    Route::get('/competitors/download-error-csv/{filename}', [CompetitorRegistrationController::class, 'downloadErrorCsv']);
//});

//POST logout
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// <--- CSV Upload Management --->
Route::get('/olympiads/{olympiadId}/csv-uploads', [CsvUploadController::class, 'index']);
Route::get('/csv-uploads/{id}', [CsvUploadController::class, 'show']);
Route::get('/csv-uploads/{id}/download', [CsvUploadController::class, 'download']);
Route::get('/csv-uploads/{id}/download-errors', [CsvUploadController::class, 'downloadErrors']);

// <--- CRUD Evaluation --->
Route::patch('/evaluations/{id}',[EvaluationController::class, 'updatePartialEvaluation']);
Route::get('/evaluations/check-updates/',[EvaluationController::class, 'checksUpdates']);
Route::get('/olympiads/{olympiadId}/areas/{areaId}/phases/{phaseId}/competitors', [EvaluationController::class, 'getCompetitorsByPhase']);
Route::patch('/evaluations/{id}/classification', [EvaluationController::class, 'updateClassificationStatus']);

// <--- Get Contestants --->
Route::get('/contestants/{phase_id}/{olympiad_id}/{area_id}/{level_id}', [ContestantController::class, 'showContestant']);
Route::get('/contestants/ranked/{phase_id}/{olympiad_id}/{area_id}/{level_id}', [ContestantController::class, 'getRankedContestants']);
Route::get('/contestants/{olympiad_id}', [ContestantController::class, 'showContestantsOlympiad']);
Route::get('/contestants/olympiads/{olympiad_id}/areas/{area_id}/phases/{phase_id}/levels/{level_id}', [ContestantController::class, 'countsByAreaPhaseLevel']);
Route::get('/contestants/olympiads/{olympiad_id}/areas/{area_id}/levels/{level_id}', [ContestantController::class, 'getAwardWinningContestants']);
Route::get('/contestants/olympiads/{olympiad_id}/areas/{area_id}/phases/{phase_id}/levels/{level_id}/classifieds', [ContestantController::class, 'getContestantsClassifieds']);
Route::get('/contestants/awarded/olympiads/{olympiad_id}/areas/{area_id}', [ContestantController::class, 'getAwardWinningContestants']);

// <--- CRUD Level --->
Route::get('/levels-olympiad', [App\Http\Controllers\Api\LevelController::class, 'getLevelsOlympiad']);
