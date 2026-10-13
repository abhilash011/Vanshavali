<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

/**
 * Repository for community announcements, news broadcasts, obituaries, and festival updates.
 */
class AnnouncementRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get announcements with optional category and status filters.
     */
    public function findAll(
        ?int $communityId = null,
        ?string $category = null,
        string $status = 'published',
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "
            SELECT a.*, u.name as author_name, c.name as community_name, ch.name as chapter_name
            FROM announcements a
            LEFT JOIN users u ON u.id = a.author_id
            LEFT JOIN communities c ON c.id = a.community_id
            LEFT JOIN chapters ch ON ch.id = a.chapter_id
            WHERE 1=1
        ";
        $params = [];

        if ($communityId !== null) {
            $sql .= " AND a.community_id = :cid";
            $params[':cid'] = $communityId;
        }

        if ($category !== null && $category !== 'all') {
            $sql .= " AND a.category = :category";
            $params[':category'] = $category;
        }

        if ($status !== null) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        // Pinned first, then by published_at DESC
        $sql .= " ORDER BY a.is_pinned DESC, a.published_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Find single announcement by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as author_name, c.name as community_name, ch.name as chapter_name
            FROM announcements a
            LEFT JOIN users u ON u.id = a.author_id
            LEFT JOIN communities c ON c.id = a.community_id
            LEFT JOIN chapters ch ON ch.id = a.chapter_id
            WHERE a.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Create announcement.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO announcements (
                community_id, chapter_id, title, slug, content, category,
                is_pinned, image_url, author_id, status, published_at, created_at, updated_at
            ) VALUES (
                :cid, :chid, :title, :slug, :content, :category,
                :pinned, :img, :author, :status, NOW(), NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':cid'      => $data['community_id'],
            ':chid'     => $data['chapter_id'] ?? null,
            ':title'    => $data['title'],
            ':slug'     => $data['slug'] ?? null,
            ':content'  => $data['content'],
            ':category' => $data['category'] ?? 'general',
            ':pinned'   => !empty($data['is_pinned']) ? 1 : 0,
            ':img'      => $data['image_url'] ?? null,
            ':author'   => $data['author_id'] ?? null,
            ':status'   => $data['status'] ?? 'published',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update announcement.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'chapter_id', 'title', 'slug', 'content', 'category',
            'is_pinned', 'image_url', 'status'
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
        $sql = "UPDATE announcements SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Delete announcement.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM announcements WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
