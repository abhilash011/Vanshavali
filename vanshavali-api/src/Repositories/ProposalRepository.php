<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

/**
 * Repository for edit proposals (approval workflow).
 */
class ProposalRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new proposal.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO edit_proposals (
                proposal_type, target_entity_type, target_entity_id,
                proposed_data, reason, status, proposed_by, community_id, created_at, updated_at
            ) VALUES (
                :type, :entity_type, :entity_id,
                :data, :reason, 'pending', :proposed_by, :community_id, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':type'         => $data['proposal_type'],
            ':entity_type'  => $data['target_entity_type'] ?? 'member',
            ':entity_id'    => $data['target_entity_id'] ?? null,
            ':data'         => json_encode($data['proposed_data']),
            ':reason'       => $data['reason'] ?? null,
            ':proposed_by'  => $data['proposed_by'],
            ':community_id' => $data['community_id'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a proposal by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, u.name as proposer_name
            FROM edit_proposals p
            LEFT JOIN users u ON u.id = p.proposed_by
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $proposal = $stmt->fetch();
        if ($proposal && $proposal['proposed_data']) {
            $proposal['proposed_data'] = json_decode($proposal['proposed_data'], true);
        }
        return $proposal ?: null;
    }

    /**
     * List proposals, optionally filtered by community and status.
     */
    public function findAll(?int $communityId = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT p.*, u.name as proposer_name FROM edit_proposals p LEFT JOIN users u ON u.id = p.proposed_by WHERE 1=1";
        $params = [];

        if ($communityId !== null) {
            $sql .= " AND p.community_id = :cid";
            $params[':cid'] = $communityId;
        }
        if ($status !== null) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll();
        foreach ($results as &$r) {
            if ($r['proposed_data']) {
                $r['proposed_data'] = json_decode($r['proposed_data'], true);
            }
        }
        return $results;
    }

    /**
     * Count proposals by status.
     */
    public function countByStatus(?int $communityId = null, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM edit_proposals WHERE 1=1";
        $params = [];
        if ($communityId !== null) {
            $sql .= " AND community_id = :cid";
            $params[':cid'] = $communityId;
        }
        if ($status !== null) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Update proposal status (approve / reject).
     */
    public function updateStatus(int $id, string $status, int $reviewedBy, ?string $reviewNote = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE edit_proposals
            SET status = :status, reviewed_by = :reviewed_by, review_note = :note, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':status'      => $status,
            ':reviewed_by' => $reviewedBy,
            ':note'        => $reviewNote,
            ':id'          => $id,
        ]);
    }
}
