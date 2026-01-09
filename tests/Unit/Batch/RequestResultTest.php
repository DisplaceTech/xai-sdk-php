<?php

/**
 * This file is part of the xAI PHP SDK.
 *
 * (c) 2026 Displace Technologies, LLC
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * This work was inspired by X.AI LLC's Python SDK.
 */

declare(strict_types=1);

namespace Displace\XaiSdk\Tests\Unit\Batch;

use Displace\XaiSdk\Batch\RequestResult;
use Displace\XaiSdk\Tests\TestCase;
use Exception;
use LogicException;
use RuntimeException;

final class RequestResultTest extends TestCase
{
    public function test_success_result_creation(): void
    {
        $result = RequestResult::success('test value');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertSame('test value', $result->getValue());
    }

    public function test_failure_result_creation(): void
    {
        $error = new Exception('Test error');
        $result = RequestResult::failure($error);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertSame($error, $result->getError());
    }

    public function test_get_value_throws_on_failure(): void
    {
        $result = RequestResult::failure(new Exception('Test error'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get value from a failure result');

        $result->getValue();
    }

    public function test_get_error_throws_on_success(): void
    {
        $result = RequestResult::success('value');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get error from a success result');

        $result->getError();
    }

    public function test_get_value_or_default(): void
    {
        $success = RequestResult::success('actual value');
        $failure = RequestResult::failure(new Exception('Error'));

        $this->assertSame('actual value', $success->getValueOrDefault('default'));
        $this->assertSame('default', $failure->getValueOrDefault('default'));
    }

    public function test_get_value_or_else(): void
    {
        $success = RequestResult::success('actual value');
        $failure = RequestResult::failure(new RuntimeException('Error message'));

        $this->assertSame('actual value', $success->getValueOrElse(fn ($e) => $e->getMessage()));
        $this->assertSame('Error message', $failure->getValueOrElse(fn ($e) => $e->getMessage()));
    }

    public function test_map_transforms_success(): void
    {
        $result = RequestResult::success(5);

        $mapped = $result->map(fn ($v) => $v * 2);

        $this->assertTrue($mapped->isSuccess());
        $this->assertSame(10, $mapped->getValue());
    }

    public function test_map_preserves_failure(): void
    {
        $error = new Exception('Original error');
        $result = RequestResult::failure($error);

        $mapped = $result->map(fn ($v) => $v * 2);

        $this->assertTrue($mapped->isFailure());
        $this->assertSame($error, $mapped->getError());
    }

    public function test_flat_map_success(): void
    {
        $result = RequestResult::success(5);

        $flatMapped = $result->flatMap(fn ($v) => RequestResult::success($v * 2));

        $this->assertTrue($flatMapped->isSuccess());
        $this->assertSame(10, $flatMapped->getValue());
    }

    public function test_flat_map_to_failure(): void
    {
        $result = RequestResult::success(5);
        $error = new Exception('Mapped error');

        $flatMapped = $result->flatMap(fn ($v) => RequestResult::failure($error));

        $this->assertTrue($flatMapped->isFailure());
        $this->assertSame($error, $flatMapped->getError());
    }

    public function test_flat_map_preserves_failure(): void
    {
        $error = new Exception('Original error');
        $result = RequestResult::failure($error);

        $flatMapped = $result->flatMap(fn ($v) => RequestResult::success($v * 2));

        $this->assertTrue($flatMapped->isFailure());
        $this->assertSame($error, $flatMapped->getError());
    }

    public function test_get_or_throw_returns_value(): void
    {
        $result = RequestResult::success('value');

        $this->assertSame('value', $result->getOrThrow());
    }

    public function test_get_or_throw_throws_error(): void
    {
        $error = new RuntimeException('Test error');
        $result = RequestResult::failure($error);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $result->getOrThrow();
    }

    public function test_success_with_null_value(): void
    {
        $result = RequestResult::success(null);

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getValue());
    }

    public function test_success_with_array_value(): void
    {
        $data = ['key' => 'value', 'nested' => ['a' => 1]];
        $result = RequestResult::success($data);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($data, $result->getValue());
    }
}
