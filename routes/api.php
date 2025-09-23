<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers here:
use App\Http\Controllers\Api\OlympiadController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\PhaseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// <--- CRUD Olympiad --->
// GET /olympiads
Route::get('/olympiads', [OlympiadController::class, 'index']);
// GET /olympiads/{id}
Route::get('/olympiads/{id}', [OlympiadController::class, 'show']);
// POST /olympiads/
Route::post('/olympiads', [OlympiadController::class, 'store']);
// PUT /olympiads/{id}
Route::put('/olympiads/{id}', [OlympiadController::class, 'update']);
// TO DO: PATCH/olympiads/{id}
// DELETE /olympiads/{id}
Route::delete('/olympiads/{id}', [OlympiadController::class, 'destroy']);

// <--- CRUD Area --->
// GET /areas
Route::get('/areas', [AreaController::class, 'index']);
// GET /areas/{id}
Route::get('/areas/{id}', [AreaController::class, 'show']);
// POST /areas/
Route::post('/areas', [AreaController::class, 'store']);
// PUT /areas/{id}
Route::put('/areas/{id}', [AreaController::class, 'update']);
// TO DO: PATCH/areas/{id}
// DELETE /areas/{id}
Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

// <--- CRUD Phase --->
// GET /phases
Route::get('/phases', [PhaseController::class, 'index']);
// GET /phases/{id}
Route::get('/phases/{id}', [PhaseController::class, 'show']);
// POST /phases/
Route::post('/phases', [PhaseController::class, 'store']);
// PUT /phases/{id}
Route::put('/phases/{id}', [PhaseController::class, 'update']);
// TO DO: PATCH/phases/{id}
// DELETE /phases/{id}
Route::delete('/phases/{id}', [PhaseController::class, 'destroy']);
