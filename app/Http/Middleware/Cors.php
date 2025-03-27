<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Cors
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Đặt header CORS cụ thể cho origin của frontend
        if ($response instanceof BinaryFileResponse) {
            $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            $response->header('Access-Control-Allow-Origin', 'http://localhost:5173');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}