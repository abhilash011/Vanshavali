<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Vanshavali\Controllers\AuthController;
use Vanshavali\Controllers\MemberController;
use Vanshavali\Controllers\ProfileController;
use Vanshavali\Controllers\RelationshipController;
use Vanshavali\Controllers\ProposalController;
use Vanshavali\Controllers\MediaController;
use Vanshavali\Controllers\CommunityController;
use Vanshavali\Controllers\EventController;
use Vanshavali\Controllers\AnnouncementController;
use Vanshavali\Controllers\GalleryController;
use Vanshavali\Middleware\AuthMiddleware;
use Vanshavali\Middleware\CorsMiddleware;
use Vanshavali\Middleware\RateLimitMiddleware;
use Vanshavali\Middleware\RbacMiddleware;
use Vanshavali\Middleware\TenantMiddleware;

// Load autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'JWT_SECRET']);

// Create Slim app
$app = AppFactory::create();

// ===================
// GLOBAL MIDDLEWARE
// ===================
$app->addBodyParsingMiddleware(); // JSON body parsing
$app->addRoutingMiddleware();

// CORS — must be first (outermost) middleware
$app->add(new CorsMiddleware());

// Error handling
$errorMiddleware = $app->addErrorMiddleware(
    (bool)($_ENV['APP_DEBUG'] ?? false),
    true,
    true
);

// Custom error handler for JSON responses
$errorMiddleware->setDefaultErrorHandler(function (
    \Psr\Http\Message\ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails
) use ($app) {
    $statusCode = 500;
    if ($exception instanceof \Slim\Exception\HttpNotFoundException) {
        $statusCode = 404;
    } elseif ($exception instanceof \Slim\Exception\HttpMethodNotAllowedException) {
        $statusCode = 405;
    }

    $response = $app->getResponseFactory()->createResponse();
    $body = [
        'success' => false,
        'data'    => null,
        'error'   => [
            'code'    => 'SERVER_ERROR',
            'message' => $displayErrorDetails ? $exception->getMessage() : 'An internal error occurred',
        ],
    ];

    $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($statusCode);
});

// ===================
// ROUTES
// ===================

