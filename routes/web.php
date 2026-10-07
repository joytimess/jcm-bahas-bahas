<?php

use App\Http\Controllers\ProfileController;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('threads.index', ['isDashboard' => true]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/search', 'search.index')->name('search');

    Route::get('/threads/{thread}', function (Request $request, Thread $thread) {
        abort_unless($request->user()->canView($thread), 404);

        return view('threads.show', ['threadId' => $thread->id]);
    })->name('threads.show');

    Route::get('/my/threads', fn (Request $request) => view('threads.index', [
        'source' => '/api/users/'.$request->user()->id.'/threads',
        'heading' => 'My Threads',
    ]))->name('my.threads');

    Route::view('/my/likes', 'likes.index')->name('my.likes');

    Route::get('/users/{user}', function (Request $request, User $user) {
        if ($user->is($request->user())) {
            return redirect()->route('profile.edit');
        }

        return view('users.show', ['profileId' => $user->id]);
    })->name('users.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
