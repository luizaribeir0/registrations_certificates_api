<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class CertificadoController extends Controller
{
    /**
     * Gera um código aleatório de 15 caracteres (letras e números)
     */
    private function gerarCodigo(): string
    {
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $codigo = '';

        for ($i = 0; $i < 15; $i++) {
            $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

        return $codigo;
    }

    /**
     * Cria um novo certificado
     */
    #[OA\Post(
        path: '/api/certificados',
        summary: 'Cria um novo certificado',
        tags: ['Certificados'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['presenca_id'],
                properties: [
                    new OA\Property(property: 'presenca_id', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificado criado com sucesso. Retorna o PDF do certificado para download automático.',
                content: new OA\MediaType(
                    mediaType: 'application/pdf'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Não foi possível criar o certificado. Verifique os dados informados.'),
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
    public function store(Request $request)
    {
        try {
            // Garantir que estamos lendo o JSON corretamente
            $data = $request->all();

            // Se o body estiver vazio, tentar ler como JSON raw
            if (empty($data) && $request->getContent()) {
                $jsonData = json_decode($request->getContent(), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $jsonData;
                }
            }

            $validator = Validator::make($data, [
                'presenca_id' => 'required|integer|exists:presencas,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível criar o certificado. Verifique os dados informados.',
                    'data' => null,
                    'errors' => $validator->errors(),
                    'received_data' => $data // Adicionar dados recebidos para debug
                ], 422);
            }

            // Usar os dados validados
            $presencaId = $data['presenca_id'];

            // Verificar se já existe um certificado para esta presença
            $certificadoExistente = Certificado::where('presenca_id', $presencaId)->first();

            if ($certificadoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um certificado para esta presença.',
                    'data' => null
                ], 422);
            }

            // Buscar dados da presença, inscrição, evento e usuário
            $presenca = DB::table('presencas')
                ->where('id', $presencaId)
                ->first();

            if (!$presenca) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presença não encontrada.',
                    'data' => null
                ], 404);
            }

            $inscricao = DB::table('inscricoes')
                ->where('id', $presenca->inscricao_id)
                ->first();

            if (!$inscricao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscrição não encontrada.',
                    'data' => null
                ], 404);
            }

            $evento = DB::table('eventos')
                ->where('id', $inscricao->evento_id)
                ->first();

            if (!$evento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evento não encontrado.',
                    'data' => null
                ], 404);
            }

            $usuario = DB::table('usuarios')
                ->where('id', $inscricao->usuario_id)
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado.',
                    'data' => null
                ], 404);
            }

            // Gerar código único
            $codigo = $this->gerarCodigo();

            // Garantir que o código seja único
            while (Certificado::where('codigo', $codigo)->exists()) {
                $codigo = $this->gerarCodigo();
            }

            // Criar certificado
            $certificado = Certificado::create([
                'presenca_id' => $presencaId,
                'codigo' => $codigo,
            ]);

            // Link de validação
            $linkValidacao = 'http://177.44.248.78:8001/api/certificados/validacao';

            // Gerar PDF do certificado
            $pdf = Pdf::loadView('certificado', [
                'usuario' => $usuario,
                'evento' => $evento,
                'codigo' => $codigo,
                'linkValidacao' => $linkValidacao
            ])->setPaper('a4', 'landscape');

            // Nome do arquivo para download
            $nomeArquivo = 'certificado_' . $certificado->id . '.pdf';

            // Retornar PDF diretamente para download automático
            return $pdf->download($nomeArquivo);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível criar o certificado.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta um certificado por ID
     */
    #[OA\Get(
        path: '/api/certificados/{id}',
        summary: 'Consulta um certificado por ID',
        tags: ['Certificados'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID do certificado',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificado encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Certificado encontrado com sucesso!'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'presenca_id', type: 'integer', example: 1),
                                new OA\Property(property: 'codigo', type: 'string', example: '71Tz2wHC6styPDY'),
                                new OA\Property(property: 'usuario', type: 'object'),
                                new OA\Property(property: 'evento', type: 'object'),
                                new OA\Property(property: 'inscricao', type: 'object'),
                                new OA\Property(property: 'presenca', type: 'object')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Certificado não encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Certificado não encontrado.'),
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
            $certificado = Certificado::find($id);

            if (!$certificado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificado não encontrado.',
                    'data' => null
                ], 404);
            }

            // Buscar dados relacionados
            $presenca = DB::table('presencas')
                ->where('id', $certificado->presenca_id)
                ->first();

            if (!$presenca) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presença não encontrada.',
                    'data' => null
                ], 404);
            }

            $inscricao = DB::table('inscricoes')
                ->where('id', $presenca->inscricao_id)
                ->first();

            if (!$inscricao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscrição não encontrada.',
                    'data' => null
                ], 404);
            }

            $evento = DB::table('eventos')
                ->where('id', $inscricao->evento_id)
                ->first();

            if (!$evento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evento não encontrado.',
                    'data' => null
                ], 404);
            }

            $usuario = DB::table('usuarios')
                ->where('id', $inscricao->usuario_id)
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado.',
                    'data' => null
                ], 404);
            }

            // Montar resposta com dados completos
            $dadosCertificado = [
                'id' => $certificado->id,
                'presenca_id' => $certificado->presenca_id,
                'codigo' => $certificado->codigo,
                'created_at' => $certificado->created_at,
                'updated_at' => $certificado->updated_at,
                'usuario' => [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                ],
                'evento' => [
                    'id' => $evento->id,
                    'descricao' => $evento->descricao,
                    'data_inicio' => $evento->data_inicio,
                    'data_final' => $evento->data_final,
                ],
                'inscricao' => [
                    'id' => $inscricao->id,
                    'evento_id' => $inscricao->evento_id,
                    'usuario_id' => $inscricao->usuario_id,
                ],
                'presenca' => [
                    'id' => $presenca->id,
                    'inscricao_id' => $presenca->inscricao_id,
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Certificado encontrado com sucesso!',
                'data' => $dadosCertificado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível consultar o certificado.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida um certificado pelo código
     */
    #[OA\Post(
        path: '/api/certificados/validacao',
        summary: 'Valida um certificado pelo código',
        tags: ['Certificados'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['codigo'],
                properties: [
                    new OA\Property(property: 'codigo', type: 'string', example: '71Tz2wHC6styPDY')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resultado da validação. Retorna valido=true com dados do certificado se válido, ou valido=false com certificado=null se inválido.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Certificado válido.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'valido', type: 'boolean', example: true, description: 'true se o certificado for válido, false caso contrário'),
                                new OA\Property(property: 'certificado', type: 'object', nullable: true, description: 'Dados do certificado se válido, null se inválido')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Não foi possível validar o certificado. Verifique os dados informados.'),
                        new OA\Property(property: 'data', type: 'null', example: null),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function validacao(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível validar o certificado. Verifique os dados informados.',
                    'data' => null,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buscar certificado pelo código
            $certificado = Certificado::where('codigo', $request->codigo)->first();

            if (!$certificado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Certificado inválido.',
                    'data' => [
                        'valido' => false,
                        'certificado' => null
                    ]
                ], 200);
            }

            // Buscar dados relacionados
            $presenca = DB::table('presencas')
                ->where('id', $certificado->presenca_id)
                ->first();

            if (!$presenca) {
                return response()->json([
                    'success' => true,
                    'message' => 'Certificado inválido.',
                    'data' => [
                        'valido' => false,
                        'certificado' => null
                    ]
                ], 200);
            }

            $inscricao = DB::table('inscricoes')
                ->where('id', $presenca->inscricao_id)
                ->first();

            if (!$inscricao) {
                return response()->json([
                    'success' => true,
                    'message' => 'Certificado inválido.',
                    'data' => [
                        'valido' => false,
                        'certificado' => null
                    ]
                ], 200);
            }

            $evento = DB::table('eventos')
                ->where('id', $inscricao->evento_id)
                ->first();

            if (!$evento) {
                return response()->json([
                    'success' => true,
                    'message' => 'Certificado inválido.',
                    'data' => [
                        'valido' => false,
                        'certificado' => null
                    ]
                ], 200);
            }

            $usuario = DB::table('usuarios')
                ->where('id', $inscricao->usuario_id)
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => true,
                    'message' => 'Certificado inválido.',
                    'data' => [
                        'valido' => false,
                        'certificado' => null
                    ]
                ], 200);
            }

            // Montar resposta com dados completos
            $dadosCertificado = [
                'id' => $certificado->id,
                'presenca_id' => $certificado->presenca_id,
                'codigo' => $certificado->codigo,
                'created_at' => $certificado->created_at,
                'updated_at' => $certificado->updated_at,
                'usuario' => [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                ],
                'evento' => [
                    'id' => $evento->id,
                    'descricao' => $evento->descricao,
                    'data_inicio' => $evento->data_inicio,
                    'data_final' => $evento->data_final,
                ],
                'inscricao' => [
                    'id' => $inscricao->id,
                    'evento_id' => $inscricao->evento_id,
                    'usuario_id' => $inscricao->usuario_id,
                ],
                'presenca' => [
                    'id' => $presenca->id,
                    'inscricao_id' => $presenca->inscricao_id,
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Certificado válido.',
                'data' => [
                    'valido' => true,
                    'certificado' => $dadosCertificado
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível validar o certificado.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

