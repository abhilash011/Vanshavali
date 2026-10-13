<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

/**
 * Repository for email verification and password reset tokens.
 */
class TokenRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ===== Email Verification Tokens =====

    /**
     * Store an email verification token.
     */
    public function storeVerificationToken(int $userId, string $tokenHash, int $expiresAt): void
    {
        // Invalidate any previous unused tokens for this user
        $this->invalidateVerificationTokens($userId);

        $stmt = $this->db->prepare("
            INSERT INTO email_verification_tokens (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, FROM_UNIXTIME(:expires_at), NOW())
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * Find a valid (unused, non-expired) verification token.
     */
    public function findValidVerificationToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM email_verification_tokens
            WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $token = $stmt->fetch();
        return $token ?: null;
    }

    /**
     * Mark a verification token as used.
     */
    public function markVerificationUsed(string $tokenHash): void
    {
        $stmt = $this->db->prepare("
            UPDATE email_verification_tokens SET used_at = NOW() WHERE token_hash = :hash
        ");
        $stmt->execute([':hash' => $tokenHash]);
    }

    /**
     * Invalidate all unused verification tokens for a user.
     */
    public function invalidateVerificationTokens(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = :id AND used_at IS NULL
        ");
        $stmt->execute([':id' => $userId]);
    }

    // ===== Password Reset Tokens =====

    /**
     * Store a password reset token.
     */
    public function storeResetToken(int $userId, string $tokenHash, int $expiresAt): void
    {
        // Invalidate any previous unused tokens for this user
        $this->invalidateResetTokens($userId);

        $stmt = $this->db->prepare("
            INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, FROM_UNIXTIME(:expires_at), NOW())
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * Find a valid (unused, non-expired) reset token.
     */
    public function findValidResetToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_reset_tokens
            WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $token = $stmt->fetch();
        return $token ?: null;
    }

    /**
     * Mark a reset token as used.
     */
    public function markResetUsed(string $tokenHash): void
    {
        $stmt = $this->db->prepare("
            UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = :hash
        ");
        $stmt->execute([':hash' => $tokenHash]);
    }

    /**
     * Invalidate all unused reset tokens for a user.
     */
    public function invalidateResetTokens(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :id AND used_at IS NULL
        ");
        $stmt->execute([':id' => $userId]);
    }
}
