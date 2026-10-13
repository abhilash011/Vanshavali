<?php

declare(strict_types=1);

namespace Vanshavali\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vanshavali\Helpers\JwtHelper;
use Vanshavali\Helpers\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private JwtHelper $jwt;

    public function __construct()
    {
        $this->jwt = new JwtHelper();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            $psr7Response = new \Slim\Psr7\Response();
            return Response::json($psr7Response, Response::error(
                'AUTH_REQUIRED',
                'Authentication token is required',
                401
            ));
        }

        $token = substr($authHeader, 7);
        $claims = $this->jwt->validate($token);

        if ($claims === null) {
            $psr7Response = new \Slim\Psr7\Response();
            return Response::json($psr7Response, Response::error(
                'INVALID_TOKEN',
                'Token is invalid or expired',
                401
            ));
        }

        // Verify it's an access token, not a refresh token
        if (($claims->type ?? '') !== 'access') {
            $psr7Response = new \Slim\Psr7\Response();
            return Response::json($psr7Response, Response::error(
                'INVALID_TOKEN_TYPE',
                'Access token required',
                401
            ));
        }

        // Attach user claims to the request for downstream use
        $request = $request->withAttribute('user_id', $claims->sub);
        $request = $request->withAttribute('user_email', $claims->email);
        $request = $request->withAttribute('user_role', $claims->role);
        $request = $request->withAttribute('community_id', $claims->community_id ?? null);

        return $handler->handle($request);
    }
}
