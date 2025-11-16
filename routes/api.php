<?php

use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\InscricaoController;
use Illuminate\Support\Facades\Route;

// Todas as rotas requerem autenticação por token
Route::middleware('auth.token')->group(function () {
    Route::apiResource('inscricoes', InscricaoController::class);
    Route::post('certificados', [CertificadoController::class, 'store']);
});

