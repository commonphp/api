<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\API\Enums\ApiStatus;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Response;
use CommonPHP\Validation\ValidationResult;

final class ApiResponseFactory
{
    /**
     * @param array<string, mixed> $headers
     */
    public function json(
        mixed $data = null,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $headers
     */
    public function success(
        mixed $data = null,
        array $meta = [],
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'status' => ApiStatus::SUCCESS->value,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return $this->json($payload, $status, $headers);
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $headers
     */
    public function ok(mixed $data = null, array $meta = [], array $headers = []): JsonResponse
    {
        return $this->success($data, $meta, ResponseStatus::OK, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function created(mixed $data = null, ?string $location = null, array $headers = []): JsonResponse
    {
        if ($location !== null) {
            $headers['Location'] = $location;
        }

        return $this->success($data, status: ResponseStatus::CREATED, headers: $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function accepted(mixed $data = null, array $headers = []): JsonResponse
    {
        return $this->success($data, status: ResponseStatus::ACCEPTED, headers: $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function noContent(array $headers = []): Response
    {
        return new Response('', ResponseStatus::NO_CONTENT, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function problem(ApiProblem $problem, array $headers = []): ApiProblemResponse
    {
        return new ApiProblemResponse($problem, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function error(
        string $detail,
        ResponseStatus|int $status = ResponseStatus::BAD_REQUEST,
        ?string $code = null,
        array $headers = [],
    ): ApiProblemResponse {
        return $this->problem(ApiProblem::forStatus($status, $detail, code: $code), $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function validation(
        ValidationResult $result,
        string $message = 'The submitted data did not pass validation.',
        array $headers = [],
    ): ApiProblemResponse {
        return $this->problem(ApiProblem::fromValidation($result, $message), $headers);
    }

    public function from(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof ApiProblem) {
            return $this->problem($result);
        }

        if ($result instanceof ValidationResult) {
            return $this->validation($result);
        }

        if ($result === null) {
            return $this->noContent();
        }

        return $this->json($result);
    }
}
