<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('Request received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'is_api' => $request->is('api/*'),
            'headers' => [
                'authorization' => $request->header('Authorization') ? 'present' : 'missing',
                'accept' => $request->header('Accept'),
            ],
        ]);

        $response = $next($request);

        Log::info('Response sent', [
            'status' => $response->getStatusCode(),
            'url' => $request->fullUrl(),
        ]);

        return $response;
    }
}

