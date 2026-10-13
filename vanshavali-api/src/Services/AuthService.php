<?php

declare(strict_types=1);

namespace Vanshavali\Services;

use Vanshavali\Helpers\JwtHelper;
use Vanshavali\Repositories\UserRepository;
use Vanshavali\Repositories\TokenRepository;

class AuthService
{
    private UserRepository $users;
    private TokenRepository $tokens;
    private JwtHelper $jwt;
    private AuditService $audit;
    private EmailService $email;

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION   = 900; // 15 minutes
    private const VERIFY_TOKEN_TTL   = 86400; // 24 hours
    private const RESET_TOKEN_TTL    = 3600;  // 1 hour

    public function __construct()
    {
        $this->users  = new UserRepository();
        $this->tokens = new TokenRepository();
        $this->jwt    = new JwtHelper();
        $this->audit  = new AuditService();
        $this->email  = new EmailService();
    }

    /**
     * Register a new user and send verification email.
     *
     * @return array{success: bool, user_id?: int, error?: string}
     */
    public function register(string $name, string $email, string $password, ?int $communityId = null): array
    {
        // Check for duplicate account
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            return ['success' => false, 'error' => 'An account with this email already exists'];
        }

        // Hash password with Argon2id if available, otherwise bcrypt
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hashedPassword = password_hash($password, $algo);

        $userId = $this->users->create([
            'name'         => $name,
            'email'        => $email,
            'password'     => $hashedPassword,
            'role'         => 'member',
            'community_id' => $communityId,
            'status'       => 'pending', // Pending until email verified
        ]);

        // Generate and send verification email
        $this->sendVerificationEmail($userId, $email, $name);

