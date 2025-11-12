<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
class EvaluatorAcademicMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 401
            ], 401);
        }

        $roleId = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->value('role_id');

        if (in_array($roleId, [2, 3])) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied. Evaluators or Academic responsables only.',
            'status' => 403
        ], 403);
    }
}
