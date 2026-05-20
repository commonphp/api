<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Fixtures;

use CommonPHP\API\ApiProblem;
use CommonPHP\API\ApiProblemResponse;
use CommonPHP\API\ApiRequest;
use CommonPHP\API\ApiResponseFactory;
use CommonPHP\API\Contracts\AbstractAction;
use CommonPHP\API\JsonResponse;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\RouteMatch;
use CommonPHP\Validation\ValidationResult;

final class ExposedAction extends AbstractAction
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed
    {
        return $this->ok(['handled' => true]);
    }

    public function responseFactory(): ApiResponseFactory
    {
        return $this->responses();
    }

    public function makeJson(mixed $data, ResponseStatus|int $status = ResponseStatus::OK): JsonResponse
    {
        return $this->json($data, $status);
    }

    public function makeOk(mixed $data = null, array $meta = []): JsonResponse
    {
        return $this->ok($data, $meta);
    }

    public function makeCreated(mixed $data = null, ?string $location = null): JsonResponse
    {
        return $this->created($data, $location);
    }

    public function makeNoContent(): Response
    {
        return $this->noContent();
    }

    public function makeProblem(ApiProblem $problem): ApiProblemResponse
    {
        return $this->problem($problem);
    }

    public function makeValidation(ValidationResult $result): ApiProblemResponse
    {
        return $this->validation($result);
    }
}
