<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers here:
use App\Http\Controllers\Api\OlympiadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\AdminController;

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

// login 
Route::post('/login', [AuthController::class, 'login']);

//login admin
Route::middleware(['auth:sanctum', 'admin'])->group(function(){
    //POST register evaluator or responsible academic
    Route::post('/register', [AuthController::class, 'register']);
    //GET all users
    Route::get('/users', [AdminController::class, 'index']);
    //POST logout    
    Route::post('/logout', [AuthController::class, 'logout']);
});