<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\EventRepository;
use Vanshavali\Services\AuditService;

/**
 * Controller for community events and RSVP management.
 */
class EventController
{
    private EventRepository $events;
    private AuditService $audit;

    public function __construct()
    {
        $this->events = new EventRepository();
        $this->audit  = new AuditService();
    }

    /**
     * GET /api/v1/events (public)
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $communityId = isset($params['community_id']) ? (int)$params['community_id'] : null;
        $status = $params['status'] ?? 'published';
        $upcomingOnly = !empty($params['upcoming']) && $params['upcoming'] !== '0';
        $limit = min((int)($params['limit'] ?? 50), 100);
        $offset = max((int)($params['offset'] ?? 0), 0);

        $events = $this->events->findAll($communityId, $status, $upcomingOnly, $limit, $offset);
        return Response::json($response, Response::success($events, ['count' => count($events)]));
    }

    /**
     * GET /api/v1/events/{id} (public)
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $event = $this->events->findById($id);

        if (!$event) {
            return Response::json($response, Response::error('NOT_FOUND', 'Event not found', 404));
        }

        return Response::json($response, Response::success($event));
    }

    /**
     * POST /api/v1/events (Moderator / Admin)
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        $communityId = $request->getAttribute('community_id');

        $validator = new Validator();
        $validator->required($data, ['title', 'start_date', 'location']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Validation failed', 422, $validator->errors()));
        }

        $data['community_id'] = $communityId ?? ($data['community_id'] ?? 1);
        $data['published_by'] = $userId;

        $id = $this->events->create($data);
        $created = $this->events->findById($id);
        $this->audit->log($userId, 'event.create', 'event', $id, null, $created);

        return Response::json($response, Response::success($created, [], 201));
    }

    /**
     * PUT /api/v1/events/{id} (Moderator / Admin)
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->events->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Event not found', 404));
        }

        $this->events->update($id, $data);
        $updated = $this->events->findById($id);
        $this->audit->log($userId, 'event.update', 'event', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * DELETE /api/v1/events/{id} (Moderator / Admin)
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');

        $existing = $this->events->findById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Event not found', 404));
        }

        $this->events->delete($id);
        $this->audit->log($userId, 'event.delete', 'event', $id, $existing, null);

        return Response::json($response, Response::success(['message' => 'Event deleted successfully']));
    }

    /**
     * POST /api/v1/events/{id}/rsvp (Public or Authenticated)
     */
    public function rsvp(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $eventId = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id'); // may be null if public

        $event = $this->events->findById($eventId);
        if (!$event) {
            return Response::json($response, Response::error('NOT_FOUND', 'Event not found', 404));
        }

        $validator = new Validator();
        $validator->required($data, ['name']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Name is required to RSVP', 422, $validator->errors()));
        }

        $data['user_id'] = $userId;
        $rsvpId = $this->events->rsvp($eventId, $data);

        return Response::json($response, Response::success([
            'rsvp_id'  => $rsvpId,
            'event_id' => $eventId,
            'status'   => $data['status'] ?? 'attending',
        ], ['message' => 'RSVP recorded successfully! We look forward to seeing you.']));
    }

    /**
     * GET /api/v1/events/{id}/rsvps (Moderator / Admin)
     */
    public function getRsvps(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $eventId = (int) $args['id'];
        $rsvps = $this->events->getRSVPs($eventId);
        return Response::json($response, Response::success($rsvps));
    }
}
