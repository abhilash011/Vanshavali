<?php

declare(strict_types=1);

namespace Vanshavali\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vanshavali\Helpers\Response;

class RbacMiddleware implements MiddlewareInterface
{
    /**
     * Role hierarchy — higher-level roles inherit all lower-level permissions.
     */
    private const ROLE_HIERARCHY = [
        'visitor'             => 0,
        'member'              => 1,
        'family_coordinator'  => 2,
        'content_moderator'   => 3,
        'community_admin'     => 4,
        'platform_admin'      => 5,
    ];

    private int $minimumLevel;

    public function __construct(string $minimumRole = 'member')
    {
        $this->minimumLevel = self::ROLE_HIERARCHY[$minimumRole] ?? 1;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $role = $request->getAttribute('user_role', 'visitor');
        $userLevel = self::ROLE_HIERARCHY[$role] ?? 0;

        if ($userLevel < $this->minimumLevel) {
            $psr7Response = new \Slim\Psr7\Response();
            return Response::json($psr7Response, Response::error(
                'FORBIDDEN',
                'You do not have permission to access this resource',
                403
            ));
        }

        return $handler->handle($request);
    }

    /**
     * Check if a given role has at least the required level.
     */
    public static function hasRole(string $userRole, string $requiredRole): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$userRole] ?? 0;
        $requiredLevel = self::ROLE_HIERARCHY[$requiredRole] ?? 0;
        return $userLevel >= $requiredLevel;
    }
}
