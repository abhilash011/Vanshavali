<?php

declare(strict_types=1);

namespace Vanshavali\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vanshavali\Helpers\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $cacheDir;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests   = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->cacheDir      = dirname(__DIR__, 2) . '/logs/ratelimit';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = md5($ip);
        $file = $this->cacheDir . "/{$key}.json";

        $now = time();
        $data = ['count' => 0, 'window_start' => $now];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
        }

        // Reset window if expired
        if ($now - $data['window_start'] >= $this->windowSeconds) {
            $data = ['count' => 0, 'window_start' => $now];
        }

        $data['count']++;

        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['count'] > $this->maxRequests) {
            $remaining = $this->windowSeconds - ($now - $data['window_start']);
            $psr7Response = new \Slim\Psr7\Response();
            $psr7Response = $psr7Response
                ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('Retry-After', (string)$remaining);

            return Response::json($psr7Response, Response::error(
                'RATE_LIMITED',
                "Too many requests. Try again in {$remaining} seconds.",
                429
            ));
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string)max(0, $this->maxRequests - $data['count']));
    }
}
