<?php

declare(strict_types=1);

namespace Vanshavali\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private string $secret;
    private int $accessTtl;
    private int $refreshTtl;

    public function __construct()
    {
        $this->secret     = $_ENV['JWT_SECRET'] ?? 'CHANGE_ME';
        $this->accessTtl  = (int)($_ENV['JWT_ACCESS_TTL'] ?? 900);
        $this->refreshTtl = (int)($_ENV['JWT_REFRESH_TTL'] ?? 604800);
    }

    /**
     * Generate an access token (short-lived).
     */
    public function generateAccessToken(int $userId, string $email, string $role, ?int $communityId = null): string
    {
        $now = time();
        $payload = [
            'iss'          => 'vanshavali-api',
            'sub'          => $userId,
            'email'        => $email,
            'role'         => $role,
            'community_id' => $communityId,
            'type'         => 'access',
            'iat'          => $now,
            'exp'          => $now + $this->accessTtl,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Generate a refresh token (longer-lived).
     */
    public function generateRefreshToken(int $userId): string
    {
        $now = time();
        $payload = [
            'iss'  => 'vanshavali-api',
            'sub'  => $userId,
            'type' => 'refresh',
            'jti'  => bin2hex(random_bytes(16)), // unique token ID for revocation
            'iat'  => $now,
            'exp'  => $now + $this->refreshTtl,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Decode and validate a token. Returns the claims or throws.
     */
    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    /**
     * Validate token and return claims, or null on failure.
     */
    public function validate(string $token): ?object
    {
        try {
            return $this->decode($token);
        } catch (\Exception $e) {
            return null;
        }
    }
}