        $this->audit->log($userId, 'register', 'user', $userId);

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Send (or re-send) email verification.
     */
    public function sendVerificationEmail(int $userId, string $email, string $name): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $this->tokens->storeVerificationToken($userId, $tokenHash, time() + self::VERIFY_TOKEN_TTL);
        $this->email->sendVerificationEmail($email, $name, $rawToken);
    }

    /**
     * Verify email address using token.
     *
     * @return array{success: bool, error?: string}
     */
    public function verifyEmail(string $rawToken): array
    {
        $tokenHash = hash('sha256', $rawToken);
        $token = $this->tokens->findValidVerificationToken($tokenHash);

        if (!$token) {
            return ['success' => false, 'error' => 'Invalid or expired verification token'];
        }

        $this->users->markEmailVerified($token['user_id']);
        $this->tokens->markVerificationUsed($tokenHash);

        $this->audit->log($token['user_id'], 'email.verified', 'user', $token['user_id']);

        return ['success' => true];
    }

    /**
     * Re-send verification email.
     *
     * @return array{success: bool, error?: string}
     */
    public function resendVerification(string $email): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            // Don't reveal whether the email exists
            return ['success' => true];
        }

        if ($user['email_verified_at']) {
            return ['success' => false, 'error' => 'Email is already verified'];
        }

        $this->sendVerificationEmail($user['id'], $user['email'], $user['name']);

        return ['success' => true];
    }

    /**
     * Authenticate a user and return tokens.
     *
     * @return array{success: bool, access_token?: string, refresh_token?: string, user?: array, error?: string}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (!$user) {
            // Simulate password verification to prevent timing attacks
            password_verify($password, '$2y$10$' . str_repeat('a', 53));
            usleep(random_int(200000, 600000));
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Check account lockout
        if ($user['login_attempts'] >= self::MAX_LOGIN_ATTEMPTS) {
            $lastAttempt = strtotime($user['last_attempt'] ?? 'now');
            $lockoutExpires = $lastAttempt + self::LOCKOUT_DURATION;

            if (time() < $lockoutExpires) {
                $remaining = (int) ceil(($lockoutExpires - time()) / 60);
                $this->audit->log($user['id'], 'login.locked', 'user', $user['id']);
                return ['success' => false, 'error' => "Account locked. Try again in {$remaining} minutes."];
            }
        }

        // Check account status — allow pending (unverified) users to login but flag it
        if (($user['status'] ?? 'active') === 'suspended' || ($user['status'] ?? 'active') === 'deleted') {
            return ['success' => false, 'error' => 'Account is not active. Please contact an administrator.'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->users->incrementLoginAttempts($user['id']);
            usleep(random_int(200000, 600000));
            $this->audit->log($user['id'], 'login.failed', 'user', $user['id']);
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Successful login
        $this->users->resetLoginAttempts($user['id']);

        $accessToken = $this->jwt->generateAccessToken(
            $user['id'],
            $user['email'],
            $user['role'],
            $user['community_id'] ?? null
        );

        $refreshToken = $this->jwt->generateRefreshToken($user['id']);

        // Store refresh token hash
        $refreshTtl = (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800);
        $this->users->storeRefreshToken(
            $user['id'],
            hash('sha256', $refreshToken),
            time() + $refreshTtl
        );

        $this->audit->log($user['id'], 'login.success', 'user', $user['id']);

        return [
            'success'       => true,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'user'          => [
                'id'                => $user['id'],
                'name'              => $user['name'],
                'email'             => $user['email'],
                'role'              => $user['role'],
                'community_id'      => $user['community_id'] ?? null,
                'email_verified'    => !empty($user['email_verified_at']),
                'status'            => $user['status'],
            ],
        ];
    }

    /**
     * Request a password reset.
     * Always returns success to prevent email enumeration.
     */
    public function requestPasswordReset(string $email): array
    {
        $user = $this->users->findByEmail($email);

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            $this->tokens->storeResetToken($user['id'], $tokenHash, time() + self::RESET_TOKEN_TTL);
            $this->email->sendPasswordResetEmail($user['email'], $user['name'], $rawToken);
            $this->audit->log($user['id'], 'password.reset_requested', 'user', $user['id']);
        }

        // Always return success to prevent email enumeration
        return ['success' => true];
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(string $rawToken, string $newPassword): array
    {
        $tokenHash = hash('sha256', $rawToken);
        $token = $this->tokens->findValidResetToken($tokenHash);

        if (!$token) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }

        // Hash the new password
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hashedPassword = password_hash($newPassword, $algo);

        // Update password and invalidate all existing sessions
        $this->users->updatePassword($token['user_id'], $hashedPassword);
        $this->users->revokeAllRefreshTokens($token['user_id']);
        $this->tokens->markResetUsed($tokenHash);

        $this->audit->log($token['user_id'], 'password.reset_completed', 'user', $token['user_id']);

        return ['success' => true];
    }

    /**
     * Change password for logged-in user.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        if (!password_verify($currentPassword, $user['password'])) {
            $this->audit->log($userId, 'password.change_failed', 'user', $userId);
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }

        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hashedPassword = password_hash($newPassword, $algo);
        $this->users->updatePassword($userId, $hashedPassword);

        // Revoke all refresh tokens to force re-login on other devices
        $this->users->revokeAllRefreshTokens($userId);

        $this->audit->log($userId, 'password.changed', 'user', $userId);

        return ['success' => true];
    }

    /**
     * Refresh an access token using a valid refresh token.
     */
    public function refresh(string $refreshToken): array
    {
        $claims = $this->jwt->validate($refreshToken);

        if ($claims === null || ($claims->type ?? '') !== 'refresh') {
            return ['success' => false, 'error' => 'Invalid refresh token'];
        }

        $tokenHash = hash('sha256', $refreshToken);
        $storedToken = $this->users->findValidRefreshToken($claims->sub, $tokenHash);

        if (!$storedToken) {
            return ['success' => false, 'error' => 'Refresh token has been revoked or expired'];
        }

        $user = $this->users->findById($claims->sub);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        // Revoke old refresh token and issue new pair (token rotation)
        $this->users->revokeRefreshToken($tokenHash);

        $newAccessToken = $this->jwt->generateAccessToken(
            $user['id'],
            $user['email'],
            $user['role'],
            $user['community_id'] ?? null
        );

        $newRefreshToken = $this->jwt->generateRefreshToken($user['id']);
        $refreshTtl = (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800);
        $this->users->storeRefreshToken(
            $user['id'],
            hash('sha256', $newRefreshToken),
            time() + $refreshTtl
        );

        return [
            'success'       => true,
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
        ];
    }

    /**
     * Logout — revoke the refresh token.
     */
    public function logout(string $refreshToken): void
    {
        $claims = $this->jwt->validate($refreshToken);
        if ($claims) {
            $this->users->revokeRefreshToken(hash('sha256', $refreshToken));
            $this->audit->log($claims->sub, 'logout', 'user', $claims->sub);
        }
    }
}
