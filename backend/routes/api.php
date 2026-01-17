<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\StatsController;

Route::prefix('v1')->group(function () {
    
    // Organizations
    Route::get('organizations', [OrganizationController::class, 'index']);
    Route::post('organizations', [OrganizationController::class, 'store']);
    Route::get('organizations/{id}', [OrganizationController::class, 'show']);
    
    // Users
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('users/{id}/files', [UserController::class, 'files']);
    
    // Files (the hot path)
    Route::get('files', [FileController::class, 'index']);
    Route::post('files', [FileController::class, 'store']);
    Route::get('files/{id}', [FileController::class, 'show']);
    Route::put('files/{id}', [FileController::class, 'update']);
    Route::delete('files/{id}', [FileController::class, 'destroy']);
    Route::post('files/{file}/view', [FileController::class, 'incrementView']);
    
    // Comments
    Route::get('files/{fileId}/comments', [CommentController::class, 'index']);
    Route::post('files/{fileId}/comments', [CommentController::class, 'store']);
    Route::put('comments/{id}', [CommentController::class, 'update']);
    Route::delete('comments/{id}', [CommentController::class, 'destroy']);
    
    // Stats & Monitoring
    Route::get('stats/db', [StatsController::class, 'database']);
    Route::get('stats/queries', [StatsController::class, 'slowQueries']);
    Route::get('stats/connections', [StatsController::class, 'connections']);
});