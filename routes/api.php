<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers here:
use App\Http\Controllers\Api\OlympiadController;

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
