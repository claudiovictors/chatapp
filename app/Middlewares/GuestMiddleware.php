<?php
/*
|--------------------------------------------------------------------------
| GuestMiddleware Class
|--------------------------------------------------------------------------
|
| This middleware [describe the middleware functionality here].
|
*/

declare(strict_types=1);

namespace App\Middlewares;

use Slenix\Http\Request;
use Slenix\Http\Response;
use Slenix\Http\Middlewares\Middleware;

class GuestMiddleware implements Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request  The HTTP request.
     * @param Response $response The HTTP response.
     * @param callable $next     The next handler in the pipeline.
     * @return mixed
     */
    public function handle(Request $request, Response $response, callable $next): mixed
    {
        // Middleware logic here

        // Example: check some condition
        if (session()->has('auth_id')) {
            response()->status(403)->redirect('/');
            return false;
        }

        return $next($request, $response);
    }
}