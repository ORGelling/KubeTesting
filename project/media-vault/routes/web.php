<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/healthz', function () {
    return response()->json(['ok' => true]);
});

Route::get('/', function () {
    return redirect()->route('files.index');
});

Route::get('/dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/files', function () {
        return Inertia::render('files');
    })->name('files.index');

    Route::get('/api/files', [FileController::class, 'index']);
    Route::post('/api/files', [FileController::class, 'store']);
    Route::get(
        '/api/files/{mediaFile}/download',
        [FileController::class, 'download'],
    );
});

require __DIR__.'/settings.php';
