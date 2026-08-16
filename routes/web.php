<?php

use App\Http\Controllers\LiveDeviceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LiveDeviceController::class, 'index'])
    ->name('home');

Route::get('/dashboard', [LiveDeviceController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::patch('/live-devices/{liveDevice}/pin', [LiveDeviceController::class, 'updatePin'])
    ->name('live-devices.pin');
Route::patch('/live-devices/{liveDevice}/hidden', [LiveDeviceController::class, 'updateHidden'])
    ->name('live-devices.hidden');
Route::patch('/live-devices/{liveDevice}/nickname', [LiveDeviceController::class, 'updateNickname'])
    ->name('live-devices.nickname');
Route::delete('/live-devices', [LiveDeviceController::class, 'reset'])
    ->name('live-devices.reset');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
