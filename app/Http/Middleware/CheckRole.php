<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => '未授權'], 401);
        }

        if (!empty($roles) && !in_array($user->role, $roles)) {
            return response()->json(['message' => '權限不足'], 403);
        }

        return $next($request);
    }
}
