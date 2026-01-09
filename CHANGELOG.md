# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-01-08

### Added

#### Embeddings API Support
- **EmbeddingsResource**: New resource for the `/v1/embeddings` endpoint
  - `create()` method supporting single string or batch array input
  - Optional `encodingFormat` and `dimensions` parameters
- **EmbeddingsResponse**: Response class with helper methods
  - `getEmbedding()` for single-input convenience
  - `getEmbeddings()` for all vectors
  - `count()` for number of embeddings
- **Embedding**: Individual embedding with `index`, `embedding` array, and dimensions helper
- **EmbeddingsUsage**: Token usage tracking for embeddings
- **embeddings.php**: Example demonstrating basic usage, batch embeddings, and similarity comparison

#### Deferred Completions
- **DeferredCompletion**: Support for long-running requests with polling
  - Status tracking: PENDING, PROCESSING, COMPLETED, FAILED, CANCELLED
  - Helper methods: `isPending()`, `isCompleted()`, `isFailed()`, `isFinished()`
  - `getResult()` and `getError()` for accessing completion data
- **ChatResource** new methods:
  - `createDeferred()`: Initiate a deferred completion request
  - `retrieveDeferred()`: Poll for completion status
  - `awaitDeferred()`: Convenient wait-with-timeout functionality
- **DeferredCompletionException**: Exception for deferred completion failures

#### Concurrent Request Batching
- **ConcurrentRequests**: Utility for executing multiple API requests
  - `run()`: Execute callbacks and return `RequestResult` array
  - `all()`: Execute all callbacks, throw on any failure
  - `settled()`: Execute all and return only successful results
  - Configurable concurrency limits
- **RequestResult**: Result wrapper with functional programming patterns
  - `isSuccess()`, `isFailure()`, `getValue()`, `getError()`
  - `map()`, `flatMap()`, `getValueOrDefault()`
- **BatchException**: Exception with detailed failure information

#### Fluent Request Builder
- **ChatRequestBuilder**: Builder pattern for complex chat requests
  - All chat parameters: `model()`, `temperature()`, `maxTokens()`, `seed()`, etc.
  - Message helpers: `systemPrompt()`, `userMessage()`, `assistantMessage()`
  - Tool support: `withTool()`, `withTools()`, `toolChoice()`, `parallelToolCalls()`
  - Response formats: `jsonResponse()`, `jsonSchema()`, `responseFormat()`
  - `build()` to get Chat instance, `send()` to execute immediately
  - `reset()` for builder reuse

#### PSR-15 Style Middleware System
- **MiddlewareInterface**: Interface for request/response interception
- **MiddlewareStack**: Stack manager with `push()`, `prepend()`, `remove()`, `handle()`
- **LoggingMiddleware**: Request/response logging with PSR-3 logger
  - Configurable log levels
  - Sensitive header redaction
  - Body truncation options
- **RetryMiddleware**: Retry logic with exponential backoff and jitter
- **CachingMiddleware**: Response caching using ResponseCache
- **HttpClient** integration:
  - `withMiddleware()` for adding middleware
  - `withMiddlewareStack()` for stack replacement
  - `getMiddlewareStack()` accessor

#### Response Caching Layer
- **ResponseCache**: PSR-16 compatible response caching
  - Cache key generation based on request hash
  - TTL configuration per endpoint
  - Skip streaming responses by default
- **CacheConfig**: Configuration class with factory methods
  - `disabled()`: Disable caching entirely
  - `deterministicOnly()`: Only cache requests with seed
  - `metadataOnly()`: Only cache metadata endpoints
  - Options: `cacheableEndpoints`, `requireSeed`, `cacheStreaming`, `endpointTtls`
- **CachedResponse**: Response wrapper with cache metadata

### Changed
- **HttpConfig**: Default `maxRetries` changed from 0 to 3 (Python SDK parity)
  - Added `DEFAULT_MAX_RETRIES` constant
  - Added `withoutRetries()` factory method for disabling retries
- **XaiClient**: Added `embeddings` property for accessing EmbeddingsResource
- **composer.json**: Added `psr/simple-cache` dependency for caching support

### Documentation
- **docs/V2_ROADMAP.md**: Comprehensive v2.0 planning document
  - Async support analysis (ReactPHP recommended, 9-13 weeks LOE)
  - gRPC support analysis (12-15 weeks LOE)
  - Voice Agent API analysis (WebSocket, 12-17 weeks LOE)
  - Recommended implementation order and timeline
- **docs/FRAMEWORK_INTEGRATIONS.md**: Laravel/Symfony package proposals
  - `displace/xai-laravel`: Facade, Service Provider, Queue integration
  - `displace/xai-symfony`: Bundle, Autowiring, Messenger integration

## [1.1.0] - 2026-01-08

### Added

#### Responses API Support
- **ResponsesResource**: New resource for the `/v1/responses` endpoint
  - Required for server-side tools (x_search, web_search, code_execution)
  - Uses `input` instead of `messages` for request format
  - Properly formats tools with `type` at root level
- **ResponsesResponse**: Response parser for the responses endpoint format
  - Parses `output` array structure (vs `choices` in chat completions)
  - Extracts text from `output_text` content blocks
  - Handles `custom_tool_call` entries for tool execution tracking
- **OutputItem**: Represents items in the responses output array
  - Supports `message` and `custom_tool_call` types
  - Provides `getText()` helper for extracting message content
- **ContentBlock**: Represents content blocks within message outputs
  - Supports `output_text` and `refusal` block types
- **toResponsesArray()**: New method on all ServerSideTool implementations
  - XSearch, WebSearch, CodeExecution, CollectionsSearch, McpTool
  - Formats tools correctly for /v1/responses endpoint

#### New Example
- **search.php**: Comprehensive example demonstrating X search and web search
  - Shows single tool usage with x_search
  - Shows single tool usage with web_search
  - Shows combined multi-tool search scenarios
  - Demonstrates tool call tracking and usage statistics

### Fixed
- **Server-side tools now work correctly** - Previously, using `ServerSideTools::xSearch()` or `ServerSideTools::webSearch()` would fail with "unknown variant" error because the SDK was sending requests to `/chat/completions` which only supports `type: function` tools. Server-side tools now route through the new `/v1/responses` endpoint. (Fixes #6)
- **Response format parsing for server-side tools** - The `/v1/responses` endpoint returns a different format with `output` array containing `output_text` blocks. The SDK now correctly parses this format. (Fixes #7)

### Changed
- **XaiClient**: Added `responses` property for accessing ResponsesResource
- **ServerSideTool interface**: Added `toResponsesArray()` method requirement
- **README.md**: Added "Search (X & Web)" feature to features table

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

[Unreleased]: https://github.com/DisplaceTech/xai-sdk-php/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/DisplaceTech/xai-sdk-php/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/DisplaceTech/xai-sdk-php/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/DisplaceTech/xai-sdk-php/releases/tag/v1.0.0
