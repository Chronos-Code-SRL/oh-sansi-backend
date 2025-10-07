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
use App\Http\Controllers\CompetitorRegistrationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// <--- CRUD Olympiad --->
Route::get('/olympiads', [OlympiadController::class, 'index']);
Route::get('/olympiads/{id}', [OlympiadController::class, 'show']);
Route::post('/olympiads', [OlympiadController::class, 'store']);
Route::put('/olympiads/{id}', [OlympiadController::class, 'update']);
Route::delete('/olympiads/{id}', [OlympiadController::class, 'destroy']);

// <--- CRUD Olympiad-Areas --->
Route::post('/olympiads/{id}/areas', [OlympiadController::class, 'assignAreas']);
Route::get('/olympiads/{id}/areas', [OlympiadController::class, 'getAreas']);

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

// login
Route::post('/login', [AuthController::class, 'login']);

//POST register evaluator or responsible academic
Route::post('/register', [AuthController::class, 'register']);

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

// academic responsible routes
//Route::middleware(['auth:sanctum', 'academic_responsible'])->group(function(){
    // Competitor registration routes
    Route::post('/competitors/upload-csv', [CompetitorRegistrationController::class, 'uploadCsv']);
    Route::get('/competitors/download-error-csv/{filename}', [CompetitorRegistrationController::class, 'downloadErrorCsv']);
//});

//POST logout
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
