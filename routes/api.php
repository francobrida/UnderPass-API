<?php

use App\Http\Controllers\Api\V1\{AuthController, GenreController, EventController, 
VouchController, VibecheckController, UserController, StampController, 
RankingController, NeighborhoodController};
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    
    Route::get('/', function () {
        return response()->json([
            'message' => 'Welcome to UnderPass API v1',
            'status' => 'Connected'
        ]);
    });
    
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::middleware(['auth.cookie', 'auth:api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::delete('/profile', [UserController::class, 'destroy']);
        Route::patch('/profile', [UserController::class, 'update']);
        Route::get('/events', [EventController::class, 'index']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);

        Route::get('genres', [GenreController::class, 'index']);
        Route::get('neighborhoods', [NeighborhoodController::class, 'index']);

        Route::get('/events/{id}', [EventController::class, 'show']);
        Route::get('/users/{user_id}/events', [EventController::class, 'getUserEvents']);
        Route::post('/events/{id}/vouches', [VouchController::class, 'store']);
        Route::post('/events/{id}/vibechecks', [VibecheckController::class, 'store']);
        Route::get('events/{id}/vibechecks', [VibecheckController::class, 'index']);
        Route::post('/stamps', [StampController::class, 'store']);
        Route::get('/stamps', [StampController::class, 'index']);
        Route::get('/ranking', [RankingController::class, 'index']);

        Route::middleware('admin')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::patch('/users/{user}', [AdminUserController::class, 'update']);
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
            Route::get('/users/{id}/events', [AdminUserController::class, 'getUserEvents']);

            Route::get('/events/{id}/vouches', [VouchController::class, 'index']);
            Route::delete('/vibechecks/{id}', [VibecheckController::class, 'destroy']);
            Route::get('users/{id}/stamps', [StampController::class, 'getUserStamps']);
            Route::delete('stamps/{id}', [StampController::class, 'destroy']);

        });
    });
});