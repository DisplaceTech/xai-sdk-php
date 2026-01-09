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

use Displace\XaiSdk\Exceptions\DeferredCompletionException;
use Displace\XaiSdk\Tests\TestCase;

final class DeferredCompletionExceptionTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $exception = new DeferredCompletionException(
            'Test message',
            'def-123',
            'test_reason',
        );

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame('def-123', $exception->deferredId);
        $this->assertSame('test_reason', $exception->reason);
    }

    public function test_failed_creates_exception(): void
    {
        $exception = DeferredCompletionException::failed('def-123', 'Server error');

        $this->assertStringContainsString('def-123', $exception->getMessage());
        $this->assertStringContainsString('failed', $exception->getMessage());
        $this->assertStringContainsString('Server error', $exception->getMessage());
        $this->assertSame('def-123', $exception->deferredId);
        $this->assertSame('Server error', $exception->reason);
    }

    public function test_failed_without_error_message(): void
    {
        $exception = DeferredCompletionException::failed('def-123');

        $this->assertStringContainsString('def-123', $exception->getMessage());
        $this->assertStringContainsString('failed', $exception->getMessage());
        $this->assertNull($exception->reason);
    }

    public function test_cancelled_creates_exception(): void
    {
        $exception = DeferredCompletionException::cancelled('def-456');

        $this->assertStringContainsString('def-456', $exception->getMessage());
        $this->assertStringContainsString('cancelled', $exception->getMessage());
        $this->assertSame('def-456', $exception->deferredId);
        $this->assertSame('cancelled', $exception->reason);
    }

    public function test_timeout_creates_exception(): void
    {
        $exception = DeferredCompletionException::timeout('def-789', 60);

        $this->assertStringContainsString('def-789', $exception->getMessage());
        $this->assertStringContainsString('Timed out', $exception->getMessage());
        $this->assertStringContainsString('60 seconds', $exception->getMessage());
        $this->assertSame('def-789', $exception->deferredId);
        $this->assertSame('timeout', $exception->reason);
    }

    public function test_resultMissing_creates_exception(): void
    {
        $exception = DeferredCompletionException::resultMissing('def-abc');

        $this->assertStringContainsString('def-abc', $exception->getMessage());
        $this->assertStringContainsString('result is missing', $exception->getMessage());
        $this->assertSame('def-abc', $exception->deferredId);
        $this->assertSame('result_missing', $exception->reason);
    }
}
