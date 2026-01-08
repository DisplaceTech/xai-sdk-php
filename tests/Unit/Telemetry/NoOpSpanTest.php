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
use Displace\XaiSdk\Telemetry\NoOpSpanContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NoOpSpanTest extends TestCase
{
    private NoOpSpan $span;

    protected function setUp(): void
    {
        if (! interface_exists(\OpenTelemetry\API\Trace\SpanInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $this->span = new NoOpSpan();
    }

    public function test_implements_span_interface(): void
    {
        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanInterface::class, $this->span);
    }

    public function test_get_attribute_returns_null(): void
    {
        $this->assertNull($this->span->getAttribute('any-key'));
        $this->assertNull($this->span->getAttribute('http.method'));
    }

    public function test_get_context_returns_noop_span_context(): void
    {
        $context = $this->span->getContext();

        $this->assertInstanceOf(\OpenTelemetry\API\Trace\SpanContextInterface::class, $context);
        $this->assertInstanceOf(NoOpSpanContext::class, $context);
    }

    public function test_is_recording_returns_false(): void
    {
        $this->assertFalse($this->span->isRecording());
    }

    public function test_set_attribute_returns_self(): void
    {
        $result = $this->span->setAttribute('key', 'value');

        $this->assertSame($this->span, $result);
    }

    public function test_set_attributes_returns_self(): void
    {
        $result = $this->span->setAttributes(['key1' => 'value1', 'key2' => 42]);

        $this->assertSame($this->span, $result);
    }

    public function test_add_event_returns_self(): void
    {
        $result = $this->span->addEvent('event-name');

        $this->assertSame($this->span, $result);
    }

    public function test_add_event_with_attributes_returns_self(): void
    {
        $result = $this->span->addEvent('event-name', ['key' => 'value']);

        $this->assertSame($this->span, $result);
    }

    public function test_add_event_with_timestamp_returns_self(): void
    {
        $result = $this->span->addEvent('event-name', [], 1234567890000000000);

        $this->assertSame($this->span, $result);
    }

    public function test_add_link_returns_self(): void
    {
        $spanContext = new NoOpSpanContext();

        $result = $this->span->addLink($spanContext);

        $this->assertSame($this->span, $result);
    }

    public function test_record_exception_returns_self(): void
    {
        $exception = new RuntimeException('Test error');

        $result = $this->span->recordException($exception);

        $this->assertSame($this->span, $result);
    }

    public function test_record_exception_with_attributes_returns_self(): void
    {
        $exception = new RuntimeException('Test error');

        $result = $this->span->recordException($exception, ['custom' => 'attr']);

        $this->assertSame($this->span, $result);
    }

    public function test_update_name_returns_self(): void
    {
        $result = $this->span->updateName('new-name');

        $this->assertSame($this->span, $result);
    }

    public function test_set_status_returns_self(): void
    {
        $result = $this->span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_OK);

        $this->assertSame($this->span, $result);
    }

    public function test_set_status_with_description_returns_self(): void
    {
        $result = $this->span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, 'Something went wrong');

        $this->assertSame($this->span, $result);
    }

    public function test_end_does_not_throw(): void
    {
        // Should not throw any exceptions
        $this->span->end();
        $this->span->end(1234567890000000000);

        $this->assertTrue(true); // If we got here, no exception was thrown
    }

    public function test_store_in_context_returns_context(): void
    {
        $context = \OpenTelemetry\Context\Context::getCurrent();

        $result = $this->span->storeInContext($context);

        $this->assertInstanceOf(\OpenTelemetry\Context\ContextInterface::class, $result);
    }

    public function test_get_current_returns_noop_span(): void
    {
        $span = NoOpSpan::getCurrent();

        $this->assertInstanceOf(NoOpSpan::class, $span);
    }

    public function test_from_context_returns_noop_span(): void
    {
        $context = \OpenTelemetry\Context\Context::getCurrent();

        $span = NoOpSpan::fromContext($context);

        $this->assertInstanceOf(NoOpSpan::class, $span);
    }

    public function test_get_invalid_returns_noop_span(): void
    {
        $span = NoOpSpan::getInvalid();

        $this->assertInstanceOf(NoOpSpan::class, $span);
    }

    public function test_wrap_returns_noop_span(): void
    {
        $spanContext = new NoOpSpanContext();

        $span = NoOpSpan::wrap($spanContext);

        $this->assertInstanceOf(NoOpSpan::class, $span);
    }

    public function test_activate_returns_scope(): void
    {
        $scope = $this->span->activate();

        $this->assertInstanceOf(\OpenTelemetry\Context\ScopeInterface::class, $scope);
    }

    public function test_fluent_interface_chain(): void
    {
        $result = $this->span
            ->setAttribute('http.method', 'GET')
            ->setAttributes(['http.url' => 'https://example.com'])
            ->addEvent('request.started')
            ->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_OK);

        $this->assertSame($this->span, $result);
    }
}
