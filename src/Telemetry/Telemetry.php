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

namespace Displace\XaiSdk\Telemetry;

use InvalidArgumentException;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use RuntimeException;

/**
 * Telemetry configuration and tracing setup for the xAI SDK.
 *
 * This class provides OpenTelemetry-based distributed tracing capabilities for monitoring
 * and debugging xAI SDK operations. It allows you to configure span processors to export
 * telemetry data to observability platforms.
 *
 * The Telemetry class handles TracerProvider setup and configuration. You can either provide
 * your own TracerProvider instance for custom configuration, or let the class create one
 * automatically with sensible defaults.
 *
 * Environment Variables:
 * - XAI_SDK_DISABLE_TRACING: Disables all tracing if set to "1" or "true"
 * - XAI_SDK_DISABLE_SENSITIVE_TELEMETRY_ATTRIBUTES: Disables collection of sensitive attributes
 * - OTEL_EXPORTER_OTLP_PROTOCOL: Export protocol ("grpc" or "http/protobuf")
 * - OTEL_EXPORTER_OTLP_ENDPOINT: OTLP endpoint URL
 * - OTEL_EXPORTER_OTLP_HEADERS: Authentication headers
 *
 * @example Console exporter for development:
 * ```php
 * use Displace\XaiSdk\Telemetry\Telemetry;
 *
 * $telemetry = new Telemetry();
 * $telemetry->setupConsoleExporter();
 *
 * // Now all SDK operations will emit traces to the console
 * ```
 * @example OTLP exporter for production:
 * ```php
 * use Displace\XaiSdk\Telemetry\Telemetry;
 *
 * $telemetry = new Telemetry();
 * $telemetry->setupOtlpExporter(
 *     endpoint: 'https://your-observability-platform.com/traces',
 *     headers: ['Authorization' => 'Bearer your-token'],
 * );
 * ```
 */
class Telemetry
{
    public const SDK_NAME = 'xai-sdk-php';

    public const SDK_VERSION = '1.0.0';

    private TracerProviderInterface $provider;

    private bool $isOwned = false;

    /**
     * Creates a new Telemetry instance.
     *
     * @param TracerProviderInterface|null $provider Optional custom TracerProvider.
     *                                               If null, a new provider will be created with xAI SDK metadata.
     */
    public function __construct(?TracerProviderInterface $provider = null)
    {
        if ($provider !== null) {
            $this->provider = $provider;
            $this->isOwned = false;
        } else {
            $this->provider = $this->createDefaultProvider();
            $this->isOwned = true;
        }
    }

    /**
     * Gets the configured TracerProvider.
     *
     * @return TracerProviderInterface
     */
    public function getProvider(): TracerProviderInterface
    {
        return $this->provider;
    }

    /**
     * Gets a tracer with the xAI SDK instrumentation scope.
     *
     * @param string $name Optional tracer name override.
     *
     * @return TracerInterface
     */
    public function getTracer(string $name = self::SDK_NAME): TracerInterface
    {
        if (TelemetryConfig::isTracingDisabled()) {
            return new NoOpTracer();
        }

        return $this->provider->getTracer($name, self::SDK_VERSION);
    }

    /**
     * Sets up a console exporter for debugging and development.
     *
     * Traces are printed to stdout in human-readable format.
     *
     * @throws RuntimeException If OpenTelemetry SDK is not installed.
     *
     * @return $this
     */
    public function setupConsoleExporter(): self
    {
        $this->ensureOpenTelemetryAvailable();

        if (! $this->provider instanceof TracerProvider) {
            throw new RuntimeException(
                'Cannot add span processor to custom provider. Use TracerProvider instance.',
            );
        }

        $exporter = new ConsoleSpanExporter();
        $processor = new SimpleSpanProcessor($exporter);
        $this->provider->addSpanProcessor($processor);

        return $this;
    }

