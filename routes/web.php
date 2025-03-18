<?php

use App\Http\Controllers\PartNumberController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('import-part-numbers', [PartNumberController::class, 'import'])->name('part-numbers.import');
Route::post('upload-part-numbers', [PartNumberController::class, 'upload'])->name('part-numbers.upload');
Route::get('test', [PartNumberController::class, 'index'])->name('test');
