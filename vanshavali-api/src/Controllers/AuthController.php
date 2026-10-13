<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator
            ->required($data, ['name', 'email', 'password'])
            ->email($data, 'email')
            ->minLength($data, 'password', 12)
            ->maxLength($data, 'password', 72)
            ->minLength($data, 'name', 2)
            ->maxLength($data, 'name', 100);

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Please correct the following errors',
                422,
                $validator->errors()
            ));
        }

        $result = $this->authService->register(
            trim($data['name']),
            strtolower(trim($data['email'])),
            $data['password'],
            isset($data['community_id']) ? (int)$data['community_id'] : null
        );

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'REGISTRATION_FAILED',
                $result['error'],
                409
            ));
        }

        return Response::json($response, Response::success(
            [
                'user_id' => $result['user_id'],
                'message' => 'Account created. Please check your email to verify your account.',
            ],
            [],
            201
        ));
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator
            ->required($data, ['email', 'password'])
            ->email($data, 'email');

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Email and password are required',
                422,
                $validator->errors()
            ));
        }

        $result = $this->authService->login(
            strtolower(trim($data['email'])),
            $data['password']
        );

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'AUTH_FAILED',
                $result['error'],
                401
            ));
        }

        return Response::json($response, Response::success([
            'access_token'  => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'user'          => $result['user'],
        ]));
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        if (empty($data['refresh_token'])) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Refresh token is required',
                422
            ));
        }

        $result = $this->authService->refresh($data['refresh_token']);

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'REFRESH_FAILED',
                $result['error'],
                401
            ));
        }

        return Response::json($response, Response::success([
            'access_token'  => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
        ]));
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        if (!empty($data['refresh_token'])) {
            $this->authService->logout($data['refresh_token']);
        }

        return Response::json($response, Response::success(
            ['message' => 'Logged out successfully']
        ));
    }

    /**
     * POST /api/v1/auth/verify-email
     */
    public function verifyEmail(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        if (empty($data['token'])) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Verification token is required',
                422
            ));
        }

        $result = $this->authService->verifyEmail($data['token']);

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'VERIFICATION_FAILED',
                $result['error'],
                400
            ));
        }

        return Response::json($response, Response::success([
            'message' => 'Email verified successfully. You can now log in.',
        ]));
    }

    /**
     * POST /api/v1/auth/resend-verification
     */
    public function resendVerification(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        if (empty($data['email'])) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Email is required',
                422
            ));
        }

        $this->authService->resendVerification(strtolower(trim($data['email'])));

        // Always return success to prevent email enumeration
        return Response::json($response, Response::success([
            'message' => 'If this email is registered, a verification link has been sent.',
        ]));
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator->required($data, ['email'])->email($data, 'email');

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'A valid email address is required',
                422,
                $validator->errors()
            ));
        }

        $this->authService->requestPasswordReset(strtolower(trim($data['email'])));

        // Always return success to prevent email enumeration
        return Response::json($response, Response::success([
            'message' => 'If this email is registered, a password reset link has been sent.',
        ]));
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        $validator = new Validator();
        $validator
            ->required($data, ['token', 'password'])
            ->minLength($data, 'password', 12)
            ->maxLength($data, 'password', 72);

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Please correct the following errors',
                422,
                $validator->errors()
            ));
        }

        $result = $this->authService->resetPassword($data['token'], $data['password']);

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'RESET_FAILED',
                $result['error'],
                400
            ));
        }

        return Response::json($response, Response::success([
            'message' => 'Password reset successfully. You can now log in with your new password.',
        ]));
    }

    /**
     * POST /api/v1/auth/change-password (authenticated)
     */
    public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = (int) $request->getAttribute('user_id');

        $validator = new Validator();
        $validator
            ->required($data, ['current_password', 'new_password'])
            ->minLength($data, 'new_password', 12)
            ->maxLength($data, 'new_password', 72);

        if (!$validator->passes()) {
            return Response::json($response, Response::error(
                'VALIDATION_ERROR',
                'Please correct the following errors',
                422,
                $validator->errors()
            ));
        }

        $result = $this->authService->changePassword(
            $userId,
            $data['current_password'],
            $data['new_password']
        );

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'PASSWORD_CHANGE_FAILED',
                $result['error'],
                400
            ));
        }

        return Response::json($response, Response::success([
            'message' => 'Password changed successfully. Please log in again.',
        ]));
    }
}
