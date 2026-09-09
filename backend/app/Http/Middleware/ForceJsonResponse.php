<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Paths that should be excluded from forced JSON (e.g. Swagger UI docs & assets).
     *
     * @var array<int, string>
     */
    protected array $except = [
        'api/documentation*',
        'docs*',
    ];

    /**
     * Handle an incoming request by forcing an Accept: application/json header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
