<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticação inválido ou não fornecido.',
                'data' => null
            ], 401);
        }

        // Remove "Bearer " prefix if present (with or without space)
        $token = trim($token);
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        } elseif (str_starts_with($token, 'Bearer')) {
            $token = substr($token, 6);
        }

        // Trim any remaining whitespace
        $token = trim($token);

        // Validate token
        if ($token !== '12345') {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticação inválido ou não fornecido.',
                'data' => null
            ], 401);
        }

        return $next($request);
    }
}

