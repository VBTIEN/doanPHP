<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToTeacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role_code !== 'R1') {
            return response()->json([
                'status' => 'error',
                'message' => 'Chỉ giáo viên mới có quyền truy cập.',
            ], 403);
        }

        return $next($request);
    }
}