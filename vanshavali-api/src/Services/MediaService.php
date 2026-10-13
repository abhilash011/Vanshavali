<?php

declare(strict_types=1);

namespace Vanshavali\Services;

use Vanshavali\Config\Database;

/**
 * Media upload service with validation, EXIF stripping, and consent tracking.
 */
class MediaService
{
    private \PDO $db;
    private string $uploadDir;
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->uploadDir = dirname(__DIR__, 2) . '/uploads';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload and process a media file.
     *
     * @param array  $uploadedFile    $_FILES array entry
     * @param int    $ownerUserId     The uploading user
     * @param int|null $communityId   Community scope
     * @param bool   $consentConfirmed Whether photo consent was given
     * @return array{success: bool, media?: array, error?: string}
     */
    public function upload(array $uploadedFile, int $ownerUserId, ?int $communityId = null, bool $consentConfirmed = false): array
    {
        // Validate file was uploaded
        if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }

        // Validate file size
        if ($uploadedFile['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
        }

        // Validate MIME type via finfo (don't trust the browser)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: JPEG, PNG, WebP, GIF.'];
        }

        // Validate extension
        $originalName = $uploadedFile['name'] ?? 'unknown';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => 'Invalid file extension.'];
        }

        // Strip EXIF data for privacy (JPEG only)
        if (in_array($mimeType, ['image/jpeg', 'image/jpg'])) {
            $this->stripExif($uploadedFile['tmp_name']);
        }

        // Generate safe filename
        $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $yearMonth = date('Y/m');
        $targetDir = $this->uploadDir . '/' . $yearMonth;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $safeFilename;
        $relativePath = $yearMonth . '/' . $safeFilename;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file.'];
        }

        // Store in database
        $stmt = $this->db->prepare("
            INSERT INTO media_assets (
                owner_user_id, community_id, file_path, file_type, file_size,
                original_filename, consent_confirmed, visibility, moderation_status, created_at
            ) VALUES (
                :owner, :community_id, :path, :type, :size,
                :original, :consent, 'private', 'approved', NOW()
            )
        ");

        $stmt->execute([
            ':owner'        => $ownerUserId,
            ':community_id' => $communityId,
            ':path'         => $relativePath,
            ':type'         => $mimeType,
            ':size'         => $uploadedFile['size'],
            ':original'     => $originalName,
            ':consent'      => $consentConfirmed ? 1 : 0,
        ]);

        $mediaId = (int) $this->db->lastInsertId();

        return [
            'success' => true,
            'media'   => [
                'id'        => $mediaId,
                'file_path' => $relativePath,
                'file_type' => $mimeType,
                'file_size' => $uploadedFile['size'],
                'url'       => $this->getPublicUrl($relativePath),
            ],
        ];
    }

    /**
     * Get a media asset by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM media_assets WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $media = $stmt->fetch();
        if ($media) {
            $media['url'] = $this->getPublicUrl($media['file_path']);
        }
        return $media ?: null;
    }

    /**
     * Delete a media asset.
     */
    public function delete(int $id): bool
    {
        $media = $this->findById($id);
        if ($media) {
            $fullPath = $this->uploadDir . '/' . $media['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $stmt = $this->db->prepare("DELETE FROM media_assets WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Strip EXIF data from a JPEG image for privacy.
     */
    private function stripExif(string $filePath): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return; // GD not available
        }

        $image = @imagecreatefromjpeg($filePath);
        if ($image) {
            imagejpeg($image, $filePath, 90);
            imagedestroy($image);
        }
    }

    /**
     * Build a public URL for a media file.
     */
    private function getPublicUrl(string $relativePath): string
    {
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost/Vanshavali/vanshavali-api/public';
        // uploads dir is outside public, so serve via a route or direct path
        return rtrim($appUrl, '/') . '/../uploads/' . $relativePath;
    }
}
