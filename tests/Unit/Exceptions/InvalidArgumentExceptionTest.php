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

namespace Displace\XaiSdk\Tests\Unit\Exceptions;

use Displace\XaiSdk\Exceptions\InvalidArgumentException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;

final class InvalidArgumentExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new InvalidArgumentException('Invalid input');

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_message_is_set(): void
    {
        $exception = new InvalidArgumentException('The value is invalid');

        $this->assertSame('The value is invalid', $exception->getMessage());
    }

    public function test_parameter_is_null_by_default(): void
    {
        $exception = new InvalidArgumentException('Invalid');

        $this->assertNull($exception->parameter);
    }

    public function test_parameter_can_be_set(): void
    {
        $exception = new InvalidArgumentException('Invalid model', 'model');

        $this->assertSame('model', $exception->parameter);
    }

    public function test_code_is_zero(): void
    {
        $exception = new InvalidArgumentException('Error');

        $this->assertSame(0, $exception->getCode());
    }

    public function test_missingRequired_creates_exception(): void
    {
        $exception = InvalidArgumentException::missingRequired('api_key');

        $this->assertStringContainsString('api_key', $exception->getMessage());
        $this->assertStringContainsString('required', $exception->getMessage());
        $this->assertSame('api_key', $exception->parameter);
    }

    public function test_invalidType_creates_exception(): void
    {
        $exception = InvalidArgumentException::invalidType('temperature', 'float', 'string');

        $this->assertStringContainsString('temperature', $exception->getMessage());
        $this->assertStringContainsString('float', $exception->getMessage());
        $this->assertStringContainsString('string', $exception->getMessage());
        $this->assertSame('temperature', $exception->parameter);
    }

    public function test_invalidValue_creates_exception(): void
    {
        $exception = InvalidArgumentException::invalidValue('model', 'model not found');

        $this->assertStringContainsString('model', $exception->getMessage());
        $this->assertStringContainsString('model not found', $exception->getMessage());
        $this->assertSame('model', $exception->parameter);
    }

    public function test_emptyValue_creates_exception(): void
    {
        $exception = InvalidArgumentException::emptyValue('messages');

        $this->assertStringContainsString('messages', $exception->getMessage());
        $this->assertStringContainsString('cannot be empty', $exception->getMessage());
        $this->assertSame('messages', $exception->parameter);
    }

    public function test_parameter_is_readonly(): void
    {
        $exception = new InvalidArgumentException('Error', 'test_param');

        $this->assertSame('test_param', $exception->parameter);
    }
}
