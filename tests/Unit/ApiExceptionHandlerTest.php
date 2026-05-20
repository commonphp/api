<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiExceptionHandler;
use CommonPHP\API\Exceptions\ApiException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Exceptions\HttpException;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Exceptions\MethodNotAllowedException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use CommonPHP\Router\Exceptions\RouterException;
use CommonPHP\Router\Exceptions\SchemaNotAllowedException;
use CommonPHP\Validation\Exceptions\ValidationException;
use CommonPHP\Validation\ValidationError;
use CommonPHP\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiExceptionHandlerTest extends TestCase
{
    public function testItHandlesApiExceptions(): void
    {
        $response = $this->handle(new ApiException('No access.', 403, 'forbidden', ['permission' => 'users.view']));
        $payload = $this->decode($response);

        self::assertSame(403, $response->statusCode());
        self::assertSame('forbidden', $payload['code']);
        self::assertSame('No access.', $payload['detail']);
        self::assertSame('/api/users', $payload['instance']);
        self::assertSame('users.view', $payload['permission']);
    }

    public function testItHandlesValidationExceptionsWithResults(): void
    {
        $result = new ValidationResult([
            new ValidationError('email', 'Email is required.', 'required'),
        ]);
        $response = $this->handle(new ValidationException('Validation failed.', $result));
        $payload = $this->decode($response);

        self::assertSame(422, $response->statusCode());
        self::assertSame('validation_failed', $payload['code']);
        self::assertSame('Validation failed.', $payload['detail']);
        self::assertSame('email', $payload['errors'][0]['field']);
    }

    public function testItHandlesValidationExceptionsWithoutResults(): void
    {
        $response = $this->handle(new ValidationException('Validation did not run.'));
        $payload = $this->decode($response);

        self::assertSame(422, $response->statusCode());
        self::assertSame('Validation did not run.', $payload['detail']);
        self::assertSame('validation_failed', $payload['code']);
    }

    public function testItHandlesHttpAndRouterClientErrors(): void
    {
        $invalidRequest = $this->decode($this->handle(InvalidRequestException::because('Bad JSON.')));
        $notFound = $this->decode($this->handle(RouteNotFoundException::forPath('/api/missing', 'GET')));
        $scheme = $this->decode($this->handle(SchemaNotAllowedException::forPath('http', '/api/secure', ['https'])));

        self::assertSame('invalid_request', $invalidRequest['code']);
        self::assertSame('api_route_not_found', $notFound['code']);
        self::assertSame('scheme_not_allowed', $scheme['code']);
    }

    public function testItHandlesMethodNotAllowedWithAllowHeader(): void
    {
        $response = $this->handle(MethodNotAllowedException::forPath('POST', '/api/items', ['GET', 'HEAD']));
        $payload = $this->decode($response);

        self::assertSame(405, $response->statusCode());
        self::assertSame('GET, HEAD', $response->header('Allow'));
        self::assertSame('method_not_allowed', $payload['code']);
    }

    public function testItHidesAndExposesRouterExceptionDetails(): void
    {
        $hidden = $this->decode($this->handle(new RouterException('route internals failed')));
        $exposed = $this->decode((new ApiExceptionHandler(exposeDetails: true))->handle(
            new RouterException('route internals failed'),
            $this->request(),
        ));

        self::assertArrayNotHasKey('detail', $hidden);
        self::assertSame('router_error', $hidden['code']);
        self::assertSame('route internals failed', $exposed['detail']);
    }

    public function testItHandlesHttpExceptions(): void
    {
        $response = $this->handle(new HttpException('Bad HTTP state.'));
        $payload = $this->decode($response);

        self::assertSame(400, $response->statusCode());
        self::assertSame('http_error', $payload['code']);
        self::assertSame('Bad HTTP state.', $payload['detail']);
    }

    public function testItHidesAndExposesGenericThrowableDetails(): void
    {
        $hidden = $this->decode($this->handle(new RuntimeException('database offline')));
        $exposed = $this->decode((new ApiExceptionHandler(exposeDetails: true))->handle(
            new RuntimeException('database offline'),
            $this->request(),
        ));

        self::assertSame(500, $hidden['status']);
        self::assertSame('internal_error', $hidden['code']);
        self::assertArrayNotHasKey('detail', $hidden);
        self::assertSame('database offline', $exposed['detail']);
    }

    private function handle(\Throwable $exception): Response
    {
        return (new ApiExceptionHandler())->handle($exception, $this->request());
    }

    private function request(): Request
    {
        return new Request(RequestMethod::GET, '/api/users');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        self::assertSame('application/problem+json; charset=utf-8', $response->header('Content-Type'));
        $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
