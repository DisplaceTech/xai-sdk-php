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

use Displace\XaiSdk\Telemetry\NoOpTracer;
use Displace\XaiSdk\Telemetry\Telemetry;
use Displace\XaiSdk\Telemetry\TelemetryConfig;
use PHPUnit\Framework\TestCase;

final class TelemetryTest extends TestCase
{
    private ?string $originalDisableTracing = null;

    protected function setUp(): void
    {
        $this->originalDisableTracing = getenv(TelemetryConfig::ENV_DISABLE_TRACING) ?: null;
    }

    protected function tearDown(): void
    {
        if ($this->originalDisableTracing === null) {
            putenv(TelemetryConfig::ENV_DISABLE_TRACING);
        } else {
            putenv(TelemetryConfig::ENV_DISABLE_TRACING . '=' . $this->originalDisableTracing);
        }
    }

    public function test_sdk_constants(): void
    {
        $this->assertSame('xai-sdk-php', Telemetry::SDK_NAME);
        $this->assertSame('1.0.0', Telemetry::SDK_VERSION);
    }

    public function test_constructor_throws_when_opentelemetry_not_available(): void
    {
        if (interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK is installed, cannot test missing dependency.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("OpenTelemetry SDK not available");

        new Telemetry();
    }

    public function test_constructor_with_custom_provider(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);

        $telemetry = new Telemetry($provider);

        $this->assertSame($provider, $telemetry->getProvider());
    }

    public function test_get_tracer_returns_noop_tracer_when_disabled(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        putenv(TelemetryConfig::ENV_DISABLE_TRACING . '=1');

        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        $telemetry = new Telemetry($provider);

        $tracer = $telemetry->getTracer();

        $this->assertInstanceOf(NoOpTracer::class, $tracer);
    }

    public function test_get_tracer_returns_real_tracer_when_enabled(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        putenv(TelemetryConfig::ENV_DISABLE_TRACING);

        $mockTracer = $this->createMock(\OpenTelemetry\API\Trace\TracerInterface::class);
        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        $provider->expects($this->once())
            ->method('getTracer')
            ->with(Telemetry::SDK_NAME, Telemetry::SDK_VERSION)
            ->willReturn($mockTracer);

        $telemetry = new Telemetry($provider);
        $tracer = $telemetry->getTracer();

        $this->assertSame($mockTracer, $tracer);
    }

    public function test_get_tracer_with_custom_name(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        putenv(TelemetryConfig::ENV_DISABLE_TRACING);

        $mockTracer = $this->createMock(\OpenTelemetry\API\Trace\TracerInterface::class);
        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        $provider->expects($this->once())
            ->method('getTracer')
            ->with('custom-name', Telemetry::SDK_VERSION)
            ->willReturn($mockTracer);

        $telemetry = new Telemetry($provider);
        $tracer = $telemetry->getTracer('custom-name');

        $this->assertSame($mockTracer, $tracer);
    }

    public function test_setup_console_exporter_throws_with_custom_provider(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        $telemetry = new Telemetry($provider);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add span processor to custom provider');

        $telemetry->setupConsoleExporter();
    }

    public function test_setup_otlp_exporter_throws_with_custom_provider(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        $telemetry = new Telemetry($provider);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add span processor to custom provider');

        $telemetry->setupOtlpExporter();
    }

    public function test_shutdown_does_nothing_with_custom_provider(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        // Shutdown should NOT be called on custom provider
        $provider->expects($this->never())->method($this->anything());

        $telemetry = new Telemetry($provider);
        $telemetry->shutdown();

        $this->assertTrue(true); // No exception thrown
    }

    public function test_force_flush_does_nothing_with_custom_provider(): void
    {
        if (!interface_exists(\OpenTelemetry\API\Trace\TracerProviderInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }

        $provider = $this->createMock(\OpenTelemetry\API\Trace\TracerProviderInterface::class);
        // ForceFlush should NOT be called on custom provider
        $provider->expects($this->never())->method($this->anything());

        $telemetry = new Telemetry($provider);
        $telemetry->forceFlush();

        $this->assertTrue(true); // No exception thrown
    }
}