    /**
     * Sets up an OTLP exporter for production monitoring.
     *
     * The protocol is determined by OTEL_EXPORTER_OTLP_PROTOCOL environment variable,
     * defaulting to "http/protobuf".
     *
     * Note: Requires additional OpenTelemetry exporter packages:
     * - For HTTP: open-telemetry/exporter-otlp-http
     * - For gRPC: open-telemetry/exporter-otlp-grpc
     *
     * @param string|null $endpoint OTLP endpoint URL. Falls back to environment variable.
     * @param array<string, string> $headers Authentication headers. Merged with environment.
     *
     * @throws RuntimeException If required packages are not installed.
     *
     * @return $this
     */
    public function setupOtlpExporter(?string $endpoint = null, array $headers = []): self
    {
        $this->ensureOpenTelemetryAvailable();

        if (! $this->provider instanceof TracerProvider) {
            throw new RuntimeException(
                'Cannot add span processor to custom provider. Use TracerProvider instance.',
            );
        }

        $protocol = strtolower(TelemetryConfig::getOtlpProtocol());
        $resolvedEndpoint = $endpoint ?? TelemetryConfig::getOtlpEndpoint();
        $resolvedHeaders = array_merge(TelemetryConfig::getOtlpHeaders(), $headers);

        $exporter = $this->createOtlpExporter($protocol, $resolvedEndpoint, $resolvedHeaders);
        $processor = new BatchSpanProcessor($exporter);
        $this->provider->addSpanProcessor($processor);

        return $this;
    }

    /**
     * Shuts down the tracer provider if owned.
     */
    public function shutdown(): void
    {
        if ($this->isOwned && $this->provider instanceof TracerProvider) {
            $this->provider->shutdown();
        }
    }

    /**
     * Forces flush of pending spans if owned.
     */
    public function forceFlush(): void
    {
        if ($this->isOwned && $this->provider instanceof TracerProvider) {
            $this->provider->forceFlush();
        }
    }

    /**
     * Creates the default TracerProvider with xAI SDK resource info.
     *
     * @return TracerProvider
     */
    private function createDefaultProvider(): TracerProvider
    {
        $this->ensureOpenTelemetryAvailable();

        $resource = ResourceInfoFactory::defaultResource()->merge(
            ResourceInfo::create([
                'service.name' => self::SDK_NAME,
                'service.version' => self::SDK_VERSION,
            ]),
        );

        return new TracerProvider([], null, $resource);
    }

    /**
     * Creates an OTLP exporter based on protocol.
     *
     * @param string $protocol The protocol ('grpc' or 'http/protobuf').
     * @param string|null $endpoint The endpoint URL.
     * @param array<string, string> $headers Authentication headers.
     *
     * @throws RuntimeException If required packages are not installed.
     *
     * @return object The span exporter instance.
     */
    private function createOtlpExporter(string $protocol, ?string $endpoint, array $headers): object
    {
        if ($protocol === 'grpc') {
            if (! class_exists(\OpenTelemetry\Contrib\Otlp\SpanExporter::class)) {
                throw new RuntimeException(
                    "OTLP gRPC exporter not available. Install 'open-telemetry/exporter-otlp' package.",
                );
            }

            return new \OpenTelemetry\Contrib\Otlp\SpanExporter(
                new \OpenTelemetry\Contrib\Grpc\GrpcTransportFactory(),
            );
        }

        if ($protocol === 'http/protobuf') {
            if (! class_exists(\OpenTelemetry\Contrib\Otlp\SpanExporter::class)) {
                throw new RuntimeException(
                    "OTLP HTTP exporter not available. Install 'open-telemetry/exporter-otlp' package.",
                );
            }

            $transportFactory = new \OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory(
                new \GuzzleHttp\Client(),
                new \GuzzleHttp\Psr7\HttpFactory(),
                new \GuzzleHttp\Psr7\HttpFactory(),
            );

            $transport = $transportFactory->create(
                $endpoint ?? 'http://localhost:4318/v1/traces',
                'application/x-protobuf',
                $headers,
            );

            return new \OpenTelemetry\Contrib\Otlp\SpanExporter($transport);
        }

        throw new InvalidArgumentException(
            "Unsupported OTLP protocol: {$protocol}. Valid options: 'grpc', 'http/protobuf'.",
        );
    }

    /**
     * Ensures OpenTelemetry SDK is available.
     *
     * @throws RuntimeException If OpenTelemetry SDK is not installed.
     */
    private function ensureOpenTelemetryAvailable(): void
    {
        if (! interface_exists(TracerProviderInterface::class)) {
            throw new RuntimeException(
                "OpenTelemetry SDK not available. Install 'open-telemetry/sdk' package.",
            );
        }
    }
}
