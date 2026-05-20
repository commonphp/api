<?php

declare(strict_types=1);

namespace CommonPHP\API\Contracts;

use CommonPHP\API\Enums\ApiStatus;

interface ApiResponseInterface
{
    public function apiStatus(): ApiStatus;

    public function payload(): mixed;
}
