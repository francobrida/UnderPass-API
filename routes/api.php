<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\EventController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    
    // PUBLIC
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // WITH AUTH
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::delete('/profile', [UserController::class, 'destroy']);
        Route::patch('/profile', [UserController::class, 'update']);
        Route::get('/events', [EventController::class, 'index']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
        Route::get('/events/{id}', [EventController::class, 'show']);
        Route::get('/users/{user_id}/events', [EventController::class, 'userEvents']);
    });
});