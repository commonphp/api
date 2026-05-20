<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\API\Exceptions\ApiException;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Exceptions\HttpException;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Exceptions\MethodNotAllowedException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use CommonPHP\Router\Exceptions\RouterException;
use CommonPHP\Router\Exceptions\SchemaNotAllowedException;
use CommonPHP\Validation\Exceptions\ValidationException;
use Throwable;

final class ApiExceptionHandler
{
    private ApiResponseFactory $responses;

    public function __construct(
        ?ApiResponseFactory $responses = null,
        private readonly bool $exposeDetails = false,
    ) {
        $this->responses = $responses ?? new ApiResponseFactory();
    }

    public function handle(Throwable $exception, ?Request $request = null): Response
    {
        $headers = [];

        if ($exception instanceof MethodNotAllowedException && $exception->allowedMethods() !== []) {
            $headers['Allow'] = implode(', ', $exception->allowedMethods());
        }

        return $this->responses->problem($this->problemFor($exception, $request), $headers);
    }

    private function problemFor(Throwable $exception, ?Request $request): ApiProblem
    {
        $instance = $request?->path();

        if ($exception instanceof ApiException) {
            return $exception->toProblem($instance);
        }

        if ($exception instanceof ValidationException) {
            $result = $exception->getResult();

            if ($result !== null) {
                return ApiProblem::fromValidation($result, $exception->getMessage(), $instance);
            }

            return ApiProblem::forStatus(
                ResponseStatus::UNPROCESSABLE_CONTENT,
                $exception->getMessage(),
                instance: $instance,
                code: 'validation_failed',
            );
        }

        if ($exception instanceof InvalidRequestException) {
            return ApiProblem::forStatus(
                ResponseStatus::BAD_REQUEST,
                $exception->getMessage(),
                instance: $instance,
                code: 'invalid_request',
            );
        }

        if ($exception instanceof RouteNotFoundException) {
            return ApiProblem::forStatus(
                ResponseStatus::NOT_FOUND,
                $exception->getMessage(),
                instance: $instance,
                code: 'api_route_not_found',
            );
        }

        if ($exception instanceof MethodNotAllowedException) {
            return ApiProblem::forStatus(
                ResponseStatus::METHOD_NOT_ALLOWED,
                $exception->getMessage(),
                instance: $instance,
                code: 'method_not_allowed',
            );
        }

        if ($exception instanceof SchemaNotAllowedException) {
            return ApiProblem::forStatus(
                ResponseStatus::BAD_REQUEST,
                $exception->getMessage(),
                instance: $instance,
                code: 'scheme_not_allowed',
            );
        }

        if ($exception instanceof RouterException) {
            return ApiProblem::forStatus(
                ResponseStatus::INTERNAL_SERVER_ERROR,
                $this->detail($exception),
                instance: $instance,
                code: 'router_error',
            );
        }

        if ($exception instanceof HttpException) {
            return ApiProblem::forStatus(
                ResponseStatus::BAD_REQUEST,
                $exception->getMessage(),
                instance: $instance,
                code: 'http_error',
            );
        }

        return ApiProblem::fromThrowable($exception, includeDetails: $this->exposeDetails, instance: $instance);
    }

    private function detail(Throwable $exception): ?string
    {
        return $this->exposeDetails ? $exception->getMessage() : null;
    }
}
