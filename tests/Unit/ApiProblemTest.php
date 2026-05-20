<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiProblem;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\Validation\ValidationError;
use CommonPHP\Validation\ValidationResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiProblemTest extends TestCase
{
    public function testItExposesProblemDetailsAndArrayShape(): void
    {
        $problem = new ApiProblem(
            'Invalid API Request',
            ResponseStatus::BAD_REQUEST,
            'The request was malformed.',
            'https://example.test/problems/invalid-request',
            '/api/items',
            'invalid_request',
            [['field' => 'name', 'message' => 'Name is required.']],
            ['trace_id' => 'abc123', 'title' => 'ignored'],
        );

        self::assertSame('https://example.test/problems/invalid-request', $problem->type());
        self::assertSame('Invalid API Request', $problem->title());
        self::assertSame(400, $problem->statusCode());
        self::assertSame('The request was malformed.', $problem->detail());
        self::assertSame('/api/items', $problem->instance());
        self::assertSame('invalid_request', $problem->code());
        self::assertSame([['field' => 'name', 'message' => 'Name is required.']], $problem->errors());
        self::assertSame(['trace_id' => 'abc123', 'title' => 'ignored'], $problem->extensions());

        self::assertSame([
            'type' => 'https://example.test/problems/invalid-request',
            'title' => 'Invalid API Request',
            'status' => 400,
            'detail' => 'The request was malformed.',
            'instance' => '/api/items',
            'code' => 'invalid_request',
            'errors' => [['field' => 'name', 'message' => 'Name is required.']],
            'trace_id' => 'abc123',
        ], $problem->toArray());
        self::assertSame($problem->toArray(), $problem->jsonSerialize());
    }

    public function testItCreatesProblemForStatus(): void
    {
        $problem = ApiProblem::forStatus(
            ResponseStatus::NOT_FOUND,
            'The endpoint does not exist.',
            instance: '/api/missing',
            code: 'missing',
            extensions: ['resource' => 'endpoint'],
        );

        self::assertSame('Not Found', $problem->title());
        self::assertSame(404, $problem->statusCode());
        self::assertSame('The endpoint does not exist.', $problem->detail());
        self::assertSame('/api/missing', $problem->instance());
        self::assertSame('missing', $problem->code());
        self::assertSame(['resource' => 'endpoint'], $problem->extensions());
    }

    public function testItCreatesProblemFromValidationResult(): void
    {
        $result = new ValidationResult([
            new ValidationError('email', 'Email is required.', 'required'),
        ]);

        $problem = ApiProblem::fromValidation($result, 'Please fix the request.', '/api/users');

        self::assertSame('Validation Failed', $problem->title());
        self::assertSame(422, $problem->statusCode());
        self::assertSame('Please fix the request.', $problem->detail());
        self::assertSame('/api/users', $problem->instance());
        self::assertSame('validation_failed', $problem->code());
        self::assertSame([
            [
                'field' => 'email',
                'message' => 'Email is required.',
                'code' => 'required',
                'value' => null,
                'context' => [],
            ],
        ], $problem->errors());
    }

    public function testItCreatesProblemFromThrowableWithOptionalDetails(): void
    {
        $hidden = ApiProblem::fromThrowable(new RuntimeException('database offline'), instance: '/api/status');
        $exposed = ApiProblem::fromThrowable(
            new RuntimeException('database offline'),
            includeDetails: true,
            instance: '/api/status',
        );

        self::assertSame('Internal Server Error', $hidden->title());
        self::assertSame(500, $hidden->statusCode());
        self::assertNull($hidden->detail());
        self::assertSame('internal_error', $hidden->code());
        self::assertSame('/api/status', $hidden->instance());

        self::assertSame('database offline', $exposed->detail());
    }

    public function testItCreatesModifiedCopies(): void
    {
        $problem = ApiProblem::forStatus(409, 'Original.');
        $withDetail = $problem->withDetail('Updated.');
        $withInstance = $problem->withInstance('/api/conflict');
        $withExtension = $problem->withExtension('trace_id', 'abc123');

        self::assertNotSame($problem, $withDetail);
        self::assertSame('Original.', $problem->detail());
        self::assertSame('Updated.', $withDetail->detail());
        self::assertSame('/api/conflict', $withInstance->instance());
        self::assertSame(['trace_id' => 'abc123'], $withExtension->extensions());
    }

    public function testItRejectsInvalidProblemArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('title cannot be empty');

        new ApiProblem('');
    }

    public function testItRejectsInvalidProblemType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('type cannot be empty');

        new ApiProblem('Bad', type: '');
    }

    public function testItRejectsInvalidStatusCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('status code must be between 100 and 599');

        new ApiProblem('Bad', 99);
    }

    public function testItRejectsEmptyExtensionKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('extension key cannot be empty');

        ApiProblem::forStatus(400)->withExtension('', 'value');
    }
}
