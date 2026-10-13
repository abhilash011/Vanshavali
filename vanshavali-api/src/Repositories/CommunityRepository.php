<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

/**
 * Repository for communities and regional chapters.
 */
class CommunityRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all active communities.
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM members WHERE community_id = c.id) as total_members,
                   (SELECT COUNT(*) FROM chapters WHERE community_id = c.id) as total_chapters
            FROM communities c
            WHERE c.status = 'active'
            ORDER BY c.name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $communities = $stmt->fetchAll();

        foreach ($communities as &$comm) {
            if ($comm['language_prefs']) {
                $comm['language_prefs'] = json_decode($comm['language_prefs'], true);
            }
            if ($comm['policies']) {
                $comm['policies'] = json_decode($comm['policies'], true);
            }
        }

        return $communities;
    }

    /**
     * Find community by ID or slug.
     */
    public function findByIdOrSlug(string|int $idOrSlug): ?array
    {
        if (is_numeric($idOrSlug)) {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM members WHERE community_id = c.id) as total_members,
                       (SELECT COUNT(*) FROM chapters WHERE community_id = c.id) as total_chapters
                FROM communities c 
                WHERE c.id = :val LIMIT 1
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM members WHERE community_id = c.id) as total_members,
                       (SELECT COUNT(*) FROM chapters WHERE community_id = c.id) as total_chapters
                FROM communities c 
                WHERE c.slug = :val LIMIT 1
            ");
        }

        $stmt->execute([':val' => $idOrSlug]);
        $community = $stmt->fetch();

        if ($community) {
            if ($community['language_prefs']) {
                $community['language_prefs'] = json_decode($community['language_prefs'], true);
            }
            if ($community['policies']) {
                $community['policies'] = json_decode($community['policies'], true);
            }
            return $community;
        }

        return null;
    }

    /**
     * Create community.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO communities (
                name, slug, region, language_prefs, contact_email, contact_phone,
                description, logo_url, policies, status, created_at, updated_at
            ) VALUES (
                :name, :slug, :region, :languages, :email, :phone,
                :desc, :logo, :policies, :status, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':name'      => $data['name'],
            ':slug'      => $data['slug'],
            ':region'    => $data['region'] ?? null,
            ':languages' => isset($data['language_prefs']) ? json_encode($data['language_prefs']) : null,
            ':email'     => $data['contact_email'] ?? null,
            ':phone'     => $data['contact_phone'] ?? null,
            ':desc'      => $data['description'] ?? null,
            ':logo'      => $data['logo_url'] ?? null,
            ':policies'  => isset($data['policies']) ? json_encode($data['policies']) : null,
            ':status'    => $data['status'] ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update community.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'name', 'slug', 'region', 'language_prefs', 'contact_email',
            'contact_phone', 'description', 'logo_url', 'policies', 'status'
        ];
        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $val = $data[$f];
                if (in_array($f, ['language_prefs', 'policies']) && is_array($val)) {
                    $val = json_encode($val);
                }
                $fields[] = "{$f} = :{$f}";
                $params[":{$f}"] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE communities SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Chapters for community.
     */
    public function getChapters(int $communityId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM chapters
            WHERE community_id = :cid AND status = 'active'
            ORDER BY member_count DESC, city ASC
        ");
        $stmt->execute([':cid' => $communityId]);
        return $stmt->fetchAll();
    }

    /**
     * Chapter by ID.
     */
    public function getChapterById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM chapters WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $c = $stmt->fetch();
        return $c ?: null;
    }

    /**
     * Create Chapter.
     */
    public function createChapter(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO chapters (
                community_id, name, slug, city, state, country,
                contact_person, contact_phone, contact_email, address,
                member_count, status, created_at, updated_at
            ) VALUES (
                :cid, :name, :slug, :city, :state, :country,
                :person, :phone, :email, :address,
                :count, :status, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':cid'     => $data['community_id'],
            ':name'    => $data['name'],
            ':slug'    => $data['slug'],
            ':city'    => $data['city'],
            ':state'   => $data['state'] ?? null,
            ':country' => $data['country'] ?? 'India',
            ':person'  => $data['contact_person'] ?? null,
            ':phone'   => $data['contact_phone'] ?? null,
            ':email'   => $data['contact_email'] ?? null,
            ':address' => $data['address'] ?? null,
            ':count'   => $data['member_count'] ?? 0,
            ':status'  => $data['status'] ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update Chapter.
     */
    public function updateChapter(int $id, array $data): bool
    {
        $allowed = [
            'name', 'slug', 'city', 'state', 'country', 'contact_person',
            'contact_phone', 'contact_email', 'address', 'member_count', 'status'
        ];
        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE chapters SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Delete Chapter.
     */
    public function deleteChapter(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM chapters WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
