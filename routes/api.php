<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\Auth\AuthController;
use App\Http\Controllers\Backend\TaskController;

// Backend Routes

// Csrf token route
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['csrf' => csrf_token()]);
});

Route::prefix('v1')->group(function () {
    // Public routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/signin', [AuthController::class, 'signin']);
        Route::post('/signup', [AuthController::class, 'signup']);
        Route::get('/verify', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        Route::post('/resendVerificationEmail', [AuthController::class, 'resendVerificationEmail']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Protected routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::group(['prefix' => 'admin'], function () {
            Route::group(['prefix' => 'tasks'], function () {
                Route::get('/fetch', [TaskController::class, 'index']);
                Route::get('/{id}/edit', [TaskController::class, 'edit']);
                Route::post('/store', [TaskController::class, 'store']);
                Route::post('/{id}/update', [TaskController::class, 'update']);
                Route::post('/{id}/delete', [TaskController::class, 'delete']);
                Route::post('/{id}/comment', [TaskController::class, 'comment']);
            });
        });
    });
});
