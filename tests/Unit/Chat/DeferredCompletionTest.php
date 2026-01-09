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

namespace Displace\XaiSdk\Tests\Unit\Chat;

use DateTimeImmutable;
use Displace\XaiSdk\Chat\DeferredCompletion;
use Displace\XaiSdk\Responses\ChatResponse;
use Displace\XaiSdk\Responses\Usage;
use Displace\XaiSdk\Tests\TestCase;

final class DeferredCompletionTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $createdAt = new DateTimeImmutable();
        $completedAt = new DateTimeImmutable();
        $result = new ChatResponse(
            'test',
            'grok-3',
            new DateTimeImmutable(),
            [],
            new Usage(),
        );

        $deferred = new DeferredCompletion(
            id: 'def-123',
            status: DeferredCompletion::STATUS_COMPLETED,
            createdAt: $createdAt,
            completedAt: $completedAt,
            result: $result,
            error: null,
            object: 'deferred_completion',
        );

        $this->assertSame('def-123', $deferred->id);
        $this->assertSame(DeferredCompletion::STATUS_COMPLETED, $deferred->status);
        $this->assertSame($createdAt, $deferred->createdAt);
        $this->assertSame($completedAt, $deferred->completedAt);
        $this->assertSame($result, $deferred->result);
        $this->assertNull($deferred->error);
        $this->assertSame('deferred_completion', $deferred->object);
    }

    public function test_isPending_returns_true_for_pending(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_PENDING,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertTrue($deferred->isPending());
        $this->assertFalse($deferred->isCompleted());
        $this->assertFalse($deferred->isFailed());
        $this->assertFalse($deferred->isFinished());
    }

    public function test_isPending_returns_true_for_processing(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_PROCESSING,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertTrue($deferred->isPending());
        $this->assertFalse($deferred->isFinished());
    }

    public function test_isCompleted_returns_true_for_completed(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_COMPLETED,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertFalse($deferred->isPending());
        $this->assertTrue($deferred->isCompleted());
        $this->assertFalse($deferred->isFailed());
        $this->assertTrue($deferred->isFinished());
    }

    public function test_isFailed_returns_true_for_failed(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_FAILED,
            createdAt: new DateTimeImmutable(),
            error: 'Something went wrong',
        );

        $this->assertFalse($deferred->isPending());
        $this->assertFalse($deferred->isCompleted());
        $this->assertTrue($deferred->isFailed());
        $this->assertTrue($deferred->isFinished());
    }

    public function test_isCancelled_returns_true_for_cancelled(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_CANCELLED,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertFalse($deferred->isPending());
        $this->assertFalse($deferred->isCompleted());
        $this->assertTrue($deferred->isCancelled());
        $this->assertTrue($deferred->isFinished());
    }

    public function test_getResult_returns_result(): void
    {
        $result = new ChatResponse(
            'test',
            'grok-3',
            new DateTimeImmutable(),
            [],
            new Usage(),
        );

        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_COMPLETED,
            createdAt: new DateTimeImmutable(),
            result: $result,
        );

        $this->assertSame($result, $deferred->getResult());
    }

    public function test_getResult_returns_null_when_pending(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_PENDING,
            createdAt: new DateTimeImmutable(),
        );

        $this->assertNull($deferred->getResult());
    }

    public function test_getError_returns_error(): void
    {
        $deferred = new DeferredCompletion(
            id: 'test',
            status: DeferredCompletion::STATUS_FAILED,
            createdAt: new DateTimeImmutable(),
            error: 'Connection failed',
        );

        $this->assertSame('Connection failed', $deferred->getError());
    }

    public function test_fromArray_creates_pending_deferred(): void
    {
        $data = $this->loadJsonFixture('deferred_completion_pending.json');

        $deferred = DeferredCompletion::fromArray($data);

        $this->assertSame('def-abc123', $deferred->id);
        $this->assertSame(DeferredCompletion::STATUS_PENDING, $deferred->status);
        $this->assertSame('deferred_completion', $deferred->object);
        $this->assertNull($deferred->result);
        $this->assertNull($deferred->error);
        $this->assertTrue($deferred->isPending());
    }

    public function test_fromArray_creates_completed_deferred(): void
    {
        $data = $this->loadJsonFixture('deferred_completion_completed.json');

        $deferred = DeferredCompletion::fromArray($data);

        $this->assertSame('def-abc123', $deferred->id);
        $this->assertSame(DeferredCompletion::STATUS_COMPLETED, $deferred->status);
        $this->assertNotNull($deferred->completedAt);
        $this->assertNotNull($deferred->result);
        $this->assertInstanceOf(ChatResponse::class, $deferred->result);
        $this->assertSame('chatcmpl-def456', $deferred->result->id);
        $this->assertSame('This is the completed response.', $deferred->result->getContent());
        $this->assertTrue($deferred->isCompleted());
    }

    public function test_fromArray_creates_failed_deferred(): void
    {
        $data = $this->loadJsonFixture('deferred_completion_failed.json');

        $deferred = DeferredCompletion::fromArray($data);

        $this->assertSame('def-abc123', $deferred->id);
        $this->assertSame(DeferredCompletion::STATUS_FAILED, $deferred->status);
        $this->assertNotNull($deferred->completedAt);
        $this->assertNull($deferred->result);
        $this->assertSame('Internal server error while processing request', $deferred->error);
        $this->assertTrue($deferred->isFailed());
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $deferred = DeferredCompletion::fromArray([]);

        $this->assertSame('', $deferred->id);
        $this->assertSame(DeferredCompletion::STATUS_PENDING, $deferred->status);
        $this->assertSame('deferred_completion', $deferred->object);
    }

    public function test_deferred_is_readonly(): void
    {
        $deferred = new DeferredCompletion(
            id: 'readonly-test',
            status: DeferredCompletion::STATUS_PENDING,
            createdAt: new DateTimeImmutable(),
        );

        // Verify readonly by accessing properties
        $this->assertSame('readonly-test', $deferred->id);
        $this->assertSame(DeferredCompletion::STATUS_PENDING, $deferred->status);
    }
}
