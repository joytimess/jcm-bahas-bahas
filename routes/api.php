<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\FollowRequestController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MyLikeController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ThreadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('/user', [UserController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::get('users/suggestions', [UserController::class, 'suggestions']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::get('users/{user}/threads', [UserController::class, 'threads']);
    Route::get('users/{user}/followers', [UserController::class, 'followers']);
    Route::get('users/{user}/following', [UserController::class, 'following']);
    Route::post('users/{user}/follow', [FollowController::class, 'toggle']);

    Route::get('me/likes', [MyLikeController::class, 'index']);

    Route::get('follow-requests', [FollowRequestController::class, 'index']);
    Route::post('follow-requests/{user}/accept', [FollowRequestController::class, 'accept']);
    Route::delete('follow-requests/{user}', [FollowRequestController::class, 'reject']);

    Route::apiResource('threads', ThreadController::class);
    Route::post('threads/{thread}/like', [LikeController::class, 'thread']);

    Route::get('threads/{thread}/comments', [CommentController::class, 'index']);
    Route::post('threads/{thread}/comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('comments/{comment}/like', [LikeController::class, 'comment']);
});
