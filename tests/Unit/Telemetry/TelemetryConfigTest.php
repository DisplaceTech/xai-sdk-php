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

use Displace\XaiSdk\Telemetry\TelemetryConfig;
use PHPUnit\Framework\TestCase;

final class TelemetryConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        // Store original environment values
        $this->originalEnv = [
            TelemetryConfig::ENV_DISABLE_TRACING => getenv(TelemetryConfig::ENV_DISABLE_TRACING),
            TelemetryConfig::ENV_DISABLE_SENSITIVE_ATTRIBUTES => getenv(TelemetryConfig::ENV_DISABLE_SENSITIVE_ATTRIBUTES),
            TelemetryConfig::ENV_OTLP_PROTOCOL => getenv(TelemetryConfig::ENV_OTLP_PROTOCOL),
            TelemetryConfig::ENV_OTLP_ENDPOINT => getenv(TelemetryConfig::ENV_OTLP_ENDPOINT),
            TelemetryConfig::ENV_OTLP_HEADERS => getenv(TelemetryConfig::ENV_OTLP_HEADERS),
        ];
    }

    protected function tearDown(): void
    {
        // Restore original environment values
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("{$key}={$value}");
            }
        }
    }

    public function test_is_tracing_disabled_returns_false_by_default(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_TRACING);

        $this->assertFalse(TelemetryConfig::isTracingDisabled());
    }

    public function test_is_tracing_disabled_returns_true_when_set_to_1(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_TRACING . '=1');

        $this->assertTrue(TelemetryConfig::isTracingDisabled());
    }

    public function test_is_tracing_disabled_returns_true_when_set_to_true(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_TRACING . '=true');

        $this->assertTrue(TelemetryConfig::isTracingDisabled());
    }

    public function test_is_tracing_disabled_returns_true_case_insensitive(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_TRACING . '=TRUE');

        $this->assertTrue(TelemetryConfig::isTracingDisabled());
    }

    public function test_is_tracing_disabled_returns_false_for_other_values(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_TRACING . '=yes');

        $this->assertFalse(TelemetryConfig::isTracingDisabled());
    }

    public function test_should_disable_sensitive_attributes_returns_false_by_default(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_SENSITIVE_ATTRIBUTES);

        $this->assertFalse(TelemetryConfig::shouldDisableSensitiveAttributes());
    }

    public function test_should_disable_sensitive_attributes_returns_true_when_set(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_SENSITIVE_ATTRIBUTES . '=1');

        $this->assertTrue(TelemetryConfig::shouldDisableSensitiveAttributes());
    }

    public function test_should_disable_sensitive_attributes_returns_true_for_true_string(): void
    {
        putenv(TelemetryConfig::ENV_DISABLE_SENSITIVE_ATTRIBUTES . '=true');

        $this->assertTrue(TelemetryConfig::shouldDisableSensitiveAttributes());
    }

    public function test_get_otlp_protocol_returns_http_protobuf_by_default(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_PROTOCOL);

        $this->assertSame('http/protobuf', TelemetryConfig::getOtlpProtocol());
    }

    public function test_get_otlp_protocol_returns_configured_value(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_PROTOCOL . '=grpc');

        $this->assertSame('grpc', TelemetryConfig::getOtlpProtocol());
    }

    public function test_get_otlp_endpoint_returns_null_by_default(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_ENDPOINT);

        $this->assertNull(TelemetryConfig::getOtlpEndpoint());
    }

    public function test_get_otlp_endpoint_returns_configured_value(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_ENDPOINT . '=https://otel.example.com/v1/traces');

        $this->assertSame('https://otel.example.com/v1/traces', TelemetryConfig::getOtlpEndpoint());
    }

    public function test_get_otlp_headers_returns_empty_array_by_default(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_HEADERS);

        $this->assertSame([], TelemetryConfig::getOtlpHeaders());
    }

    public function test_get_otlp_headers_parses_single_header(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_HEADERS . '=Authorization=Bearer token123');

        $headers = TelemetryConfig::getOtlpHeaders();

        $this->assertSame(['Authorization' => 'Bearer token123'], $headers);
    }

    public function test_get_otlp_headers_parses_multiple_headers(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_HEADERS . '=Authorization=Bearer token,X-Custom=value');

        $headers = TelemetryConfig::getOtlpHeaders();

        $this->assertSame([
            'Authorization' => 'Bearer token',
            'X-Custom' => 'value',
        ], $headers);
    }

    public function test_get_otlp_headers_trims_whitespace(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_HEADERS . '= Key = Value ');

        $headers = TelemetryConfig::getOtlpHeaders();

        $this->assertSame(['Key' => 'Value'], $headers);
    }

    public function test_get_otlp_headers_handles_value_with_equals_sign(): void
    {
        putenv(TelemetryConfig::ENV_OTLP_HEADERS . '=Authorization=Basic dXNlcjpwYXNz');

        $headers = TelemetryConfig::getOtlpHeaders();

        $this->assertSame(['Authorization' => 'Basic dXNlcjpwYXNz'], $headers);
    }

    public function test_env_constants_have_correct_values(): void
    {
        $this->assertSame('XAI_SDK_DISABLE_TRACING', TelemetryConfig::ENV_DISABLE_TRACING);
        $this->assertSame('XAI_SDK_DISABLE_SENSITIVE_TELEMETRY_ATTRIBUTES', TelemetryConfig::ENV_DISABLE_SENSITIVE_ATTRIBUTES);
        $this->assertSame('OTEL_EXPORTER_OTLP_PROTOCOL', TelemetryConfig::ENV_OTLP_PROTOCOL);
        $this->assertSame('OTEL_EXPORTER_OTLP_ENDPOINT', TelemetryConfig::ENV_OTLP_ENDPOINT);
        $this->assertSame('OTEL_EXPORTER_OTLP_HEADERS', TelemetryConfig::ENV_OTLP_HEADERS);
    }
}
