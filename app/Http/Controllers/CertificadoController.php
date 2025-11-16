<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
                description: 'Certificado criado com sucesso e arquivo .txt retornado como download',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    example: 'CERTIFICADO DE PARTICIPAÇÃO

Certificamos que

NOME DO USUÁRIO

participou do evento

NOME DO EVENTO

realizado em 25/12/2024

Código do Certificado: A1B2C3D4E5F6G7H'
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
    public function store(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'presenca_id' => 'required|integer|exists:presencas,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível criar o certificado. Verifique os dados informados.',
                    'data' => null,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar se já existe um certificado para esta presença
            $certificadoExistente = Certificado::where('presenca_id', $request->presenca_id)->first();

            if ($certificadoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um certificado para esta presença.',
                    'data' => null
                ], 422);
            }

            // Buscar dados da presença, inscrição, evento e usuário
            $presenca = DB::table('presencas')
                ->where('id', $request->presenca_id)
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
                'presenca_id' => $request->presenca_id,
                'codigo' => $codigo,
            ]);

            // Criar conteúdo do arquivo .txt
            $conteudo = "CERTIFICADO DE PARTICIPAÇÃO\n\n";
            $conteudo .= "Certificamos que\n\n";
            $conteudo .= strtoupper($usuario->nome) . "\n\n";
            $conteudo .= "participou do evento\n\n";
            $conteudo .= strtoupper($evento->descricao) . "\n\n";
            $conteudo .= "realizado em " . date('d/m/Y', strtotime($evento->data_final)) . "\n\n";
            $conteudo .= "Código do Certificado: " . $codigo . "\n";

            // Criar diretório se não existir
            $diretorio = storage_path('app/public/certificados');
            if (!file_exists($diretorio)) {
                mkdir($diretorio, 0755, true);
            }

            // Salvar arquivo .txt
            $nomeArquivo = 'certificado_' . $certificado->id . '.txt';
            $caminhoArquivo = storage_path('app/public/certificados/' . $nomeArquivo);
            file_put_contents($caminhoArquivo, $conteudo);

            // Retornar o arquivo como download
            return response()->download($caminhoArquivo, $nomeArquivo, [
                'Content-Type' => 'text/plain',
            ])->deleteFileAfterSend(false);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível criar o certificado.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

