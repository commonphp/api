<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiRequest;
use CommonPHP\API\Exceptions\UnsupportedContentTypeException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\RequestScheme;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

final class ApiRequestTest extends TestCase
{
    public function testItCopiesHttpRequests(): void
    {
        $request = new Request(
            RequestMethod::POST,
            'https://example.test/api/items?search=ada',
            ['Content-Type' => 'application/json', 'Host' => 'example.test'],
            '{"name":"Ada"}',
            ['page' => '2'],
            ['parsed' => true],
            ['sid' => 'cookie'],
            ['upload' => 'file'],
            ['REMOTE_ADDR' => '127.0.0.1'],
            RequestScheme::HTTPS,
            ['user' => 'ada'],
        );

        $apiRequest = ApiRequest::fromRequest($request);

        self::assertSame(RequestMethod::POST, $apiRequest->method());
        self::assertSame('https://example.test/api/items?search=ada', $apiRequest->uri());
        self::assertSame('/api/items', $apiRequest->path());
        self::assertSame('application/json', $apiRequest->header('Content-Type'));
        self::assertSame('{"name":"Ada"}', $apiRequest->body());
        self::assertSame(['page' => '2'], $apiRequest->queryParams());
        self::assertSame(['parsed' => true], $apiRequest->parsedBody());
        self::assertSame(['sid' => 'cookie'], $apiRequest->cookies());
        self::assertSame(['upload' => 'file'], $apiRequest->files());
        self::assertSame(['REMOTE_ADDR' => '127.0.0.1'], $apiRequest->serverParams());
        self::assertSame(RequestScheme::HTTPS, $apiRequest->scheme());
        self::assertSame('ada', $apiRequest->attribute('user'));

        self::assertSame($apiRequest, ApiRequest::fromRequest($apiRequest));
    }

    public function testItReadsPayloadFromParsedArraysAndObjects(): void
    {
        $arrayRequest = new ApiRequest(RequestMethod::POST, '/api/items', [], '', [], ['name' => 'Ada']);
        $objectRequest = new ApiRequest(RequestMethod::POST, '/api/items', [], '', [], (object) ['name' => 'Ada']);

        self::assertSame(['name' => 'Ada'], $arrayRequest->payload());
        self::assertSame(['name' => 'Ada'], $objectRequest->payload());
    }

    public function testItReadsJsonPayloadsAndDotInput(): void
    {
        $request = new ApiRequest(
            RequestMethod::POST,
            '/api/items',
            ['Content-Type' => 'application/vnd.api+json'],
            '{"user":{"name":"Ada"},"active":true,"empty":null}',
        );

        self::assertSame(['user' => ['name' => 'Ada'], 'active' => true, 'empty' => null], $request->payload());
        self::assertSame('Ada', $request->input('user.name'));
        self::assertTrue($request->input('active'));
        self::assertNull($request->input('empty'));
        self::assertSame('fallback', $request->input('missing', 'fallback'));
        self::assertTrue($request->hasInput('empty'));
        self::assertFalse($request->hasInput('missing'));
        self::assertSame($request->payload(), $request->input());
    }

    public function testItReturnsEmptyPayloadForEmptyBodies(): void
    {
        $request = new ApiRequest(RequestMethod::POST, '/api/items');

        self::assertSame([], $request->payload());
    }

    public function testItRejectsUnsupportedContentTypes(): void
    {
        $request = new ApiRequest(RequestMethod::POST, '/api/items', ['Content-Type' => 'text/plain'], 'name=Ada');

        $this->expectException(UnsupportedContentTypeException::class);

        $request->payload();
    }

    public function testItRejectsInvalidParsedBodies(): void
    {
        $request = new ApiRequest(RequestMethod::POST, '/api/items', [], '', [], 'not-an-array');

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Parsed API body must be an array or object');

        $request->payload();
    }

    public function testItRejectsJsonBodiesThatAreNotArrays(): void
    {
        $request = new ApiRequest(RequestMethod::POST, '/api/items', ['Content-Type' => 'application/json'], 'true');

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('API JSON body must decode to an object or array');

        $request->payload();
    }

    public function testItRejectsInvalidJsonBodies(): void
    {
        $request = new ApiRequest(RequestMethod::POST, '/api/items', ['Content-Type' => 'application/json'], '{"bad"');

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid JSON body');

        $request->payload();
    }

    public function testItExposesRouteMatchesAndParameters(): void
    {
        $route = new Route(RouteMethod::GET, '/api/users/{id}', static fn (): array => []);
        $match = new RouteMatch($route, ['id' => '42'], '/api/users/42', RouteMethod::GET);
        $request = (new ApiRequest(RequestMethod::GET, '/api/users/42'))->withRouteMatch($match);

        self::assertSame($match, $request->routeMatch());
        self::assertSame(['id' => '42'], $request->routeParameters());
        self::assertSame('42', $request->routeParameter('id'));
        self::assertSame('fallback', $request->routeParameter('missing', 'fallback'));
    }

    public function testItReadsRouteParametersFromAttributesWhenNoMatchExists(): void
    {
        $request = (new ApiRequest(RequestMethod::GET, '/api/users/42'))
            ->withAttribute(ApiRequest::ROUTE_PARAMETERS_ATTRIBUTE, ['id' => '42']);

        self::assertNull($request->routeMatch());
        self::assertSame(['id' => '42'], $request->routeParameters());
    }
}
