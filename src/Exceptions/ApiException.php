<?php

declare(strict_types=1);

namespace CommonPHP\API\Exceptions;

use CommonPHP\API\ApiProblem;
use CommonPHP\HTTP\Enums\ResponseStatus;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ApiException extends RuntimeException
{
    private readonly int $statusCode;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = 'API request failed.',
        ResponseStatus|int $status = ResponseStatus::INTERNAL_SERVER_ERROR,
        private readonly ?string $errorCode = null,
        private readonly array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->statusCode = self::normalizeStatus($status);

        parent::__construct($message, $code, $previous);
    }

    public static function withStatus(
        ResponseStatus|int $status,
        string $message,
        ?string $errorCode = null,
    ): self {
        return new self($message, $status, $errorCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function toProblem(?string $instance = null): ApiProblem
    {
        return ApiProblem::forStatus(
            $this->statusCode,
            $this->getMessage(),
            instance: $instance,
            code: $this->errorCode,
            extensions: $this->context,
        );
    }

    private static function normalizeStatus(ResponseStatus|int $status): int
    {
        $status = $status instanceof ResponseStatus ? $status->value : $status;

        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('API exception status code must be between 100 and 599.');
        }

        return $status;
    }
}
