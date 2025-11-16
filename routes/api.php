<?php

use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\InscricaoController;
use Illuminate\Support\Facades\Route;

// Todas as rotas requerem autenticação por token
Route::middleware('auth.token')->group(function () {
    Route::apiResource('inscricoes', InscricaoController::class);
    Route::get('inscricoes/usuario/{usuario_id}', [InscricaoController::class, 'getByUsuario']);
    Route::post('certificados', [CertificadoController::class, 'store']);
    Route::post('certificados/validacao', [CertificadoController::class, 'validacao']);
    Route::get('certificados/{id}', [CertificadoController::class, 'show']);
});

