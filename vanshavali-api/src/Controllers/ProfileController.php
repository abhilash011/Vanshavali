<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\ProfileRepository;
use Vanshavali\Repositories\UserRepository;
use Vanshavali\Services\AuditService;

/**
 * Profile controller for self-service user profile management.
 */
class ProfileController
{
    private UserRepository $users;
    private ProfileRepository $profiles;
    private AuditService $audit;

    public function __construct()
    {
        $this->users    = new UserRepository();
        $this->profiles = new ProfileRepository();
        $this->audit    = new AuditService();
    }

    /**
     * GET /api/v1/profile — Get current user's profile.
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = (int) $request->getAttribute('user_id');

        $user = $this->users->getSafeUser($userId);
        if (!$user) {
            return Response::json($response, Response::error('NOT_FOUND', 'User not found', 404));
        }

        $profile = $this->profiles->findByUserId($userId);

        return Response::json($response, Response::success([
            'user'    => $user,
            'profile' => $profile,
        ]));
    }

    /**
     * PUT /api/v1/profile — Update current user's profile.
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = (int) $request->getAttribute('user_id');
        $data = (array) $request->getParsedBody();

        // Validate user-updatable fields
        $validator = new Validator();
        if (isset($data['name'])) {
            $validator->minLength($data, 'name', 2)->maxLength($data, 'name', 100);
        }
        if (isset($data['email'])) {
            $validator->email($data, 'email');
        }
        if (isset($data['phone'])) {
            $validator->maxLength($data, 'phone', 20);
        }

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Please correct the following errors',
                422,
                $validator->errors()
            ));
        }

        $beforeUser = $this->users->getSafeUser($userId);

        // Separate user-table fields from profile-table fields
        $userFields = [];
        foreach (['name', 'phone'] as $field) {
            if (array_key_exists($field, $data)) {
                $userFields[$field] = $data[$field];
            }
        }

        // Users cannot change their own role, status, or community_id
        if (!empty($userFields)) {
            $this->users->update($userId, $userFields);
        }

        // Profile fields
        $profileFields = [];
        $profileAllowed = [
            'bio', 'date_of_birth', 'address_line1', 'address_line2',
            'city', 'state', 'country', 'postal_code', 'language_pref',
        ];

        foreach ($profileAllowed as $field) {
            if (array_key_exists($field, $data)) {
                $profileFields[$field] = $data[$field];
            }
        }

        if (!empty($profileFields)) {
            $this->profiles->upsert($userId, $profileFields);
        }

        $afterUser = $this->users->getSafeUser($userId);
        $profile = $this->profiles->findByUserId($userId);

        $this->audit->log($userId, 'profile.updated', 'user', $userId, $beforeUser, $afterUser);

        return Response::json($response, Response::success([
            'user'    => $afterUser,
            'profile' => $profile,
            'message' => 'Profile updated successfully',
        ]));
    }

    /**
     * GET /api/v1/users/{id} — View another user's public profile (admin or same community).
     */
    public function viewUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requesterId = (int) $request->getAttribute('user_id');
        $requesterRole = $request->getAttribute('user_role', 'member');
        $requesterCommunity = $request->getAttribute('community_id');

        $targetId = (int) $args['id'];
        $user = $this->users->getSafeUser($targetId);

        if (!$user) {
            return Response::json($response, Response::error('NOT_FOUND', 'User not found', 404));
        }

        // Enforce tenant scoping: same community or platform admin
        if ($requesterRole !== 'platform_admin' && $user['community_id'] != $requesterCommunity) {
            return Response::json($response, Response::error(
                'FORBIDDEN',
                'You cannot view users from other communities',
                403
            ));
        }

        // Don't expose sensitive info to non-admins
        if ($requesterRole !== 'platform_admin' && $requesterRole !== 'community_admin') {
            unset($user['email'], $user['last_login']);
        }

        $profile = $this->profiles->findByUserId($targetId);

        return Response::json($response, Response::success([
            'user'    => $user,
            'profile' => $profile,
        ]));
    }
}
