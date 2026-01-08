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

/**
 * Configuration for telemetry.
 *
 * This class provides static methods for checking telemetry configuration
 * based on environment variables.
 */
final class TelemetryConfig
{
    /**
     * Environment variable to disable all tracing.
     */
    public const ENV_DISABLE_TRACING = 'XAI_SDK_DISABLE_TRACING';

    /**
     * Environment variable to disable sensitive attributes in telemetry.
     */
    public const ENV_DISABLE_SENSITIVE_ATTRIBUTES = 'XAI_SDK_DISABLE_SENSITIVE_TELEMETRY_ATTRIBUTES';

    /**
     * Environment variable for OTLP protocol.
     */
    public const ENV_OTLP_PROTOCOL = 'OTEL_EXPORTER_OTLP_PROTOCOL';

    /**
     * Environment variable for OTLP endpoint.
     */
    public const ENV_OTLP_ENDPOINT = 'OTEL_EXPORTER_OTLP_ENDPOINT';

    /**
     * Environment variable for OTLP headers.
     */
    public const ENV_OTLP_HEADERS = 'OTEL_EXPORTER_OTLP_HEADERS';

    /**
     * Checks if tracing is disabled.
     *
     * @return bool True if tracing is disabled via environment variable.
     */
    public static function isTracingDisabled(): bool
    {
        $envValue = getenv(self::ENV_DISABLE_TRACING);
        $value = $envValue !== false ? $envValue : '';

        return in_array(strtolower($value), ['1', 'true'], true);
    }

    /**
     * Checks if sensitive attributes should be disabled.
     *
     * Sensitive attributes include user inputs, AI responses, and prompts.
     *
     * @return bool True if sensitive attributes should be excluded from traces.
     */
    public static function shouldDisableSensitiveAttributes(): bool
    {
        $envValue = getenv(self::ENV_DISABLE_SENSITIVE_ATTRIBUTES);
        $value = $envValue !== false ? $envValue : '';

        return in_array(strtolower($value), ['1', 'true'], true);
    }

    /**
     * Gets the configured OTLP protocol.
     *
     * @return string The OTLP protocol ('grpc' or 'http/protobuf').
     */
    public static function getOtlpProtocol(): string
    {
        $envValue = getenv(self::ENV_OTLP_PROTOCOL);

        return $envValue !== false && $envValue !== '' ? $envValue : 'http/protobuf';
    }

    /**
     * Gets the configured OTLP endpoint.
     *
     * @return string|null The OTLP endpoint URL, or null if not configured.
     */
    public static function getOtlpEndpoint(): ?string
    {
        $endpoint = getenv(self::ENV_OTLP_ENDPOINT);

        return $endpoint !== false ? $endpoint : null;
    }

    /**
     * Parses the OTLP headers from environment variable.
     *
     * Headers are expected in format: "key1=value1,key2=value2"
     *
     * @return array<string, string> The parsed headers.
     */
    public static function getOtlpHeaders(): array
    {
        $headersStr = getenv(self::ENV_OTLP_HEADERS);

        if ($headersStr === false || $headersStr === '') {
            return [];
        }

        $headers = [];
        $pairs = explode(',', $headersStr);

        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);

            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $headers;
    }
}
