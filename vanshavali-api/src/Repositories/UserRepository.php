<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

class UserRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Get user's safe public data (excludes password, login_attempts, etc.).
     */
    public function getSafeUser(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, avatar_url, role, community_id, status,
                   email_verified_at, last_login, created_at, updated_at
            FROM users WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create a new user account.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role, community_id, status, created_at, updated_at)
            VALUES (:name, :email, :password, :role, :community_id, :status, NOW(), NOW())
        ");

        $stmt->execute([
            ':name'         => $data['name'],
            ':email'        => $data['email'],
            ':password'     => $data['password'],
            ':role'         => $data['role'] ?? 'member',
            ':community_id' => $data['community_id'] ?? null,
            ':status'       => $data['status'] ?? 'pending',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update login attempts for brute-force protection.
     */
    public function incrementLoginAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users SET login_attempts = login_attempts + 1, last_attempt = NOW() WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
    }

    /**
     * Reset login attempts after successful login.
     */
    public function resetLoginAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users SET login_attempts = 0, last_attempt = NULL, last_login = NOW() WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
    }

    /**
     * Update a user's profile.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'email', 'phone', 'avatar_url', 'role', 'status', 'community_id'] as $field) {
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

        $stmt = $this->db->prepare("UPDATE users SET {$fieldStr} WHERE id = :id");
        return $stmt->execute($params);
    }

    /**
     * Mark a user's email as verified.
     */
    public function markEmailVerified(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users SET email_verified_at = NOW(), status = 'active', updated_at = NOW() WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
    }

    /**
     * Update a user's password.
     */
    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $stmt = $this->db->prepare("
            UPDATE users SET password = :password, login_attempts = 0, last_attempt = NULL, updated_at = NOW() WHERE id = :id
        ");
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id'       => $userId,
        ]);
    }

    /**
     * Store a refresh token.
     */
    public function storeRefreshToken(int $userId, string $tokenHash, int $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, FROM_UNIXTIME(:expires_at), NOW())
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * Verify a refresh token exists and is valid.
     */
    public function findValidRefreshToken(int $userId, string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM refresh_tokens
            WHERE user_id = :user_id AND token_hash = :token_hash AND revoked = 0 AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':token_hash' => $tokenHash,
        ]);
        $token = $stmt->fetch();
        return $token ?: null;
    }

    /**
     * Revoke a specific refresh token.
     */
    public function revokeRefreshToken(string $tokenHash): void
    {
        $stmt = $this->db->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = :hash");
        $stmt->execute([':hash' => $tokenHash]);
    }

    /**
     * Revoke all refresh tokens for a user (logout-everywhere).
     */
    public function revokeAllRefreshTokens(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE user_id = :id");
        $stmt->execute([':id' => $userId]);
    }

    /**
     * Count total users, optionally scoped by community.
     */
    public function count(?int $communityId = null): int
    {
        if ($communityId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE community_id = :cid");
            $stmt->execute([':cid' => $communityId]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        }
        return (int) $stmt->fetchColumn();
    }
}
