<?php

/*
|--------------------------------------------------------------------------
| ThrottleMiddleware Class
|--------------------------------------------------------------------------
|
| Rate-limiting middleware for Slenix routes.
|
| Reads its configuration from the parameterised 'throttle:max,decay' alias
| supported by the Router. Parameters are injected via the
| $_SERVER['HTTP_X_THROTTLE_PARAMS'] variable by the Router just before this
| middleware is instantiated, so no constructor arguments are required.
|
|
*/

declare(strict_types=1);

namespace App\Middlewares;

use Slenix\Http\Request;
use Slenix\Http\Response;
use Slenix\Http\Middlewares\Middleware;
use Slenix\Supports\Security\RateLimit;
use Slenix\Supports\Security\Jwt;

class ThrottleMiddleware implements Middleware
{
    /**
     * Default maximum number of requests allowed per window.
     *
     * @var int
     */
    private const DEFAULT_MAX = 60;

    /**
     * Default window duration in minutes.
     *
     * @var int
     */
    private const DEFAULT_DECAY_MINUTES = 1;

    /**
     * Handles an incoming HTTP request and enforces rate limiting.
     *
     * Execution flow:
     *   1. Parse throttle parameters from the Router-injected server variable.
     *   2. Resolve the best rate-limit key for the current caller (JWT → Session → IP).
     *   3. Attempt the rate-limited action via RateLimit::attempt().
     *   4. Emit X-RateLimit-* response headers regardless of the outcome.
     *   5. If the limit is exceeded return a 429 response and halt the pipeline.
     *   6. Otherwise pass the request to the next handler in the pipeline.
     *
     * @param Request  $request  The incoming HTTP request.
     * @param Response $response The outgoing HTTP response.
     * @param callable $next     The next middleware or route handler.
     *
     * @return mixed
     */
    public function handle(Request $request, Response $response, callable $next): mixed
    {
        [$maxAttempts, $decaySeconds] = $this->parseParams();

        $key    = $this->resolveKey($request);
        $result = RateLimit::attempt($key, $maxAttempts, $decaySeconds);

        $this->emitHeaders($result);

        if (!$result['allowed']) {
            return $this->respondTooManyRequests($request, $response, $result);
        }

        return $next($request, $response);
    }

    /**
     * Resolves the most appropriate rate-limit key for the current request.
     *
     * Identity is resolved in the following priority order:
     *   1. JWT user_id   — extracted from the Bearer token in the Authorization header.
     *   2. Session user_id — read from the active PHP session (key: 'user_id').
     *   3. IP address    — resolved automatically by RateLimit::buildKey().
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return string The resolved rate-limit bucket key.
     */
    private function resolveKey(Request $request): string
    {
        $route     = $this->normaliseRoute($request);
        $jwtUserId = $this->extractJwtUserId($request);

        return RateLimit::buildKey(
            route:      $route,
            ip:         $request->ip(),
            jwtUserId:  $jwtUserId,
            sessionKey: 'user_id'
        );
    }

    /**
     * Attempts to extract the user_id claim from a Bearer JWT token.
     *
     * Returns null if no Authorization header is present, the header does not
     * use the Bearer scheme, or the token fails JWT validation.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return string|null The user_id from the JWT payload, or null.
     */
    private function extractJwtUserId(Request $request): ?string
    {
        $authHeader = $request->getHeader('Authorization', '');

        if (!str_starts_with((string) $authHeader, 'Bearer ')) {
            return null;
        }

        $payload = (new Jwt())->validate(substr((string) $authHeader, 7));

        if ($payload === null || !isset($payload['user_id'])) {
            return null;
        }

        return (string) $payload['user_id'];
    }

