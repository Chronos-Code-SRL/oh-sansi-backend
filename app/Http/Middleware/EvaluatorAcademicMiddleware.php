<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluatorAcademicMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->roles_id, [2, 3])) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied. Evaluators or Academic responsables only.',
            'status' => 403
        ], 403);
    }
}
