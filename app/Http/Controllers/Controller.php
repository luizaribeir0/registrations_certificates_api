<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API de Inscrições e Certificados',
    description: 'API RESTful para gerenciamento de inscrições e certificados'
)]
#[OA\Server(
    url: '/api',
    description: 'Servidor da API'
)]
#[OA\Tag(
    name: 'Inscrições',
    description: 'Operações relacionadas a inscrições'
)]
#[OA\Tag(
    name: 'Certificados',
    description: 'Operações relacionadas a certificados'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token de autenticação Bearer. Use o formato: Bearer <token>. Token de exemplo para testes: 12345'
)]
#[OA\Schema(
    schema: 'Inscricao',
    type: 'object',
    required: ['id', 'evento_id', 'usuario_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'evento_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true)
    ]
)]
#[OA\Schema(
    schema: 'Certificado',
    type: 'object',
    required: ['id', 'presenca_id', 'codigo'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'presenca_id', type: 'integer', example: 1),
        new OA\Property(property: 'codigo', type: 'string', example: 'A1B2C3D4E5F6G7H'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true)
    ]
)]
abstract class Controller
{
    //
}
