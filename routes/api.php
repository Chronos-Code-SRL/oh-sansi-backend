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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// <--- CRUD Olympiad --->
Route::get('/olympiads', [OlympiadController::class, 'index']);
Route::get('/olympiads/{id}', [OlympiadController::class, 'show']);
Route::post('/olympiads', [OlympiadController::class, 'store']);
Route::put('/olympiads/{id}', [OlympiadController::class, 'update']);
Route::delete('/olympiads/{id}', [OlympiadController::class, 'destroy']);
// Assign areas to olympiad
Route::post('/olympiads/{id}/areas', [OlympiadController::class, 'assignAreas']);
// Get areas of an olympiad
Route::get('/olympiads/{id}/areas', [OlympiadController::class, 'getAreas']);

// <--- CRUD Area --->
Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/{id}', [AreaController::class, 'show']);
Route::post('/areas', [AreaController::class, 'store']);
Route::put('/areas/{id}', [AreaController::class, 'update']);
Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

// <--- CRUD Phase --->
Route::get('/phases', [PhaseController::class, 'index']);
Route::get('/phases/{id}', [PhaseController::class, 'show']);
Route::post('/phases', [PhaseController::class, 'store']);
Route::put('/phases/{id}', [PhaseController::class, 'update']);
Route::delete('/phases/{id}', [PhaseController::class, 'destroy']);

// login
Route::post('/login', [AuthController::class, 'login']);

//admin routes
Route::middleware(['auth:sanctum', 'admin'])->group(function(){
    //POST register evaluator or responsible academic
    Route::post('/register', [AuthController::class, 'register']);
    //GET all users
    Route::get('/users', [AdminController::class, 'index']);
});

// evaluator routes
Route::middleware(['auth:sanctum', 'evaluator'])->group(function(){

});

// academic responsible routes
Route::middleware(['auth:sanctum', 'academic_responsible'])->group(function(){

});

//POST logout
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