    /**
     * Produces a short, normalised route string from the request URI.
     *
     * Digit-only dynamic segments are replaced with {id} so that
     * /users/42/orders and /users/99/orders share the same rate-limit bucket.
     *
     * @param Request $request The incoming HTTP request.
     *
     * @return string Normalised route prefix, e.g. 'throttle:users/{id}/orders'.
     */
    private function normaliseRoute(Request $request): string
    {
        $uri        = parse_url($request->uri(), PHP_URL_PATH) ?? '/';
        $normalised = preg_replace('/\/\d+/', '/{id}', $uri) ?? $uri;

        return 'throttle:' . trim($normalised, '/');
    }

    /**
     * Emits standard X-RateLimit-* HTTP headers on every request.
     *
     * Headers emitted:
     *   - X-RateLimit-Limit     : Maximum allowed requests in the window.
     *   - X-RateLimit-Remaining : Requests still available in the current window.
     *   - X-RateLimit-Reset     : Unix timestamp when the window resets.
     *
     * @param array $result The result returned by RateLimit::attempt().
     *
     * @return void
     */
    private function emitHeaders(array $result): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-RateLimit-Limit: '     . $result['max_attempts']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: '     . $result['reset_at']);
    }

    /**
     * Sends a 429 Too Many Requests response and terminates the pipeline.
     *
     * Automatically detects the expected response format:
     *   - JSON : when the client sends Accept: application/json or X-Requested-With: XMLHttpRequest.
     *   - HTML : for all other browser-originated requests.
     *
     * Also emits the Retry-After header so compliant clients know how long to wait.
     *
     * @param Request  $request  The incoming HTTP request.
     * @param Response $response The outgoing HTTP response.
     * @param array    $result   The result returned by RateLimit::attempt().
     *
     * @return null Always returns null after halting execution via exit.
     */
    private function respondTooManyRequests(Request $request, Response $response, array $result): null
    {
        if (!headers_sent()) {
            header('Retry-After: ' . $result['retry_after']);
        }

        if ($request->expectsJson()) {
            $response->status(429)->json([
                'success'     => false,
                'message'     => 'Too many requests. Please slow down.',
                'retry_after' => $result['retry_after'],
                'reset_at'    => $result['reset_at'],
            ]);
        } else {
            http_response_code(429);
            echo '<!DOCTYPE html>'
                . '<html lang="en"><head><meta charset="UTF-8">'
                . '<title>429 — Too Many Requests</title></head><body>'
                . '<h1>429 — Too Many Requests</h1>'
                . '<p>You have sent too many requests. '
                . 'Please wait <strong>' . $result['retry_after'] . '</strong> '
                . 'second(s) before trying again.</p>'
                . '</body></html>';
        }

        exit;
    }

    /**
     * Parses the throttle parameters injected by the Router.
     *
     * The Router writes 'throttle:{max},{decay}' into
     * $_SERVER['HTTP_X_THROTTLE_PARAMS'] before instantiating this middleware.
     *
     * Format  : 'throttle:{maxAttempts},{decayMinutes}'
     * Examples:
     *   'throttle:60,1'  → [60,  60]   (60 req / 1 min)
     *   'throttle:5,10'  → [5,  600]   (5  req / 10 min)
     *   'throttle'       → [60,  60]   (defaults)
     *
     * @return array{0: int, 1: int} [maxAttempts, decaySeconds]
     */
    private function parseParams(): array
    {
        $raw = $_SERVER['HTTP_X_THROTTLE_PARAMS'] ?? '';

        if ($raw !== '' && str_starts_with($raw, 'throttle:')) {
            $parts = explode(',', substr($raw, strlen('throttle:')));
            $max   = isset($parts[0]) && is_numeric($parts[0]) && (int) $parts[0] > 0
                ? (int) $parts[0]
                : self::DEFAULT_MAX;
            $decay = isset($parts[1]) && is_numeric($parts[1]) && (int) $parts[1] > 0
                ? (int) $parts[1]
                : self::DEFAULT_DECAY_MINUTES;

            return [$max, $decay * 60];
        }

        return [self::DEFAULT_MAX, self::DEFAULT_DECAY_MINUTES * 60];
    }
}