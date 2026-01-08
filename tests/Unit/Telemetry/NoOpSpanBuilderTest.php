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

use Displace\XaiSdk\Telemetry\NoOpSpan;
use Displace\XaiSdk\Telemetry\NoOpSpanBuilder;
use Displace\XaiSdk\Telemetry\NoOpSpanContext;
use PHPUnit\Framework\TestCase;

final class NoOpSpanBuilderTest extends TestCase
{
    private NoOpSpanBuilder $builder;

    protected function setUp(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\SpanBuilderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $this->builder = new NoOpSpanBuilder();
    }

    public function test_implements_span_builder_interface(): void
    {
        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanBuilderInterface::class, $this->builder);
    }

    public function test_set_parent_returns_self(): void
    {
        $context = \OpenTelemetry\Context\Context::getCurrent();

        $result = $this->builder->setParent($context);

        $this->assertSame($this->builder, $result);
    }

    public function test_set_no_parent_returns_self(): void
    {
        $result = $this->builder->setNoParent();

        $this->assertSame($this->builder, $result);
    }

    public function test_add_link_returns_self(): void
    {
        $spanContext = new NoOpSpanContext();

        $result = $this->builder->addLink($spanContext);

        $this->assertSame($this->builder, $result);
    }

    public function test_add_link_with_attributes_returns_self(): void
    {
        $spanContext = new NoOpSpanContext();

        $result = $this->builder->addLink($spanContext, ['key' => 'value']);

        $this->assertSame($this->builder, $result);
    }

    public function test_set_attribute_returns_self(): void
    {
        $result = $this->builder->setAttribute('key', 'value');

        $this->assertSame($this->builder, $result);
    }

    public function test_set_attributes_returns_self(): void
    {
        $result = $this->builder->setAttributes(['key1' => 'value1', 'key2' => 42]);

        $this->assertSame($this->builder, $result);
    }

    public function test_set_start_timestamp_returns_self(): void
    {
        $result = $this->builder->setStartTimestamp(1234567890000000000);

        $this->assertSame($this->builder, $result);
    }

    public function test_set_span_kind_returns_self(): void
    {
        $result = $this->builder->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_CLIENT);

        $this->assertSame($this->builder, $result);
    }

    public function test_start_span_returns_noop_span(): void
    {
        $span = $this->builder->startSpan();

        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanInterface::class, $span);
        $this->assertInstanceOf(NoOpSpan::class, $span);
    }

    public function test_fluent_interface_chain(): void
    {
        $spanContext = new NoOpSpanContext();

        $span = $this->builder
            ->setParent(\OpenTelemetry\Context\Context::getCurrent())
            ->setAttribute('http.method', 'GET')
            ->setAttributes(['http.url' => 'https://example.com'])
            ->addLink($spanContext)
            ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_CLIENT)
            ->setStartTimestamp(1234567890000000000)
            ->startSpan();

        $this->assertInstanceOf(NoOpSpan::class, $span);
    }
}
