<?php

declare(strict_types=1);

namespace Vanshavali\Services;

use Vanshavali\Config\Database;

class AuditService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Log an auditable action.
     *
     * @param int|null    $actorUserId  The user performing the action (null for system actions)
     * @param string      $action       e.g. 'login', 'register', 'member.create', 'member.update'
     * @param string      $entityType   e.g. 'user', 'member', 'relationship', 'event'
     * @param int|null    $entityId     The ID of the affected entity
     * @param array|null  $beforeState  JSON-serializable previous state
     * @param array|null  $afterState   JSON-serializable new state
     */
    public function log(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $beforeState = null,
        ?array $afterState = null
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, before_state, after_state, ip_address, user_agent, created_at)
            VALUES (:actor, :action, :entity_type, :entity_id, :before_state, :after_state, :ip, :ua, NOW())
        ");

        $stmt->execute([
            ':actor'        => $actorUserId,
            ':action'       => $action,
            ':entity_type'  => $entityType,
            ':entity_id'    => $entityId,
            ':before_state' => $beforeState ? json_encode($beforeState) : null,
            ':after_state'  => $afterState ? json_encode($afterState) : null,
            ':ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua'           => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
