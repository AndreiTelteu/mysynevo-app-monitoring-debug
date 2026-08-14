<?php

use App\Http\Controllers\LiveDeviceController;
use Illuminate\Support\Facades\Route;

Route::post('/debug/live-devices/state', [LiveDeviceController::class, 'store']);
