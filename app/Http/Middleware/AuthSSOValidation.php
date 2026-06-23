<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthSSOValidation
{
    /**
     * Valida que el usuario esté autenticado con el método SSO
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->auth_method_id != 2) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        return $next($request);
    }
}
