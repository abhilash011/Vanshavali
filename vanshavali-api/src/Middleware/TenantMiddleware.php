<?php

declare(strict_types=1);

namespace Vanshavali\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vanshavali\Helpers\Response;

/**
 * Multi-tenant scoping middleware.
 *
 * Ensures that every authenticated request is scoped to the user's community.
 * Platform admins bypass tenant scoping (they can access all communities).
 */
class TenantMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $role = $request->getAttribute('user_role', 'visitor');
        $communityId = $request->getAttribute('community_id');

        // Platform admins bypass tenant scoping
        if ($role === 'platform_admin') {
            return $handler->handle($request);
        }

        // All other authenticated users MUST have a community_id
        if (empty($communityId)) {
            $psr7Response = new \Slim\Psr7\Response();
            return Response::json($psr7Response, Response::error(
                'TENANT_REQUIRED',
                'Your account is not associated with a community. Please contact an administrator.',
                403
            ));
        }

        // Attach community_id to request for downstream controllers
        $request = $request->withAttribute('tenant_community_id', (int) $communityId);

        return $handler->handle($request);
    }
}
