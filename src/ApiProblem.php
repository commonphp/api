<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\Validation\ValidationResult;
use InvalidArgumentException;
use JsonSerializable;
use Throwable;

final class ApiProblem implements JsonSerializable
{
    private readonly int $statusCode;

    /**
     * @param list<array<string, mixed>> $errors
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        private readonly string $title,
        ResponseStatus|int $status = ResponseStatus::INTERNAL_SERVER_ERROR,
        private readonly ?string $detail = null,
        private readonly string $type = 'about:blank',
        private readonly ?string $instance = null,
        private readonly ?string $code = null,
        private readonly array $errors = [],
        private readonly array $extensions = [],
    ) {
        if (trim($title) === '') {
            throw new InvalidArgumentException('API problem title cannot be empty.');
        }

        if (trim($type) === '') {
            throw new InvalidArgumentException('API problem type cannot be empty.');
        }

        $this->statusCode = self::normalizeStatus($status);
    }

    /**
     * @param array<string, mixed> $extensions
     */
    public static function forStatus(
        ResponseStatus|int $status,
        ?string $detail = null,
        ?string $title = null,
        ?string $instance = null,
        ?string $code = null,
        array $extensions = [],
    ): self {
        $statusCode = self::normalizeStatus($status);

        return new self(
            $title ?? self::reasonPhrase($statusCode),
            $statusCode,
            $detail,
            instance: $instance,
            code: $code,
            extensions: $extensions,
        );
    }

    public static function fromValidation(
        ValidationResult $result,
        string $detail = 'The submitted data did not pass validation.',
        ?string $instance = null,
    ): self {
        return new self(
            'Validation Failed',
            ResponseStatus::UNPROCESSABLE_CONTENT,
            $detail,
            instance: $instance,
            code: 'validation_failed',
            errors: $result->toArray(),
        );
    }

    public static function fromThrowable(
        Throwable $exception,
        ResponseStatus|int $status = ResponseStatus::INTERNAL_SERVER_ERROR,
        bool $includeDetails = false,
        ?string $instance = null,
    ): self {
        $statusCode = self::normalizeStatus($status);

        return new self(
            self::reasonPhrase($statusCode),
            $statusCode,
            $includeDetails ? $exception->getMessage() : null,
            instance: $instance,
            code: $statusCode >= 500 ? 'internal_error' : null,
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function detail(): ?string
    {
        return $this->detail;
    }

    public function instance(): ?string
    {
        return $this->instance;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function extensions(): array
    {
        return $this->extensions;
    }

    public function withDetail(?string $detail): self
    {
        return new self(
            $this->title,
            $this->statusCode,
            $detail,
            $this->type,
            $this->instance,
            $this->code,
            $this->errors,
            $this->extensions,
        );
    }

    public function withInstance(?string $instance): self
    {
        return new self(
            $this->title,
            $this->statusCode,
            $this->detail,
            $this->type,
            $instance,
            $this->code,
            $this->errors,
            $this->extensions,
        );
    }

    public function withExtension(string $key, mixed $value): self
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('API problem extension key cannot be empty.');
        }

        $extensions = $this->extensions;
        $extensions[$key] = $value;

        return new self(
            $this->title,
            $this->statusCode,
            $this->detail,
            $this->type,
            $this->instance,
            $this->code,
            $this->errors,
            $extensions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $problem = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->statusCode,
        ];

        if ($this->detail !== null) {
            $problem['detail'] = $this->detail;
        }

        if ($this->instance !== null) {
            $problem['instance'] = $this->instance;
        }

        if ($this->code !== null) {
            $problem['code'] = $this->code;
        }

        if ($this->errors !== []) {
            $problem['errors'] = $this->errors;
        }

        foreach ($this->extensions as $key => $value) {
            if (!array_key_exists($key, $problem)) {
                $problem[$key] = $value;
            }
        }

        return $problem;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function normalizeStatus(ResponseStatus|int $status): int
    {
        $status = $status instanceof ResponseStatus ? $status->value : $status;

        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('API problem status code must be between 100 and 599.');
        }

        return $status;
    }

    private static function reasonPhrase(int $statusCode): string
    {
        return ResponseStatus::tryFrom($statusCode)?->reasonPhrase() ?? 'HTTP ' . $statusCode;
    }
}
