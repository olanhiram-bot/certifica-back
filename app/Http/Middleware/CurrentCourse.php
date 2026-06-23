<?php

namespace App\Http\Middleware;

use App\Models\CourseCall;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CurrentCourse
{
    public function handle(Request $request, Closure $next): JsonResponse|Response
    {

        // se valida con el scope
        if (! CourseCall::currentToday()->exists()) {
            return response()->json(['error' => 'Fuera de rango de fechas.'], 403);
        }

        // Si todo está bien, dejamos pasar la petición
        return $next($request);
    }
}
