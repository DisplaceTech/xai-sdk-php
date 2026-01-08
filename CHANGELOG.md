# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-01-08

### Added

#### Core Features
- **XaiClient**: Main client entry point with API key authentication
- **Chat Completions**: Full support for multi-turn conversations
  - Stateful chat management with `append()` and `sample()` methods
  - System, user, assistant, and tool message types
  - Helper functions: `system()`, `user()`, `assistant()`, `image()`, `toolResult()`
- **Streaming**: Real-time token streaming via Server-Sent Events (SSE)
  - Iterator-based streaming with `stream()` method
  - Chunk-by-chunk processing with accumulated response
- **Image Understanding**: Vision model support
  - URL-based images with detail level control
  - Base64-encoded image support
  - Multiple images per message
- **Image Generation**: Create images from text prompts
  - Single image generation with `generate()`
  - Batch generation with `generateBatch()`
  - URL and base64 response formats

#### Advanced Features
- **Function Calling**: Tool/function definitions for model invocation
  - `Tool` class for defining callable functions
  - JSON Schema parameter definitions
  - Tool call parsing and result handling
- **Structured Outputs**: JSON schema-based response formatting
  - Define expected response structure
  - Type-safe JSON parsing
- **Reasoning Models**: Support for reasoning-focused models
  - Configurable effort levels (`low`, `high`)
  - Reasoning content extraction
  - Reasoning token tracking
- **Server-Side Tools**: xAI-hosted tool execution
  - Web search with domain filtering
  - X (Twitter) search with date ranges
  - Code execution
  - Collections search (RAG)
  - MCP server integration
- **Collections API**: Document management for RAG
  - Create, update, delete collections
  - Upload and manage documents
  - Hybrid, semantic, and keyword search
  - Wait for indexing support

#### Infrastructure
- **HTTP Client**: PSR-18 compatible with Guzzle default
  - Configurable timeouts
  - Custom base URL support
- **Telemetry**: Optional OpenTelemetry integration
  - Console exporter for development
  - OTLP exporter for production
  - Custom TracerProvider support
  - Sensitive attribute filtering
- **Exception Hierarchy**: Typed exceptions for error handling
  - `XaiException`: Base exception
  - `AuthenticationException`: 401 errors
  - `RateLimitException`: 429 errors with retry-after
  - `BadRequestException`: 400 errors
  - `ApiException`: General API errors
  - `ServerException`: 5xx errors
  - `StreamException`: Streaming errors

#### Configuration
- Environment variable support (`XAI_API_KEY`)
- Client configuration (API key, base URL, timeout)
- Chat configuration (model, temperature, max tokens, top-p, penalties)
- Stream configuration options

#### Developer Experience
- PHP 8.4+ with strict types throughout
- Readonly properties for immutability
- Comprehensive PHPDoc documentation
- 10 example files covering all features:
  - `chat.php` - Basic multi-turn chat
  - `streaming.php` - Real-time streaming
  - `image_understanding.php` - Vision models
  - `image_generation.php` - Image creation
  - `function_calling.php` - Tool usage
  - `structured_outputs.php` - JSON schemas
  - `reasoning.php` - Reasoning models
  - `server_side_tools.php` - Web/X search, code execution
  - `collections.php` - RAG workflow
  - `telemetry.php` - OpenTelemetry setup

#### Quality Assurance
- PHPUnit 11 test suite
- PHPStan level 8 static analysis
- PHP CS Fixer with PSR-12 rules
- GitHub Actions CI/CD pipeline
- PHP 8.4+ version matrix

### Notes

This is the initial release of the xAI PHP SDK, providing full feature parity with the official Python SDK (synchronous client). The SDK is designed for production use with comprehensive error handling, type safety, and observability support.

#### Python SDK Parity

The PHP SDK maintains API compatibility with the Python SDK:
- Same method names and signatures (adapted to PHP conventions)
- Same response structures and field names
- Same streaming interface patterns
- Same configuration options
- Same example coverage

#### PHP-Specific Adaptations

- Synchronous-only in v1.0 (async planned for v2.0)
- REST API with JSON (Python uses gRPC)
- PHP Iterator pattern for streaming
- PSR-18 HTTP client compatibility

[Unreleased]: https://github.com/DisplaceTech/xai-sdk-php/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/DisplaceTech/xai-sdk-php/releases/tag/v1.0.0
