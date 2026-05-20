<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiProblem;
use CommonPHP\API\ApiProblemResponse;
use CommonPHP\API\ApiResponseFactory;
use CommonPHP\API\JsonResponse;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Response;
use CommonPHP\Validation\ValidationError;
use CommonPHP\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ApiResponseFactoryTest extends TestCase
{
    public function testItCreatesJsonResponses(): void
    {
        $response = (new ApiResponseFactory())->json(['ok' => true], ResponseStatus::ACCEPTED, ['X-Test' => 'yes']);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(202, $response->statusCode());
        self::assertSame('yes', $response->header('X-Test'));
        self::assertSame(['ok' => true], $this->decode($response));
    }

    public function testItCreatesSuccessEnvelopes(): void
    {
        $response = (new ApiResponseFactory())->success(
            ['id' => 10],
            ['page' => 1],
            ResponseStatus::OK,
            ['X-Test' => 'yes'],
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame('yes', $response->header('X-Test'));
        self::assertSame([
            'status' => 'success',
            'data' => ['id' => 10],
            'meta' => ['page' => 1],
        ], $this->decode($response));
    }

    public function testItCreatesNamedConvenienceResponses(): void
    {
        $factory = new ApiResponseFactory();
        $ok = $factory->ok(['ready' => true]);
        $created = $factory->created(['id' => 1], '/api/items/1');
        $accepted = $factory->accepted(['queued' => true]);
        $noContent = $factory->noContent(['X-Test' => 'yes']);

        self::assertSame(200, $ok->statusCode());
        self::assertSame(201, $created->statusCode());
        self::assertSame('/api/items/1', $created->header('Location'));
        self::assertSame(202, $accepted->statusCode());
        self::assertSame(204, $noContent->statusCode());
        self::assertSame('yes', $noContent->header('X-Test'));
        self::assertSame('', $noContent->body());
    }

    public function testItCreatesProblemAndErrorResponses(): void
    {
        $factory = new ApiResponseFactory();
        $problem = ApiProblem::forStatus(ResponseStatus::FORBIDDEN, 'No access.', code: 'forbidden');
        $problemResponse = $factory->problem($problem, ['X-Test' => 'yes']);
        $errorResponse = $factory->error('Bad input.', ResponseStatus::BAD_REQUEST, 'bad_input');

        self::assertInstanceOf(ApiProblemResponse::class, $problemResponse);
        self::assertSame('yes', $problemResponse->header('X-Test'));
        self::assertSame('forbidden', $this->decode($problemResponse)['code']);
        self::assertSame(400, $errorResponse->statusCode());
        self::assertSame('bad_input', $this->decode($errorResponse)['code']);
    }

    public function testItCreatesValidationProblemResponses(): void
    {
        $result = new ValidationResult([
            new ValidationError('email', 'Email is invalid.', 'email'),
        ]);

        $response = (new ApiResponseFactory())->validation($result, 'Validation did not pass.');
        $payload = $this->decode($response);

        self::assertSame(422, $response->statusCode());
        self::assertSame('Validation did not pass.', $payload['detail']);
        self::assertSame('email', $payload['errors'][0]['field']);
    }

    public function testItNormalizesActionResultsToResponses(): void
    {
        $factory = new ApiResponseFactory();
        $existing = new Response('existing', 299);
        $problem = ApiProblem::forStatus(404, 'Missing.');
        $validation = new ValidationResult([new ValidationError('name', 'Required.', 'required')]);

        self::assertSame($existing, $factory->from($existing));
        self::assertInstanceOf(ApiProblemResponse::class, $factory->from($problem));
        self::assertSame(422, $factory->from($validation)->statusCode());
        self::assertSame(204, $factory->from(null)->statusCode());

        $arrayResponse = $factory->from(['ok' => true]);
        self::assertInstanceOf(JsonResponse::class, $arrayResponse);
        self::assertSame(['ok' => true], $this->decode($arrayResponse));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
