<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\API\Contracts\ApiResponseInterface;
use CommonPHP\API\Enums\ApiStatus;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Response;
use JsonException;

class JsonResponse extends Response implements ApiResponseInterface
{
    public const DEFAULT_ENCODING_OPTIONS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function __construct(
        private readonly mixed $payload = null,
        ResponseStatus|int $status = ResponseStatus::OK,
        array|HeaderBag $headers = [],
        private readonly int $encodingOptions = self::DEFAULT_ENCODING_OPTIONS,
        string $contentType = 'application/json; charset=utf-8',
    ) {
        parent::__construct(
            self::encodePayload($payload, $status, $encodingOptions),
            $status,
            self::headersWithContentType($headers, $contentType),
        );
    }

    public function apiStatus(): ApiStatus
    {
        return ApiStatus::fromHttpStatus($this->statusCode());
    }

    public function payload(): mixed
    {
        return $this->payload;
    }

    public function encodingOptions(): int
    {
        return $this->encodingOptions;
    }

    /**
     * @param array<string, mixed>|HeaderBag $headers
     * @return array<string, mixed>|HeaderBag
     */
    private static function headersWithContentType(array|HeaderBag $headers, string $contentType): array|HeaderBag
    {
        if ($headers instanceof HeaderBag) {
            return (clone $headers)->set('Content-Type', $contentType);
        }

        $headers['Content-Type'] = $contentType;

        return $headers;
    }

    private static function encodePayload(
        mixed $payload,
        ResponseStatus|int $status,
        int $encodingOptions,
    ): string {
        if (!ResponseStatus::codeAllowsBody(self::statusCodeFrom($status))) {
            return '';
        }

        try {
            return json_encode($payload, $encodingOptions);
        } catch (JsonException $exception) {
            throw new JsonException('Unable to encode API JSON response: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private static function statusCodeFrom(ResponseStatus|int $status): int
    {
        return $status instanceof ResponseStatus ? $status->value : $status;
    }
}
