<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log da requisição
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ];

        // Adicionar token de autorização (mascarado para segurança)
        $authHeader = $request->header('Authorization');
        if ($authHeader) {
            $token = trim($authHeader);
            if (str_starts_with($token, 'Bearer ')) {
                $token = substr($token, 7);
            } elseif (str_starts_with($token, 'Bearer')) {
                $token = substr($token, 6);
            }
            $token = trim($token);
            
            // Mascarar o token mostrando apenas os primeiros e últimos caracteres
            if (strlen($token) > 4) {
                $maskedToken = substr($token, 0, 2) . str_repeat('*', strlen($token) - 4) . substr($token, -2);
            } else {
                $maskedToken = str_repeat('*', strlen($token));
            }
            $logData['authorization'] = 'Bearer ' . $maskedToken;
        }

        // Adicionar dados do body (se houver e não for muito grande)
        if ($request->has('presenca_id') || $request->has('codigo') || $request->has('evento_id') || $request->has('usuario_id')) {
            $logData['request_body'] = $request->only(['presenca_id', 'codigo', 'evento_id', 'usuario_id']);
        }

        // Processar a requisição
        $response = $next($request);

        // Calcular tempo de resposta
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2); // em milissegundos

        // Adicionar informações da resposta
        $logData['status_code'] = $response->getStatusCode();
        $logData['response_time_ms'] = $responseTime;

        // Log baseado no status code usando canal específico da API
        if ($response->getStatusCode() >= 500) {
            Log::channel('api')->error('API Request - Server Error', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::channel('api')->warning('API Request - Client Error', $logData);
        } else {
            Log::channel('api')->info('API Request', $logData);
        }

        return $response;
    }
}

