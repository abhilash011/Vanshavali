<?php

declare(strict_types=1);

namespace Vanshavali\Repositories;

use Vanshavali\Config\Database;

/**
 * Repository for community events, scheduling, and RSVP tracking.
 */
class EventRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get events with optional filters.
     */
    public function findAll(
        ?int $communityId = null,
        ?string $status = 'published',
        bool $upcomingOnly = false,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = "
            SELECT e.*, c.name as community_name,
                   (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND status = 'attending') as attending_count,
                   (SELECT SUM(guest_count) FROM event_rsvps WHERE event_id = e.id AND status = 'attending') as total_guests
            FROM events e
            LEFT JOIN communities c ON c.id = e.community_id
            WHERE 1=1
        ";
        $params = [];

        if ($communityId !== null) {
            $sql .= " AND e.community_id = :cid";
            $params[':cid'] = $communityId;
        }

        if ($status !== null) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $status;
        }

        if ($upcomingOnly) {
            $sql .= " AND (e.start_date >= NOW() OR e.end_date >= NOW())";
        }

        $sql .= " ORDER BY e.start_date ASC LIMIT :limit OFFSET :offset";

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
     * Find single event by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, c.name as community_name,
                   (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND status = 'attending') as attending_count,
                   (SELECT SUM(guest_count) FROM event_rsvps WHERE event_id = e.id AND status = 'attending') as total_guests
            FROM events e
            LEFT JOIN communities c ON c.id = e.community_id
            WHERE e.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $event = $stmt->fetch();
        return $event ?: null;
    }

    /**
     * Create event.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO events (
                community_id, title, description, event_type, location,
                start_date, end_date, contact_person, contact_info,
                rsvp_enabled, visibility, status, published_by, created_at, updated_at
            ) VALUES (
                :cid, :title, :desc, :type, :location,
                :start, :end, :contact_p, :contact_i,
                :rsvp, :vis, :status, :pub_by, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':cid'       => $data['community_id'] ?? null,
            ':title'     => $data['title'],
            ':desc'      => $data['description'] ?? null,
            ':type'      => $data['event_type'] ?? 'other',
            ':location'  => $data['location'] ?? null,
            ':start'     => $data['start_date'] ?? null,
            ':end'       => $data['end_date'] ?? null,
            ':contact_p' => $data['contact_person'] ?? null,
            ':contact_i' => $data['contact_info'] ?? null,
            ':rsvp'      => !empty($data['rsvp_enabled']) ? 1 : 0,
            ':vis'       => $data['visibility'] ?? 'public',
            ':status'    => $data['status'] ?? 'published',
            ':pub_by'    => $data['published_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update event.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'title', 'description', 'event_type', 'location', 'start_date',
            'end_date', 'contact_person', 'contact_info', 'rsvp_enabled',
            'visibility', 'status'
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
        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Delete event.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * RSVP to an event.
     */
    public function rsvp(int $eventId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO event_rsvps (
                event_id, user_id, name, email, phone, guest_count, status, note, created_at, updated_at
            ) VALUES (
                :eid, :uid, :name, :email, :phone, :guests, :status, :note, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':eid'    => $eventId,
            ':uid'    => $data['user_id'] ?? null,
            ':name'   => $data['name'],
            ':email'  => $data['email'] ?? null,
            ':phone'  => $data['phone'] ?? null,
            ':guests' => max(1, (int)($data['guest_count'] ?? 1)),
            ':status' => $data['status'] ?? 'attending',
            ':note'   => $data['note'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get RSVPs for event.
     */
    public function getRSVPs(int $eventId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM event_rsvps WHERE event_id = :eid ORDER BY created_at DESC
        ");
        $stmt->execute([':eid' => $eventId]);
        return $stmt->fetchAll();
    }
}
