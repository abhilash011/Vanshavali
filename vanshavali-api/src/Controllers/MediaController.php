<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Services\AuditService;
use Vanshavali\Services\MediaService;

/**
 * Controller for handling media/photo uploads, validation, EXIF stripping, and consent flag capture.
 */
class MediaController
{
    private MediaService $mediaService;
    private AuditService $audit;

    public function __construct()
    {
        $this->mediaService = new MediaService();
        $this->audit        = new AuditService();
    }

    /**
     * POST /api/v1/media/upload
     * Upload an image with privacy/EXIF stripping and consent capture.
     */
    public function upload(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        $communityId = $request->getAttribute('community_id');
        $parsedBody = (array) $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        // 2.11: Check consent flag
        $consentConfirmed = !empty($parsedBody['consent_confirmed']) &&
            ($parsedBody['consent_confirmed'] === true || $parsedBody['consent_confirmed'] === '1' || $parsedBody['consent_confirmed'] === 'true');

        if (!$consentConfirmed) {
            return Response::json($response, Response::error(
                'CONSENT_REQUIRED',
                'You must confirm that you have permission/consent to upload this photograph.',
                422
            ));
        }

        if (empty($uploadedFiles['file']) && empty($_FILES['file'])) {
            return Response::json($response, Response::error(
                'FILE_REQUIRED',
                'No file provided for upload. Please send a multipart file in field "file".',
                400
            ));
        }

        // Support standard PHP $_FILES or PSR-7 uploaded file
        $fileArray = $_FILES['file'] ?? null;
        if (!$fileArray && isset($uploadedFiles['file'])) {
            $psrFile = $uploadedFiles['file'];
            $fileArray = [
                'name'     => $psrFile->getClientFilename(),
                'type'     => $psrFile->getClientMediaType(),
                'tmp_name' => $psrFile->getStream()->getMetadata('uri'),
                'error'    => $psrFile->getError(),
                'size'     => $psrFile->getSize(),
            ];
        }

        $result = $this->mediaService->upload($fileArray, (int) $userId, $communityId, $consentConfirmed);

        if (!$result['success']) {
            return Response::json($response, Response::error(
                'UPLOAD_FAILED',
                $result['error'] ?? 'File upload failed',
                400
            ));
        }

        $media = $result['media'];
        $this->audit->log($userId, 'media.upload', 'media_asset', (int) $media['id'], null, [
            'file_path'         => $media['file_path'],
            'consent_confirmed' => true,
        ]);

        return Response::json($response, Response::success($media, [
            'message' => 'Image uploaded successfully with EXIF data stripped.',
        ], 201));
    }

    /**
     * GET /api/v1/media/{id}
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $media = $this->mediaService->findById($id);

        if (!$media) {
            return Response::json($response, Response::error('NOT_FOUND', 'Media not found', 404));
        }

        return Response::json($response, Response::success($media));
    }
}
