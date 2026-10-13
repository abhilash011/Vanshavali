<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\CommunityRepository;
use Vanshavali\Services\AuditService;

/**
 * Controller for communities and regional chapters.
 */
class CommunityController
{
    private CommunityRepository $communities;
    private AuditService $audit;

    public function __construct()
    {
        $this->communities = new CommunityRepository();
        $this->audit       = new AuditService();
    }

    /**
     * GET /api/v1/communities (public)
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = min((int)($params['limit'] ?? 50), 100);
        $offset = max((int)($params['offset'] ?? 0), 0);

        $list = $this->communities->findAll($limit, $offset);
        return Response::json($response, Response::success($list, ['count' => count($list)]));
    }

    /**
     * GET /api/v1/communities/{idOrSlug} (public)
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $idOrSlug = $args['idOrSlug'];
        $community = $this->communities->findByIdOrSlug($idOrSlug);

        if (!$community) {
            return Response::json($response, Response::error('NOT_FOUND', 'Community not found', 404));
        }

        // Attach chapters
        $community['chapters'] = $this->communities->getChapters((int)$community['id']);

        return Response::json($response, Response::success($community));
    }

    /**
     * POST /api/v1/communities (Admin only)
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $validator = new Validator();
        $validator->required($data, ['name', 'slug']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Validation failed', 422, $validator->errors()));
        }

        $id = $this->communities->create($data);
        $created = $this->communities->findByIdOrSlug($id);
        $this->audit->log($userId, 'community.create', 'community', $id, null, $created);

        return Response::json($response, Response::success($created, [], 201));
    }

    /**
     * PUT /api/v1/communities/{id} (Admin only)
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->communities->findByIdOrSlug($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Community not found', 404));
        }

        $this->communities->update($id, $data);
        $updated = $this->communities->findByIdOrSlug($id);
        $this->audit->log($userId, 'community.update', 'community', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * GET /api/v1/communities/{id}/chapters (public)
     */
    public function getChapters(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $communityId = (int) $args['id'];
        $chapters = $this->communities->getChapters($communityId);
        return Response::json($response, Response::success($chapters));
    }

    /**
     * POST /api/v1/communities/{id}/chapters (Admin/Coordinator)
     */
    public function createChapter(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $communityId = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $validator = new Validator();
        $validator->required($data, ['name', 'city']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Validation failed', 422, $validator->errors()));
        }

        $data['community_id'] = $communityId;
        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));
        }

        $chapterId = $this->communities->createChapter($data);
        $created = $this->communities->getChapterById($chapterId);
        $this->audit->log($userId, 'chapter.create', 'chapter', $chapterId, null, $created);

        return Response::json($response, Response::success($created, [], 201));
    }

    /**
     * PUT /api/v1/chapters/{id} (Admin/Coordinator)
     */
    public function updateChapter(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->communities->getChapterById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Chapter not found', 404));
        }

        $this->communities->updateChapter($id, $data);
        $updated = $this->communities->getChapterById($id);
        $this->audit->log($userId, 'chapter.update', 'chapter', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * DELETE /api/v1/chapters/{id} (Admin/Coordinator)
     */
    public function deleteChapter(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');

        $existing = $this->communities->getChapterById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Chapter not found', 404));
        }

        $this->communities->deleteChapter($id);
        $this->audit->log($userId, 'chapter.delete', 'chapter', $id, $existing, null);

        return Response::json($response, Response::success(['message' => 'Chapter deleted successfully']));
    }
}
