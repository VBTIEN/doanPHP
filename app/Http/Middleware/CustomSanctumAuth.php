<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Guard;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

class CustomSanctumAuth
{
    protected $guard;

    public function __construct(Guard $guard)
    {
        $this->guard = $guard;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra token
        if ($user = $this->guard->user($request)) {
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}