<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\API\Enums\ApiStatus;
use CommonPHP\HTTP\HeaderBag;

final class ApiProblemResponse extends JsonResponse
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function __construct(
        private readonly ApiProblem $problem,
        array|HeaderBag $headers = [],
    ) {
        parent::__construct(
            $problem->toArray(),
            $problem->statusCode(),
            $headers,
            self::DEFAULT_ENCODING_OPTIONS,
            'application/problem+json; charset=utf-8',
        );
    }

    public function problem(): ApiProblem
    {
        return $this->problem;
    }

    public function apiStatus(): ApiStatus
    {
        return ApiStatus::fromHttpStatus($this->problem->statusCode());
    }
}
