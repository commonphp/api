<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\Enums\ApiStatus;
use PHPUnit\Framework\TestCase;

final class ApiStatusTest extends TestCase
{
    public function testItMapsHttpStatusCodes(): void
    {
        self::assertSame(ApiStatus::SUCCESS, ApiStatus::fromHttpStatus(200));
        self::assertSame(ApiStatus::SUCCESS, ApiStatus::fromHttpStatus(399));
        self::assertSame(ApiStatus::FAIL, ApiStatus::fromHttpStatus(400));
        self::assertSame(ApiStatus::FAIL, ApiStatus::fromHttpStatus(499));
        self::assertSame(ApiStatus::ERROR, ApiStatus::fromHttpStatus(500));
    }

    public function testItReportsStatePredicates(): void
    {
        self::assertTrue(ApiStatus::SUCCESS->isSuccessful());
        self::assertFalse(ApiStatus::SUCCESS->isFailure());
        self::assertFalse(ApiStatus::SUCCESS->isError());

        self::assertFalse(ApiStatus::FAIL->isSuccessful());
        self::assertTrue(ApiStatus::FAIL->isFailure());
        self::assertFalse(ApiStatus::FAIL->isError());

        self::assertFalse(ApiStatus::ERROR->isSuccessful());
        self::assertFalse(ApiStatus::ERROR->isFailure());
        self::assertTrue(ApiStatus::ERROR->isError());
    }
}
