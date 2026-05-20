<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\Exceptions\ApiException;
use CommonPHP\API\Exceptions\InvalidActionException;
use CommonPHP\API\Exceptions\UnsupportedContentTypeException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteMatch;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiExceptionsTest extends TestCase
{
    public function testApiExceptionStoresStatusCodeErrorCodeAndContext(): void
    {
        $exception = new ApiException(
            'No access.',
            ResponseStatus::FORBIDDEN,
            'forbidden',
            ['permission' => 'users.view'],
        );
        $problem = $exception->toProblem('/api/users');

        self::assertSame(403, $exception->statusCode());
        self::assertSame('forbidden', $exception->errorCode());
        self::assertSame(['permission' => 'users.view'], $exception->context());
        self::assertSame(403, $problem->statusCode());
        self::assertSame('No access.', $problem->detail());
        self::assertSame('/api/users', $problem->instance());
        self::assertSame(['permission' => 'users.view'], $problem->extensions());
    }

    public function testApiExceptionCanBeCreatedWithStatus(): void
    {
        $exception = ApiException::withStatus(409, 'Already exists.', 'conflict');

        self::assertSame(409, $exception->statusCode());
        self::assertSame('conflict', $exception->errorCode());
        self::assertSame('Already exists.', $exception->getMessage());
    }

    public function testApiExceptionRejectsInvalidStatusCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('status code must be between 100 and 599');

        new ApiException('Bad', 600);
    }

    public function testInvalidActionExceptionDescribesBadHandlers(): void
    {
        $match = $this->match();
        $exception = InvalidActionException::forHandler($match, 'stdClass');

        self::assertSame(500, $exception->statusCode());
        self::assertSame('api.invalid_action', $exception->errorCode());
        self::assertSame(['route' => 'GET /api/items/{id}', 'handler_type' => 'stdClass'], $exception->context());
        self::assertStringContainsString('not dispatchable', $exception->getMessage());
    }

    public function testInvalidActionExceptionPreservesPreviousFailures(): void
    {
        $previous = new RuntimeException('boom');
        $exception = InvalidActionException::failed($this->match(), $previous);

        self::assertSame(500, $exception->statusCode());
        self::assertSame('api.action_failed', $exception->errorCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['route' => 'GET /api/items/{id}'], $exception->context());
    }

    public function testUnsupportedContentTypeExceptionDescribesRequest(): void
    {
        $request = new Request(RequestMethod::POST, '/api/items', ['Content-Type' => 'text/plain']);
        $exception = UnsupportedContentTypeException::forRequest($request, ['application/json', 'application/vnd.api+json']);

        self::assertSame(415, $exception->statusCode());
        self::assertSame('api.unsupported_content_type', $exception->errorCode());
        self::assertSame('text/plain', $exception->contentType());
        self::assertSame(['application/json', 'application/vnd.api+json'], $exception->supportedContentTypes());
        self::assertSame([
            'content_type' => 'text/plain',
            'supported_content_types' => ['application/json', 'application/vnd.api+json'],
        ], $exception->context());
    }

    private function match(): RouteMatch
    {
        return new RouteMatch(
            new Route(RouteMethod::GET, '/api/items/{id}', static fn (): array => []),
            ['id' => '42'],
            '/api/items/42',
            RouteMethod::GET,
        );
    }
}
