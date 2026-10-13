<?php

declare(strict_types=1);

namespace Vanshavali\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Vanshavali\Helpers\Response;
use Vanshavali\Helpers\Validator;
use Vanshavali\Repositories\GalleryRepository;
use Vanshavali\Services\AuditService;

/**
 * Controller for photo albums, media galleries, and lightbox viewers.
 */
class GalleryController
{
    private GalleryRepository $gallery;
    private AuditService $audit;

    public function __construct()
    {
        $this->gallery = new GalleryRepository();
        $this->audit   = new AuditService();
    }

    /**
     * GET /api/v1/gallery/albums (public)
     */
    public function albums(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $communityId = isset($params['community_id']) ? (int)$params['community_id'] : null;
        $visibility = $params['visibility'] ?? 'public';

        $albums = $this->gallery->findAllAlbums($communityId, $visibility);
        return Response::json($response, Response::success($albums, ['count' => count($albums)]));
    }

    /**
     * GET /api/v1/gallery/albums/{id} (public)
     */
    public function getAlbum(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $album = $this->gallery->findAlbumById($id);

        if (!$album) {
            return Response::json($response, Response::error('NOT_FOUND', 'Album not found', 404));
        }

        $album['photos'] = $this->gallery->getAlbumPhotos($id);

        return Response::json($response, Response::success($album));
    }

    /**
     * POST /api/v1/gallery/albums (Moderator / Admin)
     */
    public function createAlbum(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        $communityId = $request->getAttribute('community_id');

        $validator = new Validator();
        $validator->required($data, ['title']);

        if (!$validator->passes()) {
            return Response::json($response, Response::error('VALIDATION_ERROR', 'Title is required', 422, $validator->errors()));
        }

        $data['community_id'] = $communityId ?? ($data['community_id'] ?? 1);
        $data['created_by'] = $userId;

        $id = $this->gallery->createAlbum($data);
        $created = $this->gallery->findAlbumById($id);
        $this->audit->log($userId, 'gallery.create_album', 'media_album', $id, null, $created);

        return Response::json($response, Response::success($created, [], 201));
    }

    /**
     * PUT /api/v1/gallery/albums/{id} (Moderator / Admin)
     */
    public function updateAlbum(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $existing = $this->gallery->findAlbumById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Album not found', 404));
        }

        $this->gallery->updateAlbum($id, $data);
        $updated = $this->gallery->findAlbumById($id);
        $this->audit->log($userId, 'gallery.update_album', 'media_album', $id, $existing, $updated);

        return Response::json($response, Response::success($updated));
    }

    /**
     * DELETE /api/v1/gallery/albums/{id} (Moderator / Admin)
     */
    public function deleteAlbum(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $userId = $request->getAttribute('user_id');

        $existing = $this->gallery->findAlbumById($id);
        if (!$existing) {
            return Response::json($response, Response::error('NOT_FOUND', 'Album not found', 404));
        }

        $this->gallery->deleteAlbum($id);
        $this->audit->log($userId, 'gallery.delete_album', 'media_album', $id, $existing, null);

        return Response::json($response, Response::success(['message' => 'Album deleted successfully']));
    }
}
