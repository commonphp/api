<?php

declare(strict_types=1);

namespace CommonPHP\API\Enums;

enum ApiStatus: string
{
    case SUCCESS = 'success';
    case FAIL = 'fail';
    case ERROR = 'error';

    public static function fromHttpStatus(int $statusCode): self
    {
        if ($statusCode < 400) {
            return self::SUCCESS;
        }

        if ($statusCode < 500) {
            return self::FAIL;
        }

        return self::ERROR;
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isFailure(): bool
    {
        return $this === self::FAIL;
    }

    public function isError(): bool
    {
        return $this === self::ERROR;
    }
}
