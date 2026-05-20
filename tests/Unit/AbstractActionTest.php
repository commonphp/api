<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiProblem;
use CommonPHP\API\ApiResponseFactory;
use CommonPHP\API\Tests\Fixtures\ExposedAction;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\Validation\ValidationError;
use CommonPHP\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

final class AbstractActionTest extends TestCase
{
    public function testItProvidesResponseHelpersToActions(): void
    {
        $factory = new ApiResponseFactory();
        $action = new ExposedAction($factory);

        self::assertSame($factory, $action->responseFactory());
        self::assertSame(['ok' => true], $this->decode($action->makeJson(['ok' => true])));
        self::assertSame([
            'status' => 'success',
            'data' => ['id' => 1],
            'meta' => ['page' => 1],
        ], $this->decode($action->makeOk(['id' => 1], ['page' => 1])));

        $created = $action->makeCreated(['id' => 1], '/api/items/1');
        self::assertSame(201, $created->statusCode());
        self::assertSame('/api/items/1', $created->header('Location'));

        $noContent = $action->makeNoContent();
        self::assertSame(204, $noContent->statusCode());
        self::assertSame('', $noContent->body());

        $problem = $action->makeProblem(ApiProblem::forStatus(ResponseStatus::CONFLICT, 'Already exists.'));
        self::assertSame(409, $problem->statusCode());

        $validation = $action->makeValidation(new ValidationResult([
            new ValidationError('name', 'Name is required.', 'required'),
        ]));
        self::assertSame(422, $validation->statusCode());
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(\CommonPHP\HTTP\Response $response): array
    {
        $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
