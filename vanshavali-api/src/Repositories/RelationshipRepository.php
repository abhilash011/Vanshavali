<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

class RelationshipRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a relationship between two members.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO relationships (
                member_id, related_member_id, relationship_type, verification_status,
                effective_date, proposed_by, approved_by, created_at, updated_at
            ) VALUES (
                :member_id, :related_member_id, :type, :status,
                :effective_date, :proposed_by, :approved_by, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':member_id'         => $data['member_id'],
            ':related_member_id' => $data['related_member_id'],
            ':type'              => $data['relationship_type'],
            ':status'            => $data['verification_status'] ?? 'approved',
            ':effective_date'    => $data['effective_date'] ?? null,
            ':proposed_by'       => $data['proposed_by'] ?? null,
            ':approved_by'       => $data['approved_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a relationship.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['relationship_type', 'verification_status', 'effective_date', 'approved_by'];
        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $fieldStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE relationships SET {$fieldStr} WHERE id = :id");
        return $stmt->execute($params);
    }

    /**
     * Get all relationships for a member.
     */
    public function findByMemberId(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, m.name as related_name, m.gender as related_gender, m.profile_image as related_image
            FROM relationships r
            JOIN members m ON m.id = r.related_member_id
            WHERE r.member_id = :member_id
            ORDER BY r.relationship_type ASC
        ");
        $stmt->execute([':member_id' => $memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Get full family tree graph (nodes + edges) for a root member.
     * Excludes private members for non-admin users.
     */
    public function getTreeGraph(int $rootMemberId, ?string $requesterRole = null): array
    {
        $visited = [];
        $queue = [$rootMemberId];
        $nodes = [];
        $edges = [];
        $edgeKeys = []; // Track unique edges to avoid duplicates

        while (!empty($queue)) {
            $currentId = array_shift($queue);

            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;

            // Get member node
            $stmt = $this->db->prepare("SELECT * FROM members WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $currentId]);
            $member = $stmt->fetch();

            if (!$member) {
                continue;
            }

            // Skip private members for non-admins (except the root)
            if ($currentId !== $rootMemberId
                && $member['visibility'] === 'private'
                && $requesterRole !== 'platform_admin'
                && $requesterRole !== 'community_admin') {
                continue;
            }

            $nodes[] = $member;

            // Get approved relationships
            $stmt = $this->db->prepare("
                SELECT * FROM relationships
                WHERE (member_id = :id OR related_member_id = :id2)
                  AND verification_status = 'approved'
            ");
            $stmt->execute([':id' => $currentId, ':id2' => $currentId]);
            $rels = $stmt->fetchAll();

            foreach ($rels as $rel) {
                // De-duplicate edges
                $edgeKey = min($rel['member_id'], $rel['related_member_id']) . '-' .
                           max($rel['member_id'], $rel['related_member_id']) . '-' .
                           $rel['relationship_type'];
                if (!in_array($edgeKey, $edgeKeys)) {
                    $edges[] = $rel;
                    $edgeKeys[] = $edgeKey;
                }

                $otherId = ($rel['member_id'] == $currentId) ? $rel['related_member_id'] : $rel['member_id'];
                if (!in_array($otherId, $visited)) {
                    $queue[] = $otherId;
                }
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * Delete a relationship.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM relationships WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find a specific relationship by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM relationships WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $rel = $stmt->fetch();
        return $rel ?: null;
    }

    /**
     * Check if a relationship already exists between two members.
     */
    public function exists(int $memberId, int $relatedMemberId, string $type): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM relationships
            WHERE member_id = :m1 AND related_member_id = :m2 AND relationship_type = :type
            LIMIT 1
        ");
        $stmt->execute([':m1' => $memberId, ':m2' => $relatedMemberId, ':type' => $type]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
