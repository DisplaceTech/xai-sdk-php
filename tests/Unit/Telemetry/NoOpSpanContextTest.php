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

namespace Displace\XaiSdk\Tests\Unit\Telemetry;

use Displace\XaiSdk\Telemetry\NoOpSpanContext;
use PHPUnit\Framework\TestCase;

final class NoOpSpanContextTest extends TestCase
{
    private NoOpSpanContext $context;

    protected function setUp(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\SpanContextInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $this->context = new NoOpSpanContext();
    }

    public function test_implements_span_context_interface(): void
    {
        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanContextInterface::class, $this->context);
    }

    public function test_get_trace_id_returns_zero_string(): void
    {
        $traceId = $this->context->getTraceId();

        $this->assertSame(str_repeat('0', 32), $traceId);
        $this->assertSame(32, strlen($traceId));
    }

    public function test_get_span_id_returns_zero_string(): void
    {
        $spanId = $this->context->getSpanId();

        $this->assertSame(str_repeat('0', 16), $spanId);
        $this->assertSame(16, strlen($spanId));
    }

    public function test_get_trace_flags_returns_zero(): void
    {
        $this->assertSame(0, $this->context->getTraceFlags());
    }

    public function test_get_trace_state_returns_null(): void
    {
        $this->assertNull($this->context->getTraceState());
    }

    public function test_is_valid_returns_false(): void
    {
        $this->assertFalse($this->context->isValid());
    }

    public function test_is_remote_returns_false(): void
    {
        $this->assertFalse($this->context->isRemote());
    }

    public function test_is_sampled_returns_false(): void
    {
        $this->assertFalse($this->context->isSampled());
    }

    public function test_multiple_instances_return_same_values(): void
    {
        $context1 = new NoOpSpanContext();
        $context2 = new NoOpSpanContext();

        $this->assertSame($context1->getTraceId(), $context2->getTraceId());
        $this->assertSame($context1->getSpanId(), $context2->getSpanId());
        $this->assertSame($context1->getTraceFlags(), $context2->getTraceFlags());
    }
}
