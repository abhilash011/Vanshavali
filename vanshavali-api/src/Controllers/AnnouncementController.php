<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\AnnouncementRepository;
use Vanshavali\Services\AuditService;

/**
 * Controller for community announcements, festival notices, and obituaries.
 */
class AnnouncementController
{
    private AnnouncementRepository $announcements;
    private AuditService $audit;

    public function __construct()
    {
        $this->announcements = new AnnouncementRepository();
        $this->audit         = new AuditService();
    }

    /**
     * GET /api/v1/announcements (public)
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $communityId = isset($params['community_id']) ? (int)$params['community_id'] : null;
        $category = $params['category'] ?? null;
        $limit = min((int)($params['limit'] ?? 50), 100);
        $offset = max((int)($params['offset'] ?? 0), 0);

        $list = $this->announcements->findAll($communityId, $category, 'published', $limit, $offset);
        return Response::json($response, Response::success($list, ['count' => count($list)]));
    }

    /**
     * GET /api/v1/announcements/{id} (public)
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $ann = $this->announcements->findById($id);

        if (!$ann) {
            return Response::json($response, Response::error('NOT_FOUND', 'Announcement not found', 404));
        }

        return Response::json($response, Response::success($ann));
    }

    /**
     * POST /api/v1/announcements (Moderator / Admin)
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        $communityId = $request->getAttribute('community_id');

        $validator = new Validator();
        $validator->required($data, ['title', 'content']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Validation failed', 422, $validator->errors()));
        }

        $data['community_id'] = $communityId ?? ($data['community_id'] ?? 1);
        $data['author_id'] = $userId;
        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title']), '-'));
        }

        $id = $this->announcements->create($data);
        $created = $this->announcements->findById($id);
        $this->audit->log($userId, 'announcement.create', 'announcement', $id, null, $created);

        return Response::json($response, Response::success($created, [], 201));
    }

    /**
     * PUT /api/v1/announcements/{id} (Moderator / Admin)
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->announcements->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Announcement not found', 404));
        }

        $this->announcements->update($id, $data);
        $updated = $this->announcements->findById($id);
        $this->audit->log($userId, 'announcement.update', 'announcement', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * DELETE /api/v1/announcements/{id} (Moderator / Admin)
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');

        $existing = $this->announcements->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Announcement not found', 404));
        }

        $this->announcements->delete($id);
        $this->audit->log($userId, 'announcement.delete', 'announcement', $id, $existing, null);

        return Response::json($response, Response::success(['message' => 'Announcement deleted successfully']));
    }
}
