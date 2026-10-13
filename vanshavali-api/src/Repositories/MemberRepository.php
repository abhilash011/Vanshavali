<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

class MemberRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all members, optionally filtered by community and visibility.
     */
    public function findAll(?int $communityId = null, int $limit = 100, int $offset = 0, ?string $requesterRole = null): array
    {
        $sql = "SELECT * FROM members";
        $conditions = [];
        $params = [];

        if ($communityId !== null) {
            $conditions[] = "community_id = :community_id";
            $params[':community_id'] = $communityId;
        }

        // Enforce visibility for non-admin users
        if ($requesterRole && $requesterRole !== 'platform_admin' && $requesterRole !== 'community_admin') {
            $conditions[] = "visibility != 'private'";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY order_id ASC, id ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Find a member by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM members WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $member = $stmt->fetch();
        return $member ?: null;
    }

    /**
     * Create a new member record.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO members (
                user_id, community_id, name, gender, birth_year, birth_date, death_year, death_date,
                is_deceased, relationship_label, profile_image, tags, gotra, native_place,
                occupation, bio, order_id, is_minor, visibility, created_at, updated_at
            ) VALUES (
                :user_id, :community_id, :name, :gender, :birth_year, :birth_date, :death_year, :death_date,
                :is_deceased, :relationship_label, :profile_image, :tags, :gotra, :native_place,
                :occupation, :bio, :order_id, :is_minor, :visibility, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':user_id'            => $data['user_id'] ?? null,
            ':community_id'       => $data['community_id'] ?? null,
            ':name'               => $data['name'],
            ':gender'             => $data['gender'] ?? null,
            ':birth_year'         => $data['birth_year'] ?? null,
            ':birth_date'         => $data['birth_date'] ?? null,
            ':death_year'         => $data['death_year'] ?? null,
            ':death_date'         => $data['death_date'] ?? null,
            ':is_deceased'        => $data['is_deceased'] ?? 0,
            ':relationship_label' => $data['relationship_label'] ?? null,
            ':profile_image'      => $data['profile_image'] ?? null,
            ':tags'               => isset($data['tags']) ? (is_array($data['tags']) ? json_encode($data['tags']) : $data['tags']) : null,
            ':gotra'              => $data['gotra'] ?? null,
            ':native_place'       => $data['native_place'] ?? null,
            ':occupation'         => $data['occupation'] ?? null,
            ':bio'                => $data['bio'] ?? null,
            ':order_id'           => $data['order_id'] ?? null,
            ':is_minor'           => $data['is_minor'] ?? 0,
            ':visibility'         => $data['visibility'] ?? 'family_only',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing member.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'name', 'gender', 'birth_year', 'birth_date', 'death_year', 'death_date',
            'is_deceased', 'relationship_label', 'profile_image', 'tags', 'gotra',
            'native_place', 'occupation', 'bio', 'order_id', 'is_minor', 'visibility',
        ];
        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'tags' && is_array($value)) {
                    $value = json_encode($value);
                }
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $fieldStr = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE members SET {$fieldStr} WHERE id = :id");
        return $stmt->execute($params);
    }

    /**
     * Update visibility for a member.
     */
    public function updateVisibility(int $id, string $visibility): bool
    {
        $stmt = $this->db->prepare("UPDATE members SET visibility = :vis, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':vis' => $visibility, ':id' => $id]);
    }

    /**
     * Delete a member.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM members WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Count total members (with optional community filter).
     */
    public function count(?int $communityId = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM members";
        $params = [];

        if ($communityId !== null) {
            $sql .= " WHERE community_id = :community_id";
            $params[':community_id'] = $communityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Search members by name, gotra, or native place.
     */
    public function search(string $query, ?int $communityId = null, int $limit = 20): array
    {
        $sql = "SELECT * FROM members WHERE (name LIKE :query OR gotra LIKE :query2 OR native_place LIKE :query3)";
        $params = [
            ':query'  => "%{$query}%",
            ':query2' => "%{$query}%",
            ':query3' => "%{$query}%",
        ];

        if ($communityId !== null) {
            $sql .= " AND community_id = :community_id";
            $params[':community_id'] = $communityId;
        }

        // Don't return private members in search
        $sql .= " AND visibility != 'private'";
        $sql .= " ORDER BY name ASC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
