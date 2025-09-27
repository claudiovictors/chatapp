<?php
declare(strict_types=1);

namespace App\Middlewares;

use Slenix\Http\Auth\Jwt;
use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;
use Slenix\Http\Message\Middleware;

class JwtMiddleware implements Middleware
{
    private Jwt $jwt;

    public function __construct()
    {
        $this->jwt = new Jwt();
    }

    public function handle(Request $request, Response $response, array $params): bool
    {
        $authHeader = '';
        if (method_exists($request, 'getHeaderLine')) {
            $authHeader = $request->getHeaderLine('Authorization') ?: $request->getHeaderLine('authorization');
        } elseif (method_exists($request, 'getHeader')) {
            $h = $request->getHeader('Authorization') ?: $request->getHeader('authorization');
            $authHeader = is_array($h) ? ($h[0] ?? '') : (string)$h;
        }

        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            $response->status(401)->json(['error' => 'Token não fornecido ou em formato inválido.']);
            return false;
        }

        $token = trim(substr($authHeader, 7));
        $payload = $this->jwt->validate($token);

        if (!$payload) {
            $response->status(401)->json(['error' => 'Token inválido ou expirado.']);
            return false;
        }

        $request->setAttribute('jwt_payload', $payload);
        return true;
    }
}
