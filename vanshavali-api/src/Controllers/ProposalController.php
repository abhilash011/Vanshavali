<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\MemberRepository;
use Vanshavali\Repositories\ProposalRepository;
use Vanshavali\Repositories\RelationshipRepository;
use Vanshavali\Services\AuditService;

/**
 * Controller for tree edit proposals and profile correction/removal workflow.
 */
class ProposalController
{
    private ProposalRepository $proposals;
    private MemberRepository $members;
    private RelationshipRepository $relationships;
    private AuditService $audit;

    public function __construct()
    {
        $this->proposals     = new ProposalRepository();
        $this->members       = new MemberRepository();
        $this->relationships = new RelationshipRepository();
        $this->audit         = new AuditService();
    }

    /**
     * GET /api/v1/proposals
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $communityId = $request->getAttribute('community_id');
        $status = $params['status'] ?? null;
        $limit = min((int)($params['limit'] ?? 50), 200);
        $offset = max((int)($params['offset'] ?? 0), 0);

        $list = $this->proposals->findAll($communityId, $status, $limit, $offset);
        $total = $this->proposals->countByStatus($communityId, $status);

        return Response::json($response, Response::success($list, [
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ]));
    }

    /**
     * GET /api/v1/proposals/{id}
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $proposal = $this->proposals->findById($id);

        if (!$proposal) {
            return Response::json($response, Response::error('NOT_FOUND', 'Proposal not found', 404));
        }

        return Response::json($response, Response::success($proposal));
    }

    /**
     * POST /api/v1/proposals
     * Submit a profile correction, edit, or relationship change request.
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        $communityId = $request->getAttribute('community_id');

        $validator = new Validator();
        $validator->required($data, ['proposal_type', 'target_entity_type']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Validation failed', 422, $validator->errors()));
        }

        $allowedTypes = [
            'edit_member',
            'remove_member',
            'create_member',
            'create_relationship',
            'remove_relationship',
            'profile_correction',
        ];

        if (!in_array($data['proposal_type'], $allowedTypes, true)) {
            return Response::json($response, Response::error('INVALID_TYPE', 'Invalid proposal type', 422));
        }

        $proposalId = $this->proposals->create([
            'proposal_type'      => $data['proposal_type'],
            'target_entity_type' => $data['target_entity_type'],
            'target_entity_id'   => isset($data['target_entity_id']) ? (int) $data['target_entity_id'] : null,
            'proposed_data'      => $data['proposed_data'] ?? [],
            'reason'             => $data['reason'] ?? null,
            'proposed_by'        => $userId,
            'community_id'       => $communityId,
        ]);

        $created = $this->proposals->findById($proposalId);
        $this->audit->log($userId, 'proposal.create', 'edit_proposal', $proposalId, null, $created);

        return Response::json($response, Response::success($created, [
            'message' => 'Proposal submitted for coordinator review',
        ], 201));
    }

    /**
     * POST /api/v1/proposals/{id}/approve
     * Approves a proposal and executes the underlying changes.
     */
    public function approve(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();
        $note = $body['note'] ?? 'Approved by coordinator/admin';

        $proposal = $this->proposals->findById($id);
        if (!$proposal) {
            return Response::json($response, Response::error('NOT_FOUND', 'Proposal not found', 404));
        }

        if ($proposal['status'] !== 'pending') {
            return Response::json($response, Response::error('INVALID_STATE', 'Proposal is already ' . $proposal['status'], 400));
        }

        $proposedData = $proposal['proposed_data'] ?? [];
        $appliedResult = null;

        // Apply changes based on proposal type
        switch ($proposal['proposal_type']) {
            case 'edit_member':
            case 'profile_correction':
                if (!empty($proposal['target_entity_id'])) {
                    $this->members->update((int) $proposal['target_entity_id'], $proposedData);
                    $appliedResult = $this->members->findById((int) $proposal['target_entity_id']);
                }
                break;

            case 'create_member':
                $newMemberId = $this->members->create($proposedData);
                $appliedResult = $this->members->findById($newMemberId);
                break;

            case 'remove_member':
                if (!empty($proposal['target_entity_id'])) {
                    $this->members->delete((int) $proposal['target_entity_id']);
                    $appliedResult = ['deleted_member_id' => $proposal['target_entity_id']];
                }
                break;

            case 'create_relationship':
                $relId = $this->relationships->create($proposedData);
                $appliedResult = $this->relationships->findById($relId);
                break;

            case 'remove_relationship':
                if (!empty($proposal['target_entity_id'])) {
                    $this->relationships->delete((int) $proposal['target_entity_id']);
                    $appliedResult = ['deleted_relationship_id' => $proposal['target_entity_id']];
                }
                break;
        }

        $this->proposals->updateStatus($id, 'approved', $userId, $note);
        $updatedProposal = $this->proposals->findById($id);

        $this->audit->log($userId, 'proposal.approve', 'edit_proposal', $id, $proposal, [
            'proposal' => $updatedProposal,
            'result'   => $appliedResult,
        ]);

        return Response::json($response, Response::success([
            'proposal' => $updatedProposal,
            'applied'  => $appliedResult,
        ], ['message' => 'Proposal approved and applied successfully']));
    }

    /**
     * POST /api/v1/proposals/{id}/reject
     */
    public function reject(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();
        $note = $body['note'] ?? 'Rejected by coordinator/admin';

        $proposal = $this->proposals->findById($id);
        if (!$proposal) {
            return Response::json($response, Response::error('NOT_FOUND', 'Proposal not found', 404));
        }

        if ($proposal['status'] !== 'pending') {
            return Response::json($response, Response::error('INVALID_STATE', 'Proposal is already ' . $proposal['status'], 400));
        }

        $this->proposals->updateStatus($id, 'rejected', $userId, $note);
        $updatedProposal = $this->proposals->findById($id);

        $this->audit->log($userId, 'proposal.reject', 'edit_proposal', $id, $proposal, $updatedProposal);

        return Response::json($response, Response::success($updatedProposal, [
            'message' => 'Proposal rejected',
        ]));
    }
}