// Health check — no auth required
$app->get('/api/v1/health', function ($request, $response) {
    $data = [
        'status'  => 'ok',
        'version' => '1.0.0',
        'phase'   => 'Phase 3: Community & Content',
        'time'    => date('c'),
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// CORS preflight for all routes
$app->options('/{routes:.+}', function ($request, $response) {
    return $response->withStatus(204);
});

// ----- Auth routes (public, rate-limited) -----
$authController = new AuthController();
$rateLimiter = new RateLimitMiddleware(10, 60); // 10 requests per minute for auth
$strictRateLimiter = new RateLimitMiddleware(5, 60); // 5 requests per minute for sensitive ops

$app->post('/api/v1/auth/register', [$authController, 'register'])
    ->add($rateLimiter);

$app->post('/api/v1/auth/login', [$authController, 'login'])
    ->add($rateLimiter);

$app->post('/api/v1/auth/refresh', [$authController, 'refresh'])
    ->add($rateLimiter);

$app->post('/api/v1/auth/logout', [$authController, 'logout']);

// Email verification (public, rate-limited)
$app->post('/api/v1/auth/verify-email', [$authController, 'verifyEmail'])
    ->add($rateLimiter);

$app->post('/api/v1/auth/resend-verification', [$authController, 'resendVerification'])
    ->add($strictRateLimiter);

// Password reset (public, strictly rate-limited)
$app->post('/api/v1/auth/forgot-password', [$authController, 'forgotPassword'])
    ->add($strictRateLimiter);

$app->post('/api/v1/auth/reset-password', [$authController, 'resetPassword'])
    ->add($strictRateLimiter);

// ----- Phase 3 Controllers -----
$communityController    = new CommunityController();
$eventController        = new EventController();
$announcementController = new AnnouncementController();
$galleryController      = new GalleryController();
$memberController       = new MemberController();
$relationshipController = new RelationshipController();
$proposalController     = new ProposalController();
$mediaController        = new MediaController();
$profileController      = new ProfileController();

// ===============================================
// PUBLIC COMMUNITY CONTENT ROUTES (No auth required)
// Task 3.4, 3.5, 3.6, 3.7, 3.8
// ===============================================
$app->group('/api/v1', function ($group) use (
    $communityController,
    $eventController,
    $announcementController,
    $galleryController,
    $memberController
) {
    // Communities & Chapters (Task 3.1, 3.4)
    $group->get('/communities', [$communityController, 'index']);
    $group->get('/communities/{idOrSlug}', [$communityController, 'show']);
    $group->get('/communities/{id:[0-9]+}/chapters', [$communityController, 'getChapters']);

    // Events (Task 3.2, 3.5)
    $group->get('/events', [$eventController, 'index']);
    $group->get('/events/{id:[0-9]+}', [$eventController, 'show']);
    $group->post('/events/{id:[0-9]+}/rsvp', [$eventController, 'rsvp']);

    // Announcements & News (Task 3.2, 3.6)
    $group->get('/announcements', [$announcementController, 'index']);
    $group->get('/announcements/{id:[0-9]+}', [$announcementController, 'show']);

    // Media Gallery & Lightbox (Task 3.3, 3.7)
    $group->get('/gallery/albums', [$galleryController, 'albums']);
    $group->get('/gallery/albums/{id:[0-9]+}', [$galleryController, 'getAlbum']);

    // Public Member Directory (Task 3.8)
    $group->get('/directory', [$memberController, 'index']);
    $group->get('/directory/search', [$memberController, 'search']);
});

// ----- Auth middleware instances -----
$authMiddleware = new AuthMiddleware();
$tenantMiddleware = new TenantMiddleware();

// ----- Authenticated routes (password change) -----
$app->post('/api/v1/auth/change-password', [$authController, 'changePassword'])
    ->add($authMiddleware);

// ----- Profile routes (authenticated, self-service) -----
$app->group('/api/v1', function ($group) use ($profileController) {
    $group->get('/profile', [$profileController, 'show']);
    $group->put('/profile', [$profileController, 'update']);
    $group->get('/users/{id}', [$profileController, 'viewUser']);
})->add($authMiddleware);

// ----- Protected routes (require JWT + tenant scoping) -----
$app->group('/api/v1', function ($group) use (
    $memberController,
    $relationshipController,
    $proposalController,
    $mediaController,
    $communityController,
    $eventController,
    $announcementController,
    $galleryController
) {
    // Member directory search
    $group->get('/members/search', [$memberController, 'search']);

    // Members CRUD
    $group->get('/members', [$memberController, 'index']);
    $group->get('/members/{id}', [$memberController, 'show']);
    $group->post('/members', [$memberController, 'create']);
    $group->put('/members/{id}', [$memberController, 'update']);
    $group->patch('/members/{id}/visibility', [$memberController, 'updateVisibility']);
    $group->delete('/members/{id}', [$memberController, 'delete'])
        ->add(new RbacMiddleware('family_coordinator'));

    // Family tree visualization endpoints
    $group->get('/members/{id}/tree', [$relationshipController, 'tree']);
    $group->get('/families/{id}/tree', [$relationshipController, 'tree']);

    // Relationships CRUD
    $group->get('/members/{id}/relationships', [$relationshipController, 'getForMember']);
    $group->post('/relationships', [$relationshipController, 'create']);
    $group->put('/relationships/{id}', [$relationshipController, 'update']);
    $group->delete('/relationships/{id}', [$relationshipController, 'delete']);

    // Proposals workflow
    $group->get('/proposals', [$proposalController, 'index']);
    $group->post('/proposals', [$proposalController, 'create']);
    $group->get('/proposals/{id}', [$proposalController, 'show']);
    $group->post('/proposals/{id}/approve', [$proposalController, 'approve'])
        ->add(new RbacMiddleware('family_coordinator'));
    $group->post('/proposals/{id}/reject', [$proposalController, 'reject'])
        ->add(new RbacMiddleware('family_coordinator'));

    // Media upload with consent capture & EXIF stripping
    $group->post('/media/upload', [$mediaController, 'upload']);
    $group->get('/media/{id}', [$mediaController, 'show']);

    // Phase 3: Content Management (Events, Announcements, Chapters, Albums)
    $group->post('/events', [$eventController, 'create'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->put('/events/{id:[0-9]+}', [$eventController, 'update'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->delete('/events/{id:[0-9]+}', [$eventController, 'delete'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->get('/events/{id:[0-9]+}/rsvps', [$eventController, 'getRsvps'])
        ->add(new RbacMiddleware('content_moderator'));

    $group->post('/announcements', [$announcementController, 'create'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->put('/announcements/{id:[0-9]+}', [$announcementController, 'update'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->delete('/announcements/{id:[0-9]+}', [$announcementController, 'delete'])
        ->add(new RbacMiddleware('content_moderator'));

    $group->post('/gallery/albums', [$galleryController, 'createAlbum'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->put('/gallery/albums/{id:[0-9]+}', [$galleryController, 'updateAlbum'])
        ->add(new RbacMiddleware('content_moderator'));
    $group->delete('/gallery/albums/{id:[0-9]+}', [$galleryController, 'deleteAlbum'])
        ->add(new RbacMiddleware('content_moderator'));

    $group->post('/communities', [$communityController, 'create'])
        ->add(new RbacMiddleware('platform_admin'));
    $group->put('/communities/{id:[0-9]+}', [$communityController, 'update'])
        ->add(new RbacMiddleware('community_admin'));
    $group->post('/communities/{id:[0-9]+}/chapters', [$communityController, 'createChapter'])
        ->add(new RbacMiddleware('community_admin'));
    $group->put('/chapters/{id:[0-9]+}', [$communityController, 'updateChapter'])
        ->add(new RbacMiddleware('community_admin'));
    $group->delete('/chapters/{id:[0-9]+}', [$communityController, 'deleteChapter'])
        ->add(new RbacMiddleware('community_admin'));

})->add($tenantMiddleware)->add($authMiddleware);

// ===================
// RUN
// ===================
$app->run();
