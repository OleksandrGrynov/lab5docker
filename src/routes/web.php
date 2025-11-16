<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {

    Route::get('/chats/create', [ChatController::class, 'create'])->name('chats.create');

    Route::post('/chats/create-private', [ChatController::class, 'createPrivate'])->name('chats.create.private');
    Route::post('/chats/create-group', [ChatController::class, 'createGroup'])->name('chats.create.group');

    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{chat}/send', [ChatController::class, 'send'])->name('chats.send');
});



Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
Route::post('/chats/{chat}/send', [ChatController::class, 'send'])->name('chats.send');

require __DIR__.'/auth.php';
