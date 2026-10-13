<?php

declare(strict_types=1);

namespace Vanshavali\Helpers;

class Response
{
    /**
     * Build a success response envelope.
     */
    public static function success(mixed $data = null, array $meta = [], int $statusCode = 200): array
    {
        return [
            'statusCode' => $statusCode,
            'body' => [
                'success' => true,
                'data'    => $data,
                'meta'    => !empty($meta) ? $meta : null,
                'error'   => null,
            ],
        ];
    }

    /**
     * Build an error response envelope.
     */
    public static function error(
        string $code,
        string $message,
        int $statusCode = 400,
        array $fields = []
    ): array {
        return [
            'statusCode' => $statusCode,
            'body' => [
                'success' => false,
                'data'    => null,
                'error'   => [
                    'code'    => $code,
                    'message' => $message,
                    'fields'  => !empty($fields) ? $fields : null,
                ],
            ],
        ];
    }

    /**
     * Write a JSON response to the PSR-7 response object.
     */
    public static function json(\Psr\Http\Message\ResponseInterface $response, array $envelope): \Psr\Http\Message\ResponseInterface
    {
        $statusCode = $envelope['statusCode'] ?? 200;
        $body = $envelope['body'] ?? $envelope;

        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($statusCode);
    }
}
