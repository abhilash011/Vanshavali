<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

/**
 * Repository for media albums and photo galleries.
 */
class GalleryRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get albums for a community.
     */
    public function findAllAlbums(?int $communityId = null, string $visibility = 'public'): array
    {
        $sql = "
            SELECT a.*, 
                   (SELECT COUNT(*) FROM media_assets WHERE album_id = a.id) as photo_count
            FROM media_albums a
            WHERE 1=1
        ";
        $params = [];

        if ($communityId !== null) {
            $sql .= " AND a.community_id = :cid";
            $params[':cid'] = $communityId;
        }

        if ($visibility !== 'all') {
            $sql .= " AND (a.visibility = :vis OR a.visibility = 'public')";
            $params[':vis'] = $visibility;
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find single album by ID.
     */
    public function findAlbumById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM media_assets WHERE album_id = a.id) as photo_count
            FROM media_albums a
            WHERE a.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $album = $stmt->fetch();
        return $album ?: null;
    }

    /**
     * Create album.
     */
    public function createAlbum(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO media_albums (
                community_id, title, description, cover_image_url, visibility, created_by, created_at, updated_at
            ) VALUES (
                :cid, :title, :desc, :cover, :vis, :created_by, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':cid'        => $data['community_id'],
            ':title'      => $data['title'],
            ':desc'       => $data['description'] ?? null,
            ':cover'      => $data['cover_image_url'] ?? null,
            ':vis'        => $data['visibility'] ?? 'public',
            ':created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update album.
     */
    public function updateAlbum(int $id, array $data): bool
    {
        $allowed = ['title', 'description', 'cover_image_url', 'visibility'];
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
        $sql = "UPDATE media_albums SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Delete album.
     */
    public function deleteAlbum(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM media_albums WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get photos in album.
     */
    public function getAlbumPhotos(int $albumId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.name as uploader_name
            FROM media_assets m
            LEFT JOIN users u ON u.id = m.owner_user_id
            WHERE m.album_id = :aid
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([':aid' => $albumId]);
        return $stmt->fetchAll();
    }
}
