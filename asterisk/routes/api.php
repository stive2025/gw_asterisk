<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\AudioController;
use Illuminate\Support\Facades\Route;

// Rutas de llamadas (sin autenticación)
Route::post('calls/dial', [CallController::class, 'dial']);    
Route::post('calls/hangup', [CallController::class, 'hangup']);

// Ruta para subir audios
Route::post('audios/upload', [AudioController::class, 'upload']);