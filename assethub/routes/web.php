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

Route::middleware('auth')->group(function () {
    Route::get('/files', function () {
        return Inertia::render('Files');
    })->name('files.index');

    Route::get('/api/files', [FileController::class, 'index']);
    Route::post('/api/files', [FileController::class, 'store']);
    Route::get(
        '/api/files/{mediaFile}/download',
        [FileController::class, 'download'],
    );
});

// This is provided by Laravel's React authentication starter kit.
require __DIR__ . '/auth.php';
