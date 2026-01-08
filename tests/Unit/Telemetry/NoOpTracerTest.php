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

use Displace\XaiSdk\Telemetry\NoOpSpanBuilder;
use Displace\XaiSdk\Telemetry\NoOpTracer;
use PHPUnit\Framework\TestCase;

final class NoOpTracerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }
    }

    public function test_implements_tracer_interface(): void
    {
        $tracer = new NoOpTracer();

        $this->assertInstanceOf(\OpenTelemetry\API\Trace\TracerInterface::class, $tracer);
    }

    public function test_span_builder_returns_noop_span_builder(): void
    {
        $tracer = new NoOpTracer();

        $builder = $tracer->spanBuilder('test-span');

        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanBuilderInterface::class, $builder);
        $this->assertInstanceOf(NoOpSpanBuilder::class, $builder);
    }

    public function test_span_builder_accepts_any_span_name(): void
    {
        $tracer = new NoOpTracer();

        $builder1 = $tracer->spanBuilder('operation-1');
        $builder2 = $tracer->spanBuilder('http.request');
        $builder3 = $tracer->spanBuilder('');

        $this->assertInstanceOf(NoOpSpanBuilder::class, $builder1);
        $this->assertInstanceOf(NoOpSpanBuilder::class, $builder2);
        $this->assertInstanceOf(NoOpSpanBuilder::class, $builder3);
    }

    public function test_is_enabled_returns_false(): void
    {
        $tracer = new NoOpTracer();

        $this->assertFalse($tracer->isEnabled());
    }
}
