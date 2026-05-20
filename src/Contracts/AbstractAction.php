<?php

declare(strict_types=1);

namespace CommonPHP\API\Contracts;

use CommonPHP\API\ApiProblem;
use CommonPHP\API\ApiProblemResponse;
use CommonPHP\API\ApiResponseFactory;
use CommonPHP\API\JsonResponse;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Response;
use CommonPHP\Validation\ValidationResult;

abstract class AbstractAction implements ActionInterface
{
    private ApiResponseFactory $responses;

    public function __construct(?ApiResponseFactory $responses = null)
    {
        $this->responses = $responses ?? new ApiResponseFactory();
    }

    protected function responses(): ApiResponseFactory
    {
        return $this->responses;
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function json(
        mixed $data,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): JsonResponse {
        return $this->responses->json($data, $status, $headers);
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $headers
     */
    protected function ok(mixed $data = null, array $meta = [], array $headers = []): JsonResponse
    {
        return $this->responses->success($data, $meta, ResponseStatus::OK, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function created(mixed $data = null, ?string $location = null, array $headers = []): JsonResponse
    {
        return $this->responses->created($data, $location, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function noContent(array $headers = []): Response
    {
        return $this->responses->noContent($headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function problem(ApiProblem $problem, array $headers = []): ApiProblemResponse
    {
        return $this->responses->problem($problem, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function validation(ValidationResult $result, array $headers = []): ApiProblemResponse
    {
        return $this->responses->validation($result, headers: $headers);
    }
}
