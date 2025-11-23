<?php

use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\InscricaoController;
use Illuminate\Support\Facades\Route;

// Rota pública de validação de certificado (não requer autenticação)
// IMPORTANTE: Esta rota deve ser definida ANTES do grupo de middleware
Route::post('certificados/validacao', [CertificadoController::class, 'validacao']);

// Todas as outras rotas requerem autenticação por token
Route::middleware('auth.token')->group(function () {
    Route::apiResource('inscricoes', InscricaoController::class);
    Route::get('inscricoes/usuario/{usuario_id}', [InscricaoController::class, 'getByUsuario']);
    Route::post('certificados', [CertificadoController::class, 'store']);
    Route::get('certificados/{id}', [CertificadoController::class, 'show']);
    Route::get('certificados/{id}/download', [CertificadoController::class, 'download']);
});

