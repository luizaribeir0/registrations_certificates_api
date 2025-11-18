<?php

namespace App\Http\Controllers;

use App\Models\Inscricao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class InscricaoController extends Controller
{
    /**
     * Lista todas as inscrições sem presença registrada
     */
    #[OA\Get(
        path: '/api/inscricoes',
        summary: 'Lista inscrições sem presença registrada',
        description: 'Retorna todas as inscrições que não possuem presença registrada na tabela de presenças',
        tags: ['Inscrições'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de inscrições sem presença registrada retornada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrições listadas com sucesso!'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'evento_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'usuario_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2024-01-01 10:00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: '2024-01-01 10:00:00'),
                                    new OA\Property(property: 'nome_evento', type: 'string', example: 'Workshop de Laravel'),
                                    new OA\Property(property: 'email_usuario', type: 'string', format: 'email', example: 'usuario@example.com')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token de autenticação inválido ou não fornecido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Token de autenticação inválido ou não fornecido.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        try {
            $inscricoesIds = DB::table('presencas')->pluck('inscricao_id');

            $inscricoes = DB::table('inscricoes')
                ->leftJoin('eventos', 'inscricoes.evento_id', '=', 'eventos.id')
                ->leftJoin('usuarios', 'inscricoes.usuario_id', '=', 'usuarios.id')
                ->whereNotIn('inscricoes.id', $inscricoesIds)
                ->select(
                    'inscricoes.*',
                    'eventos.descricao as nome_evento',
                    'usuarios.email as email_usuario'
                )
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Inscrições listadas com sucesso!',
                'data' => $inscricoes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível listar as inscrições.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Cria uma nova inscrição
     */
    #[OA\Post(
        path: '/api/inscricoes',
        summary: 'Cria uma nova inscrição',
        tags: ['Inscrições'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['evento_id', 'usuario_id'],
                properties: [
                    new OA\Property(property: 'evento_id', type: 'integer', example: 1),
                    new OA\Property(property: 'usuario_id', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Inscrição criada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrição criada com sucesso!'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Inscricao')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Não foi possível criar a inscrição. Verifique os dados informados.'),
                        new OA\Property(property: 'data', type: 'null', example: null),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token de autenticação inválido ou não fornecido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Token de autenticação inválido ou não fornecido.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'evento_id' => 'required|integer|exists:eventos,id',
                'usuario_id' => 'required|integer|exists:usuarios,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível criar a inscrição. Verifique os dados informados.',
                    'data' => null,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar se o evento está cancelado
            $evento = DB::table('eventos')->where('id', $request->evento_id)->first();

            if ($evento && $evento->cancelado != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível se inscrever em um evento cancelado.',
                    'data' => null
                ], 422);
            }

            // Verificar se já existe uma inscrição para este evento e usuário
            $inscricaoExistente = Inscricao::where('evento_id', $request->evento_id)
                ->where('usuario_id', $request->usuario_id)
                ->first();

            if ($inscricaoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma inscrição para este evento e usuário.',
                    'data' => null
                ], 422);
            }

            $inscricao = Inscricao::create([
                'evento_id' => $request->evento_id,
                'usuario_id' => $request->usuario_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscrição criada com sucesso!',
                'data' => $inscricao
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível criar a inscrição.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Consulta uma inscrição específica
     */
    #[OA\Get(
        path: '/api/inscricoes/{id}',
        summary: 'Consulta uma inscrição por ID',
        tags: ['Inscrições'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da inscrição',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inscrição encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrição encontrada com sucesso!'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Inscricao')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Inscrição não encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrição não encontrada.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token de autenticação inválido ou não fornecido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Token de autenticação inválido ou não fornecido.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $inscricao = Inscricao::find($id);

            if (!$inscricao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscrição não encontrada.',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inscrição encontrada com sucesso!',
                'data' => $inscricao
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível consultar a inscrição.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Lista inscrições de um usuário específico
     */
    #[OA\Get(
        path: '/api/inscricoes/usuario/{usuario_id}',
        summary: 'Lista inscrições de um usuário',
        tags: ['Inscrições'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'usuario_id',
                in: 'path',
                required: true,
                description: 'ID do usuário',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inscrições do usuário retornadas com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrições do usuário listadas com sucesso!'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'evento_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'usuario_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2024-01-01 10:00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: '2024-01-01 10:00:00'),
                                    new OA\Property(property: 'tem_presenca', type: 'boolean', example: true, description: 'true se tem presença registrada, false caso contrário'),
                                    new OA\Property(property: 'tem_certificado', type: 'boolean', example: true, description: 'true se tem certificado gerado, false caso contrário')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Usuário não encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário não encontrado.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token de autenticação inválido ou não fornecido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Token de autenticação inválido ou não fornecido.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            )
        ]
    )]
    public function getByUsuario(int $usuario_id): JsonResponse
    {
        try {
            // Verificar se o usuário existe
            $usuario = DB::table('usuarios')->where('id', $usuario_id)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado.',
                    'data' => null
                ], 404);
            }

            // Buscar todas as inscrições do usuário com informações de presença e certificado
            $inscricoes = DB::table('inscricoes')
                ->leftJoin('presencas', 'inscricoes.id', '=', 'presencas.inscricao_id')
                ->leftJoin('certificados', 'presencas.id', '=', 'certificados.presenca_id')
                ->where('inscricoes.usuario_id', $usuario_id)
                ->select(
                    'inscricoes.*',
                    DB::raw('CASE WHEN presencas.id IS NOT NULL THEN 1 ELSE 0 END as tem_presenca'),
                    DB::raw('CASE WHEN certificados.id IS NOT NULL THEN 1 ELSE 0 END as tem_certificado')
                )
                ->get();

            // Converter os campos para booleanos
            $inscricoes = $inscricoes->map(function ($inscricao) {
                $inscricao->tem_presenca = (bool) $inscricao->tem_presenca;
                $inscricao->tem_certificado = (bool) $inscricao->tem_certificado;
                return $inscricao;
            });

            return response()->json([
                'success' => true,
                'message' => 'Inscrições do usuário listadas com sucesso!',
                'data' => $inscricoes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível listar as inscrições do usuário.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove uma inscrição
     */
    #[OA\Delete(
        path: '/api/inscricoes/{id}',
        summary: 'Remove uma inscrição',
        tags: ['Inscrições'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID da inscrição',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inscrição removida com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrição removida com sucesso!'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Inscrição não encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscrição não encontrada.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token de autenticação inválido ou não fornecido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Token de autenticação inválido ou não fornecido.'),
                        new OA\Property(property: 'data', type: 'null', example: null)
                    ]
                )
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $inscricao = Inscricao::find($id);

            if (!$inscricao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscrição não encontrada.',
                    'data' => null
                ], 404);
            }

            $inscricao->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inscrição removida com sucesso!',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível remover a inscrição.',
                'data' => null
            ], 500);
        }
    }
}

