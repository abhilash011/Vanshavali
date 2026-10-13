<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\MemberRepository;
use Vanshavali\Repositories\RelationshipRepository;
use Vanshavali\Services\AuditService;

class MemberController
{
    private MemberRepository $members;
    private RelationshipRepository $relationships;
    private AuditService $audit;

    public function __construct()
    {
        $this->members       = new MemberRepository();
        $this->relationships = new RelationshipRepository();
        $this->audit         = new AuditService();
    }

    /**
     * GET /api/v1/members
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $communityId = $request->getAttribute('community_id');
        $userRole = $request->getAttribute('user_role');
        $limit  = min((int)($params['limit'] ?? 100), 500);
        $offset = max((int)($params['offset'] ?? 0), 0);
        $search = $params['search'] ?? ($params['q'] ?? null);

        if ($search) {
            $members = $this->members->search($search, $communityId, $limit);
            $total = count($members);
        } else {
            $members = $this->members->findAll($communityId, $limit, $offset, $userRole);
            $total = $this->members->count($communityId);
        }

        return Response::json($response, Response::success($members, [
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ]));
    }

    /**
     * GET /api/v1/members/search
     */
    public function search(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $query = trim($params['q'] ?? ($params['search'] ?? ''));
        $communityId = $request->getAttribute('community_id');
        $limit = min((int)($params['limit'] ?? 20), 100);

        if ($query === '') {
            return Response::json($response, Response::success([], ['total' => 0]));
        }

        $results = $this->members->search($query, $communityId, $limit);

        return Response::json($response, Response::success($results, [
            'total' => count($results),
            'query' => $query,
        ]));
    }

    /**
     * GET /api/v1/members/{id}
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userRole = $request->getAttribute('user_role');
        $member = $this->members->findById($id);

        if (!$member) {
            return Response::json($response, Response::error(
                'NOT_FOUND',
                'Member not found',
                404
            ));
        }

        // Privacy enforcement: non-admins cannot view private members
        if ($member['visibility'] === 'private' && $userRole !== 'platform_admin' && $userRole !== 'community_admin') {
            return Response::json($response, Response::error(
                'FORBIDDEN',
                'This member profile is marked as private.',
                403
            ));
        }

        // Attach relationships
        $member['relationships'] = $this->relationships->findByMemberId($id);

        return Response::json($response, Response::success($member));
    }

    /**
     * POST /api/v1/members
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $validator = new Validator();
        $validator->required($data, ['name']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Please correct the following errors',
                422,
                $validator->errors()
            ));
        }

        $data['community_id'] = $request->getAttribute('community_id');
        $memberId = $this->members->create($data);

        // Create relationships if provided (e.g. parent/spouse/child IDs)
        $this->createRelationshipsFromInput($memberId, $data);

        $member = $this->members->findById($memberId);
        $this->audit->log($userId, 'member.create', 'member', $memberId, null, $member);

        return Response::json($response, Response::success($member, [], 201));
    }

    /**
     * PUT /api/v1/members/{id}
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->members->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Member not found', 404));
        }

        $this->members->update($id, $data);
        $updated = $this->members->findById($id);

        $this->audit->log($userId, 'member.update', 'member', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * PATCH /api/v1/members/{id}/visibility
     * 2.1: Field-level visibility settings (private/family-only/community-only/public)
     */
    public function updateVisibility(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        $visibility = $data['visibility'] ?? null;

        $allowed = ['private', 'family_only', 'community_only', 'public'];
        if (!$visibility || !in_array($visibility, $allowed, true)) {
            return Response::json($response, Response::error(
                'INVALID_VISIBILITY',
                'Visibility must be one of: ' . implode(', ', $allowed),
                422
            ));
        }

        $existing = $this->members->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Member not found', 404));
        }

        $this->members->updateVisibility($id, $visibility);
        $updated = $this->members->findById($id);

        $this->audit->log($userId, 'member.visibility_change', 'member', $id, ['visibility' => $existing['visibility']], ['visibility' => $visibility]);

        return Response::json($response, Response::success($updated, [
            'message' => 'Member visibility updated to ' . $visibility,
        ]));
    }

    /**
     * DELETE /api/v1/members/{id}
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');

        $existing = $this->members->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Member not found', 404));
        }

        $this->members->delete($id);
        $this->audit->log($userId, 'member.delete', 'member', $id, $existing, null);

        return Response::json($response, Response::success(['message' => 'Member deleted']));
    }

    /**
     * GET /api/v1/families/{id}/tree
     * GET /api/v1/members/{id}/tree
     */
    public function tree(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $rootId = (int) $args['id'];
        $userRole = $request->getAttribute('user_role');

        $member = $this->members->findById($rootId);
        if (!$member) {
            return Response::json($response, Response::error('NOT_FOUND', 'Root member not found', 404));
        }

        $graph = $this->relationships->getTreeGraph($rootId, $userRole);

        return Response::json($response, Response::success($graph));
    }

    /**
     * Helper: Create relationships from flat input (fid, mid, pids) for legacy compatibility.
     */
    private function createRelationshipsFromInput(int $memberId, array $data): void
    {
        if (!empty($data['fid'])) {
            $this->relationships->create([
                'member_id'         => $memberId,
                'related_member_id' => (int) $data['fid'],
                'relationship_type' => 'child',
            ]);
            $this->relationships->create([
                'member_id'         => (int) $data['fid'],
                'related_member_id' => $memberId,
                'relationship_type' => 'parent',
            ]);
        }

        if (!empty($data['mid'])) {
            $this->relationships->create([
                'member_id'         => $memberId,
                'related_member_id' => (int) $data['mid'],
                'relationship_type' => 'child',
            ]);
            $this->relationships->create([
                'member_id'         => (int) $data['mid'],
                'related_member_id' => $memberId,
                'relationship_type' => 'parent',
            ]);
        }

        if (!empty($data['pids']) && is_array($data['pids'])) {
            foreach ($data['pids'] as $partnerId) {
                $this->relationships->create([
                    'member_id'         => $memberId,
                    'related_member_id' => (int) $partnerId,
                    'relationship_type' => 'spouse',
                ]);
                $this->relationships->create([
                    'member_id'         => (int) $partnerId,
                    'related_member_id' => $memberId,
                    'relationship_type' => 'spouse',
                ]);
            }
        }
    }
}
