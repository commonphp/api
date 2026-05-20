<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiProblem;
use CommonPHP\API\ApiProblemResponse;
use CommonPHP\API\Contracts\ApiResponseInterface;
use CommonPHP\API\Enums\ApiStatus;
use CommonPHP\API\JsonResponse;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\HeaderBag;
use JsonException;
use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    public function testItEncodesJsonPayloads(): void
    {
        $response = new JsonResponse(['ok' => true, 'path' => '/api'], ResponseStatus::CREATED, ['X-Test' => 'yes']);

        self::assertInstanceOf(ApiResponseInterface::class, $response);
        self::assertSame(201, $response->statusCode());
        self::assertSame('application/json; charset=utf-8', $response->header('Content-Type'));
        self::assertSame('yes', $response->header('X-Test'));
        self::assertSame('{"ok":true,"path":"/api"}', $response->body());
        self::assertSame(['ok' => true, 'path' => '/api'], $response->payload());
        self::assertSame(ApiStatus::SUCCESS, $response->apiStatus());
        self::assertIsInt($response->encodingOptions());
    }

    public function testItMapsApiStatusFromResponseStatus(): void
    {
        self::assertSame(ApiStatus::FAIL, (new JsonResponse(['missing' => true], 404))->apiStatus());
        self::assertSame(ApiStatus::ERROR, (new JsonResponse(['error' => true], 500))->apiStatus());
    }

    public function testItSuppressesBodiesForNoBodyStatuses(): void
    {
        $response = new JsonResponse(['deleted' => true], ResponseStatus::NO_CONTENT);

        self::assertSame(204, $response->statusCode());
        self::assertSame('', $response->body());
        self::assertSame(['deleted' => true], $response->payload());
    }

    public function testItDoesNotMutatePassedHeaderBag(): void
    {
        $headers = new HeaderBag(['X-Test' => 'yes']);
        $response = new JsonResponse(['ok' => true], 200, $headers);

        self::assertFalse($headers->has('Content-Type'));
        self::assertSame('application/json; charset=utf-8', $response->header('Content-Type'));
    }

    public function testItRejectsUnencodablePayloads(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Unable to encode API JSON response');

        try {
            new JsonResponse(['resource' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function testProblemResponsesUseProblemJson(): void
    {
        $problem = ApiProblem::forStatus(ResponseStatus::CONFLICT, 'The resource already exists.');
        $response = new ApiProblemResponse($problem, ['X-Test' => 'yes']);

        self::assertSame($problem, $response->problem());
        self::assertSame(409, $response->statusCode());
        self::assertSame(ApiStatus::FAIL, $response->apiStatus());
        self::assertSame('application/problem+json; charset=utf-8', $response->header('Content-Type'));
        self::assertSame('yes', $response->header('X-Test'));
        self::assertSame($problem->toArray(), $response->payload());
        self::assertSame('The resource already exists.', $this->decode($response->body())['detail']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
