<?php

use App\Http\Controllers\FontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FontController::class, 'index'])->name('fonts.index');

Route::get('/api/fonts.json', [FontController::class, 'bundleJson'])->name('fonts.bundle');

Route::get('/compare', [FontController::class, 'compare'])->name('fonts.compare');

Route::get('/fonts/{slug}', [FontController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('fonts.show');

Route::get('/font-file/{fontFile}.ttf', [FontController::class, 'serveFile'])
    ->where('fontFile', '[0-9]+')
    ->name('fonts.serve');

Route::post('/font-file/{fontFile}/install', [FontController::class, 'installFont'])
    ->where('fontFile', '[0-9]+')
    ->name('fonts.install');
