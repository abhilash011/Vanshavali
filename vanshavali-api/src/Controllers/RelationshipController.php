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

/**
 * Controller for relationship management & family tree graph operations.
 */
class RelationshipController
{
    private RelationshipRepository $relationships;
    private MemberRepository $members;
    private AuditService $audit;

    public const ALLOWED_TYPES = [
        'parent',
        'child',
        'spouse',
        'sibling',
        'guardian',
        'adopted',
        'step',
        'unknown',
    ];

    public function __construct()
    {
        $this->relationships = new RelationshipRepository();
        $this->members       = new MemberRepository();
        $this->audit         = new AuditService();
    }

    /**
     * GET /api/v1/members/{id}/relationships
     */
    public function getForMember(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $memberId = (int) $args['id'];
        $member = $this->members->findById($memberId);

        if (!$member) {
            return Response::json($response, Response::error('NOT_FOUND', 'Member not found', 404));
        }

        $rels = $this->relationships->findByMemberId($memberId);
        return Response::json($response, Response::success($rels));
    }

    /**
     * POST /api/v1/relationships
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role') ?? 'member';

        $validator = new Validator();
        $validator->required($data, ['member_id', 'related_member_id', 'relationship_type']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Validation failed', 422, $validator->errors()));
        }

        $memberId = (int) $data['member_id'];
        $relatedId = (int) $data['related_member_id'];
        $type = (string) $data['relationship_type'];

        if ($memberId === $relatedId) {
            return Response::json($response, Response::error('INVALID_RELATIONSHIP', 'A member cannot have a relationship with themselves', 422));
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return Response::json($response, Response::error('INVALID_TYPE', 'Invalid relationship type: ' . $type, 422));
        }

        // Verify both members exist
        $m1 = $this->members->findById($memberId);
        $m2 = $this->members->findById($relatedId);
        if (!$m1 || !$m2) {
            return Response::json($response, Response::error('NOT_FOUND', 'One or both members not found', 404));
        }

        // Check for duplicates
        if ($this->relationships->exists($memberId, $relatedId, $type)) {
            return Response::json($response, Response::error('DUPLICATE_RELATIONSHIP', 'This relationship already exists', 409));
        }

        // Auto-approve if coordinator or admin, else default to approved if internal direct entry
        $status = in_array($userRole, ['platform_admin', 'community_admin', 'family_coordinator'], true) ? 'approved' : 'approved';

        $relId = $this->relationships->create([
            'member_id'           => $memberId,
            'related_member_id'   => $relatedId,
            'relationship_type'   => $type,
            'verification_status' => $status,
            'effective_date'      => $data['effective_date'] ?? null,
            'proposed_by'         => $userId,
            'approved_by'         => ($status === 'approved') ? $userId : null,
        ]);

        // Automatically create the reciprocal relationship where appropriate
        $reciprocalType = $this->getReciprocalType($type, $m1['gender'] ?? null);
        if ($reciprocalType && !$this->relationships->exists($relatedId, $memberId, $reciprocalType)) {
            $this->relationships->create([
                'member_id'           => $relatedId,
                'related_member_id'   => $memberId,
                'relationship_type'   => $reciprocalType,
                'verification_status' => $status,
                'effective_date'      => $data['effective_date'] ?? null,
                'proposed_by'         => $userId,
                'approved_by'         => ($status === 'approved') ? $userId : null,
            ]);
        }

        $created = $this->relationships->findById($relId);
        $this->audit->log($userId, 'relationship.create', 'relationship', $relId, null, $created);

        return Response::json($response, Response::success($created, [], 201));
    }

    /**
     * PUT /api/v1/relationships/{id}
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->relationships->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Relationship not found', 404));
        }

        if (isset($data['relationship_type']) && !in_array($data['relationship_type'], self::ALLOWED_TYPES, true)) {
            return Response::json($response, Response::error('INVALID_TYPE', 'Invalid relationship type', 422));
        }

        $this->relationships->update($id, $data);
        $updated = $this->relationships->findById($id);

        $this->audit->log($userId, 'relationship.update', 'relationship', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * DELETE /api/v1/relationships/{id}
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');

        $existing = $this->relationships->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Relationship not found', 404));
        }

        $this->relationships->delete($id);
        $this->audit->log($userId, 'relationship.delete', 'relationship', $id, $existing, null);

        return Response::json($response, Response::success(['message' => 'Relationship removed']));
    }

    /**
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
     * Helper to compute reciprocal relationship.
     */
    private function getReciprocalType(string $type, ?string $originGender): ?string
    {
        return match ($type) {
            'parent' => 'child',
            'child' => 'parent',
            'spouse' => 'spouse',
            'sibling' => 'sibling',
            'guardian' => 'child',
            'adopted' => 'parent',
            'step' => 'step',
            default => null,
        };
    }
}
