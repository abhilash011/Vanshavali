<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

class ProfileRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find profile by user ID.
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_profiles WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        $profile = $stmt->fetch();
        return $profile ?: null;
    }

    /**
     * Create a profile record.
     */
    public function create(int $userId, array $data = []): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_profiles (user_id, phone, avatar_url, bio, date_of_birth, address_line1, address_line2, city, state, country, postal_code, language_pref, created_at, updated_at)
            VALUES (:user_id, :phone, :avatar_url, :bio, :dob, :addr1, :addr2, :city, :state, :country, :postal, :lang, NOW(), NOW())
        ");

        $stmt->execute([
            ':user_id'    => $userId,
            ':phone'      => $data['phone'] ?? null,
            ':avatar_url' => $data['avatar_url'] ?? null,
            ':bio'        => $data['bio'] ?? null,
            ':dob'        => $data['date_of_birth'] ?? null,
            ':addr1'      => $data['address_line1'] ?? null,
            ':addr2'      => $data['address_line2'] ?? null,
            ':city'       => $data['city'] ?? null,
            ':state'      => $data['state'] ?? null,
            ':country'    => $data['country'] ?? null,
            ':postal'     => $data['postal_code'] ?? null,
            ':lang'       => $data['language_pref'] ?? 'en',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a profile.
     */
    public function update(int $userId, array $data): bool
    {
        $allowedFields = [
            'phone', 'avatar_url', 'bio', 'date_of_birth',
            'address_line1', 'address_line2', 'city', 'state',
            'country', 'postal_code', 'language_pref',
        ];

        $fields = [];
        $params = [':user_id' => $userId];

        foreach ($allowedFields as $field) {
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

        $stmt = $this->db->prepare("UPDATE user_profiles SET {$fieldStr} WHERE user_id = :user_id");
        return $stmt->execute($params);
    }

    /**
     * Upsert (create or update) a profile.
     */
    public function upsert(int $userId, array $data): void
    {
        $existing = $this->findByUserId($userId);
        if ($existing) {
            $this->update($userId, $data);
        } else {
            $this->create($userId, $data);
        }
    }
}
