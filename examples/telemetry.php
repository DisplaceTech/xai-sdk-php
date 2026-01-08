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
 *
 * Telemetry Example
 * =================
 *
 * This example demonstrates how to integrate OpenTelemetry tracing with the xAI SDK.
 * Telemetry helps monitor and debug SDK operations in production environments.
 *
 * Usage:
 *   php examples/telemetry.php                    # No telemetry (baseline)
 *   php examples/telemetry.php --console          # Console export (debugging)
 *   php examples/telemetry.php --otlp             # OTLP export (production)
 *   php examples/telemetry.php --custom           # Custom TracerProvider
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 *   - For console/OTLP: composer require open-telemetry/sdk
 *   - For OTLP: composer require open-telemetry/exporter-otlp
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\Telemetry\Telemetry;
use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['console', 'otlp', 'custom', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Telemetry Example

        Usage: php examples/telemetry.php [OPTIONS]

        Options:
          --console     Export traces to console (for debugging)
          --otlp        Export traces to OTLP backend (for production)
          --custom      Use custom TracerProvider example
          --help        Show this help message

        Environment:
          XAI_API_KEY                     Your xAI API key (required)
          XAI_SDK_DISABLE_TRACING         Set to "1" to disable tracing
          OTEL_EXPORTER_OTLP_ENDPOINT     OTLP endpoint URL (for --otlp)
          OTEL_EXPORTER_OTLP_HEADERS      Authentication headers (for --otlp)
          OTEL_EXPORTER_OTLP_PROTOCOL     Export protocol: grpc or http/protobuf

        HELP;
    exit(0);
}

// Create the client
try {
    $client = new XaiClient();
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Please set the XAI_API_KEY environment variable.\n";
    exit(1);
}

echo "xAI Telemetry Example\n";
echo "=====================\n\n";

/**
 * Example without telemetry - no traces will be collected.
 */
function noTelemetryExample(XaiClient $client): void
{
    echo "Mode: No telemetry (baseline)\n";
    echo "-----------------------------\n\n";

    $chat = $client->chat->create(model: 'grok-3');
    $chat->append(user('Hello, how are you?'));

    $response = $chat->sample();
    echo "Response: {$response->getContent()}\n\n";
    echo "Tokens used: {$response->usage->totalTokens}\n";
}

/**
 * Export traces to console for debugging and development.
 */
function consoleExportExample(XaiClient $client): void
{
    echo "Mode: Console export (debugging)\n";
    echo "--------------------------------\n\n";

    try {
        $telemetry = new Telemetry();
        $telemetry->setupConsoleExporter();
        echo "Console exporter configured. Traces will be printed to stdout.\n\n";
    } catch (RuntimeException $e) {
        echo "Warning: {$e->getMessage()}\n";
        echo "Continuing without telemetry...\n\n";
    }

    $chat = $client->chat->create(model: 'grok-3');
    $chat->append(user('Hello, how are you?'));

    echo "Sending request (traces will appear below)...\n\n";

    $response = $chat->sample();
    echo "\nResponse: {$response->getContent()}\n\n";
    echo "Tokens used: {$response->usage->totalTokens}\n";

    // Force flush to ensure traces are exported
    if (isset($telemetry)) {
        $telemetry->forceFlush();
    }
}

/**
 * Export traces to an OTLP-compatible backend.
 */
function otlpExportExample(XaiClient $client): void
{
    echo "Mode: OTLP export (production)\n";
    echo "------------------------------\n\n";

    $endpoint = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: null;

    if ($endpoint === null) {
        echo "Warning: OTEL_EXPORTER_OTLP_ENDPOINT not set.\n";
        echo "Using default: http://localhost:4318/v1/traces\n\n";
    } else {
        echo "OTLP endpoint: {$endpoint}\n\n";
    }

    try {
        $telemetry = new Telemetry();
        $telemetry->setupOtlpExporter();
        echo "OTLP exporter configured.\n\n";
    } catch (RuntimeException $e) {
        echo "Error: {$e->getMessage()}\n";
        echo "\nTo use OTLP export, install:\n";
        echo "  composer require open-telemetry/sdk open-telemetry/exporter-otlp\n\n";
        echo "Continuing without telemetry...\n\n";
    }

    $chat = $client->chat->create(model: 'grok-3');
    $chat->append(user('Hello, how are you?'));

    echo "Sending request...\n\n";

    $response = $chat->sample();
    echo "Response: {$response->getContent()}\n\n";
    echo "Tokens used: {$response->usage->totalTokens}\n";

    // Force flush to ensure traces are exported
    if (isset($telemetry)) {
        $telemetry->forceFlush();
        echo "\nTraces exported to OTLP backend.\n";
    }
}

/**
 * Example using a custom TracerProvider.
 * Useful for applications that already have OpenTelemetry configured.
 */
function customTracerProviderExample(XaiClient $client): void
{
    echo "Mode: Custom TracerProvider\n";
    echo "---------------------------\n\n";

    // Check if OpenTelemetry SDK is available
    if (! class_exists(OpenTelemetry\SDK\Trace\TracerProvider::class)) {
        echo "Error: OpenTelemetry SDK not installed.\n";
        echo "Install with: composer require open-telemetry/sdk\n\n";
        noTelemetryExample($client);

        return;
    }

    try {
        // Create a custom resource with service information
        $resource = OpenTelemetry\SDK\Resource\ResourceInfoFactory::defaultResource()->merge(
            OpenTelemetry\SDK\Resource\ResourceInfo::create([
                'service.name' => 'my-application',
                'service.version' => '1.0.0',
                'deployment.environment' => 'development',
            ]),
        );

        // Create a custom TracerProvider
        $tracerProvider = new OpenTelemetry\SDK\Trace\TracerProvider([], null, $resource);

        // Set as global tracer provider
        OpenTelemetry\API\Trace\TracerProviderInterface::setGlobalTracerProvider($tracerProvider);

        // Create telemetry with custom provider
        $telemetry = new Telemetry($tracerProvider);
        $telemetry->setupConsoleExporter();

        echo "Custom TracerProvider configured with service metadata.\n\n";
    } catch (Throwable $e) {
        echo "Warning: Could not set up custom TracerProvider: {$e->getMessage()}\n";
        echo "Continuing without telemetry...\n\n";
    }

    $chat = $client->chat->create(model: 'grok-3');
    $chat->append(user('Hello, how are you?'));

    echo "Sending request...\n\n";

    $response = $chat->sample();
    echo "\nResponse: {$response->getContent()}\n\n";
    echo "Tokens used: {$response->usage->totalTokens}\n";

    if (isset($telemetry)) {
        $telemetry->forceFlush();
    }
}

// Run the appropriate example
try {
    if (isset($options['console'])) {
        consoleExportExample($client);
    } elseif (isset($options['otlp'])) {
        otlpExportExample($client);
    } elseif (isset($options['custom'])) {
        customTracerProviderExample($client);
    } else {
        noTelemetryExample($client);
    }
} catch (Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";

    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}
