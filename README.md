<div align="center">
  <img src="https://avatars.githubusercontent.com/u/130314967?s=200&v=4" alt="xAI Logo" width="100" />
  <h1>xAI PHP SDK</h1>
  <p>The official PHP SDK for xAI's APIs</p>
  <a href="https://packagist.org/packages/displace/xai-sdk-php">
    <img src="https://img.shields.io/packagist/v/displace/xai-sdk-php" alt="Packagist Version" />
  </a>
  <a href="https://packagist.org/packages/displace/xai-sdk-php">
    <img src="https://img.shields.io/packagist/l/displace/xai-sdk-php" alt="License" />
  </a>
  <a href="https://packagist.org/packages/displace/xai-sdk-php">
    <img src="https://img.shields.io/packagist/php-v/displace/xai-sdk-php" alt="PHP Version" />
  </a>
</div>

<br>

The xAI PHP SDK is a modern PHP library for interacting with xAI's APIs. Built for PHP 8.4 and above, it provides full parity with the official Python SDK, offering an intuitive and developer-friendly interface.

Whether you're generating text, images, or structured outputs, the xAI PHP SDK is designed to be robust, type-safe, and production-ready.

## Documentation

Comprehensive API documentation is available at [docs.x.ai](https://docs.x.ai). Explore detailed guides, API references, and tutorials to get the most out of the xAI SDK.

## Installation

Install via Composer:

```bash
composer require displace/xai-sdk-php
```

### Requirements

- PHP 8.4 or higher
- `ext-json` extension
- `ext-mbstring` extension

## Quick Start

### Client Instantiation

The SDK looks for the `XAI_API_KEY` environment variable by default:

```php
<?php

use Displace\XaiSdk\XaiClient;

// Uses XAI_API_KEY environment variable
$client = new XaiClient();

// Or explicitly pass the API key
$client = new XaiClient(apiKey: 'your-api-key');
```

### Multi-Turn Chat

The SDK supports multi-turn conversations with a simple `append` method to manage conversation history:

```php
<?php

use Displace\XaiSdk\XaiClient;
use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\user;

$client = new XaiClient();

$chat = $client->chat->create(
    model: 'grok-3',
    messages: [
        system('You are a helpful assistant.'),
    ]
);

// Interactive chat loop
while (true) {
    echo "You: ";
    $prompt = trim(fgets(STDIN));

    if (strtolower($prompt) === 'exit') {
        break;
    }

    $chat->append(user($prompt));
    $response = $chat->sample();

    echo "Grok: {$response->getContent()}\n";
    $chat->append($response);
}
```

### Streaming

Stream responses in real-time for interactive applications:

```php
<?php

use Displace\XaiSdk\XaiClient;
use function Displace\XaiSdk\Chat\user;

$client = new XaiClient();
$chat = $client->chat->create(model: 'grok-3');

$chat->append(user('Explain quantum computing in simple terms.'));

echo "Grok: ";
foreach ($chat->stream() as [$response, $chunk]) {
    echo $chunk->content;
}
echo "\n";

// Append the final response to continue the conversation
$chat->append($response);
```

### Image Understanding

Analyze images with vision models:

```php
<?php

use Displace\XaiSdk\XaiClient;
use function Displace\XaiSdk\Chat\image;
use function Displace\XaiSdk\Chat\user;

$client = new XaiClient();
$chat = $client->chat->create(model: 'grok-2-vision');

$chat->append(
    user(
        'What do these images have in common?',
        image('https://example.com/image1.jpg', 'high'),
        image('https://example.com/image2.jpg', 'high')
    )
);

$response = $chat->sample();
echo "Grok: {$response->getContent()}\n";
```

## Features

The xAI PHP SDK provides comprehensive feature coverage:

| Feature | Description | Example |
|---------|-------------|---------|
| **Chat Completions** | Multi-turn conversations with stateful management | [chat.php](/examples/chat.php) |
| **Streaming** | Real-time token streaming for responsive UIs | [streaming.php](/examples/streaming.php) |
| **Image Understanding** | Vision model support with URLs and base64 | [image_understanding.php](/examples/image_understanding.php) |
| **Image Generation** | Generate images from text prompts | [image_generation.php](/examples/image_generation.php) |
| **Function Calling** | Define tools the model can invoke | [function_calling.php](/examples/function_calling.php) |
| **Structured Outputs** | Type-safe JSON responses with schemas | [structured_outputs.php](/examples/structured_outputs.php) |
| **Reasoning Models** | Extended thinking with configurable effort | [reasoning.php](/examples/reasoning.php) |
| **Server-Side Tools** | Web search, X search, code execution | [server_side_tools.php](/examples/server_side_tools.php) |
| **Collections (RAG)** | Document management and semantic search | [collections.php](/examples/collections.php) |
| **Telemetry** | OpenTelemetry tracing integration | [telemetry.php](/examples/telemetry.php) |

## Configuration

### Client Options

```php
<?php

use Displace\XaiSdk\XaiClient;

$client = new XaiClient(
    apiKey: 'your-api-key',           // API key (or use XAI_API_KEY env var)
    baseUrl: 'https://api.x.ai/v1',   // API base URL
    timeout: 120,                      // Request timeout in seconds
);
```

### Chat Configuration

```php
<?php

$chat = $client->chat->create(
    model: 'grok-3',                   // Model to use
    messages: [...],                   // Initial messages
    temperature: 0.7,                  // Sampling temperature (0.0-2.0)
    maxTokens: 1024,                   // Maximum tokens in response
    topP: 0.9,                         // Nucleus sampling parameter
    frequencyPenalty: 0.0,             // Frequency penalty (-2.0-2.0)
    presencePenalty: 0.0,              // Presence penalty (-2.0-2.0)
    tools: [...],                      // Tool/function definitions
    responseFormat: [...],             // Structured output schema
    reasoningEffort: 'high',           // For reasoning models: 'low' or 'high'
);
```

### Environment Variables

| Variable | Description |
|----------|-------------|
| `XAI_API_KEY` | Your xAI API key |
| `XAI_SDK_DISABLE_TRACING` | Set to `1` to disable telemetry tracing |
| `XAI_SDK_DISABLE_SENSITIVE_TELEMETRY_ATTRIBUTES` | Set to `1` to exclude prompts/responses from traces |
| `OTEL_EXPORTER_OTLP_ENDPOINT` | OTLP endpoint URL for telemetry export |
| `OTEL_EXPORTER_OTLP_HEADERS` | Authentication headers for OTLP |
| `OTEL_EXPORTER_OTLP_PROTOCOL` | Protocol: `grpc` or `http/protobuf` |

## Telemetry & Observability

The SDK includes optional OpenTelemetry integration for monitoring and debugging:

### Console Export (Development)

```php
<?php

use Displace\XaiSdk\Telemetry\Telemetry;
use Displace\XaiSdk\XaiClient;

$telemetry = new Telemetry();
$telemetry->setupConsoleExporter();

$client = new XaiClient();
// All API calls will now emit traces to the console
```

### OTLP Export (Production)

```php
<?php

use Displace\XaiSdk\Telemetry\Telemetry;

$telemetry = new Telemetry();
$telemetry->setupOtlpExporter(
    endpoint: 'https://your-observability-platform.com/traces',
    headers: ['Authorization' => 'Bearer your-token'],
);
```

### Installation Requirements

```bash
# For telemetry support
composer require open-telemetry/sdk

# For OTLP export
composer require open-telemetry/exporter-otlp
```

## Error Handling

The SDK provides typed exceptions for different error scenarios:

```php
<?php

use Displace\XaiSdk\Exceptions\AuthenticationException;
use Displace\XaiSdk\Exceptions\RateLimitException;
use Displace\XaiSdk\Exceptions\ApiException;
use Displace\XaiSdk\Exceptions\XaiException;

try {
    $response = $chat->sample();
} catch (AuthenticationException $e) {
    // Invalid or missing API key
    echo "Authentication failed: {$e->getMessage()}\n";
} catch (RateLimitException $e) {
    // Rate limit exceeded - implement backoff
    echo "Rate limited. Retry after: {$e->getRetryAfter()} seconds\n";
} catch (ApiException $e) {
    // API returned an error
    echo "API error ({$e->getHttpStatusCode()}): {$e->getMessage()}\n";
} catch (XaiException $e) {
    // Base exception for all SDK errors
    echo "Error: {$e->getMessage()}\n";
}
```

| Exception | HTTP Status | Description |
|-----------|-------------|-------------|
| `AuthenticationException` | 401 | Invalid or missing API key |
| `RateLimitException` | 429 | Rate limit exceeded |
| `BadRequestException` | 400 | Invalid request parameters |
| `ApiException` | Various | General API errors |
| `XaiException` | - | Base exception class |

## Available Models

| Model | Description | Use Case |
|-------|-------------|----------|
| `grok-3` | Latest Grok model | General chat, complex tasks |
| `grok-3-mini` | Reasoning model | Math, logic, step-by-step problems |
| `grok-2-vision` | Vision model | Image understanding and analysis |
| `grok-2-image` | Image generation | Creating images from prompts |

Use the Models API to retrieve available models:

```php
<?php

$models = $client->models->list();

foreach ($models as $model) {
    echo "{$model->id}: {$model->description}\n";
}
```

## Development

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run only unit tests
composer test:unit
```

### Code Quality

```bash
# Check code style (PSR-12)
composer cs:check

# Fix code style issues
composer cs:fix

# Run static analysis (PHPStan level 8)
composer stan

# Run all quality checks
composer qa
```

## Comparison with Python SDK

The PHP SDK maintains API parity with the official Python SDK:

| Feature | Python SDK | PHP SDK |
|---------|------------|---------|
| Client | `Client()` / `AsyncClient()` | `XaiClient()` |
| Chat | `client.chat.create()` | `$client->chat->create()` |
| Streaming | `chat.stream()` | `$chat->stream()` |
| Messages | `user()`, `system()`, `assistant()` | `user()`, `system()`, `assistant()` |
| Images | `image(url)` | `image($url)` |
| Tools | `tool()` | `new Tool()` |
| Telemetry | `Telemetry()` | `new Telemetry()` |

### PHP-Specific Adaptations

1. **Synchronous Only (v1.0)** - Async support planned for v2.0
2. **REST API** - Uses REST with JSON instead of gRPC
3. **Iterator Pattern** - Uses PHP iterators for streaming
4. **Strict Types** - Leverages PHP 8.4+ readonly properties

## License

The xAI PHP SDK is distributed under the [Apache License 2.0](LICENSE).

```
Copyright 2026 Displace Technologies, LLC

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```

## Attribution

This SDK was inspired by and maintains API compatibility with [X.AI LLC's official Python SDK](https://pypi.org/project/xai-sdk/) for the xAI API. We thank X.AI LLC for their excellent work on the original SDK design.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Support

- **Issues**: [GitHub Issues](https://github.com/DisplaceTech/xai-sdk-php/issues)
- **Documentation**: [docs.x.ai](https://docs.x.ai)
- **Examples**: [/examples](/examples) directory
