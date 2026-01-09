# xAI PHP SDK v2.0 Roadmap

## Executive Summary

This document outlines the proposed feature roadmap for v2.0 of the xAI PHP SDK. The three major features under consideration are:

1. **Full Async Support** - Native asynchronous operations using ReactPHP or Amp
2. **gRPC Support** - Protocol Buffers-based communication matching the Python SDK architecture
3. **Voice Agent API** - WebSocket-based real-time voice interactions with Grok

These features would bring the PHP SDK closer to full parity with the official xAI Python SDK, which is built on gRPC and supports both synchronous and asynchronous clients.

---

## 1. Full Async Support

### Overview

The current PHP SDK uses synchronous HTTP via Guzzle (PSR-18). Adding async support would enable concurrent API requests, improve throughput in high-volume applications, and enable better integration with event-driven architectures.

### Technical Approach

#### Option A: ReactPHP (Recommended)

ReactPHP is a mature, low-level library for event-driven programming in PHP. It provides:

- **Event Loop**: Core component that handles I/O multiplexing
- **HTTP Client**: Async HTTP client supporting PSR-7 messages
- **Promises**: A+ compliant promises for async workflow management
- **Streams**: Non-blocking stream abstractions

**Pros:**
- Mature ecosystem (10+ years)
- Wide adoption (7.3M+ downloads for Pawl WebSocket client)
- Compatible with Fibers (PHP 8.1+) via `react/async`
- Already partially present in vendor (react/promise, react/stream, react/socket)

**Cons:**
- Requires restructuring application code around event loop
- Cannot easily mix with blocking I/O

#### Option B: Amp

Amp is a concurrent framework using PHP Fibers (8.1+):

**Pros:**
- More modern API using coroutines
- Fibers make async code look synchronous
- Good for new projects targeting PHP 8.1+

**Cons:**
- Smaller ecosystem than ReactPHP
- Requires PHP 8.1+ (current SDK requires 8.4)

### API Design

```php
<?php

namespace Displace\XaiSdk;

use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Asynchronous client for the xAI API.
 *
 * Uses ReactPHP for non-blocking I/O operations.
 */
class AsyncXaiClient
{
    private LoopInterface $loop;
    private AsyncHttpClient $httpClient;

    public function __construct(
        ?string $apiKey = null,
        ?LoopInterface $loop = null,
        string $baseUrl = 'https://api.x.ai/v1',
        array $headers = [],
    ) {
        $this->loop = $loop ?? \React\EventLoop\Loop::get();
        // ... initialization
    }

    /**
     * @property-read AsyncChatResource $chat
     * @property-read AsyncModelsResource $models
     * @property-read AsyncImageResource $image
     */
    public function __get(string $name): mixed
    {
        // Return async-enabled resources
    }
}

/**
 * Async chat resource.
 */
class AsyncChatResource
{
    /**
     * Creates a chat completion asynchronously.
     *
     * @return PromiseInterface<ChatCompletionResponse>
     */
    public function create(
        string $model,
        array $messages,
        array $options = [],
    ): PromiseInterface {
        return $this->httpClient->postAsync('/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            ...$options,
        ])->then(fn($response) => ChatCompletionResponse::fromArray($response));
    }

    /**
     * Creates multiple chat completions concurrently.
     *
     * @param array<array{model: string, messages: array, ...}> $requests
     * @return PromiseInterface<array<ChatCompletionResponse>>
     */
    public function batch(array $requests): PromiseInterface
    {
        $promises = array_map(
            fn($req) => $this->create($req['model'], $req['messages'], $req),
            $requests
        );

        return \React\Promise\all($promises);
    }
}
```

### Example Usage

```php
<?php

use Displace\XaiSdk\AsyncXaiClient;
use function React\Async\await;

// Create async client
$client = new AsyncXaiClient();

// Option 1: Using promises directly
$client->chat->create('grok-3', [
    ['role' => 'user', 'content' => 'Hello!']
])->then(function ($response) {
    echo $response->getContent();
});

// Run the event loop
$client->run();

// Option 2: Using await (PHP 8.1+ with Fibers)
$response = await($client->chat->create('grok-3', [
    ['role' => 'user', 'content' => 'Hello!']
]));
echo $response->getContent();

// Option 3: Concurrent requests
$responses = await($client->chat->batch([
    ['model' => 'grok-3', 'messages' => [['role' => 'user', 'content' => 'Question 1']]],
    ['model' => 'grok-3', 'messages' => [['role' => 'user', 'content' => 'Question 2']]],
    ['model' => 'grok-3', 'messages' => [['role' => 'user', 'content' => 'Question 3']]],
]));

foreach ($responses as $response) {
    echo $response->getContent() . "\n";
}
```

### Compatibility with Sync Client

The SDK should maintain backward compatibility:

```php
<?php

// Existing synchronous API remains unchanged
$syncClient = new XaiClient();
$response = $syncClient->chat->completions->create(...);

// New async client is additive
$asyncClient = new AsyncXaiClient();
$promise = $asyncClient->chat->completions->create(...);
```

### Dependencies Required

```json
{
    "require": {
        "react/http": "^1.9",
        "react/async": "^4.0",
        "react/promise": "^3.0",
        "react/event-loop": "^1.4"
    }
}
```

Alternatively, as optional dependencies:

```json
{
    "suggest": {
        "react/http": "Required for async client support (^1.9)",
        "react/async": "Required for await() helper (^4.0)"
    }
}
```

### Level of Effort

| Aspect | Estimate |
|--------|----------|
| **Research & Design** | 1-2 weeks |
| **Core AsyncHttpClient** | 2-3 weeks |
| **Async Resource Classes** | 2-3 weeks |
| **Streaming Support** | 1-2 weeks |
| **Testing** | 2 weeks |
| **Documentation** | 1 week |
| **Total** | **9-13 weeks** |

**Complexity**: Medium-High

The main complexity lies in:
- Ensuring proper error propagation through promises
- Managing connection pooling and timeouts
- Testing async code paths
- Maintaining feature parity with sync client

### Risks and Considerations

1. **Ecosystem Fragmentation**: Users must choose between ReactPHP and Amp ecosystems
2. **Learning Curve**: Async PHP is less familiar to many developers
3. **Debugging Complexity**: Async stack traces are harder to follow
4. **Blocking Code**: Any blocking I/O (file, database) must be refactored
5. **Server Compatibility**: Traditional PHP-FPM doesn't benefit; requires Swoole, RoadRunner, or ReactPHP server

---

## 2. gRPC Support

### Overview

The official xAI Python SDK is built entirely on gRPC, providing efficient binary serialization via Protocol Buffers. Adding gRPC support to the PHP SDK would enable:

- Faster serialization/deserialization
- Bi-directional streaming
- Strong typing via protobuf
- Direct compatibility with xAI's protobuf definitions

### Benefits of gRPC vs REST

| Feature | REST (Current) | gRPC |
|---------|---------------|------|
| Serialization | JSON (text) | Protocol Buffers (binary) |
| Schema | OpenAPI (optional) | Required .proto files |
| Streaming | SSE/chunked | Native bi-directional |
| Code Gen | Manual | Automatic from .proto |
| Payload Size | Larger | 30-50% smaller |
| Latency | Higher | Lower |
| Browser Support | Full | Via gRPC-Web proxy |

### Python SDK gRPC Architecture

The xAI Python SDK uses:

- `xai_sdk.Client` and `xai_sdk.AsyncClient` both use gRPC channels
- gRPC interceptors for authentication and timeouts
- Automatic retry with exponential backoff
- Separate protobuf definitions in `xai-proto` repository

```python
# Python SDK example (for reference)
from xai_sdk import Client, AsyncClient

sync_client = Client(timeout=300)
async_client = AsyncClient(channel_options=[("grpc.enable_retries", 1)])
```

### Required Dependencies

```json
{
    "require": {
        "ext-grpc": "*",
        "google/protobuf": "^4.0",
        "grpc/grpc": "^1.60"
    }
}
```

**System Requirements:**
- PHP `grpc` extension (PECL: `pecl install grpc`)
- PHP `protobuf` extension (optional, for performance)
- `protoc` compiler (for code generation during development)

### Implementation Approach

#### Phase 1: Proto Compilation Setup

```bash
# Directory structure
src/
  Grpc/
    Generated/           # Auto-generated from .proto files
      Chat/
        ChatCompletionRequest.php
        ChatCompletionResponse.php
        ChatServiceClient.php
      Models/
        ...
    GrpcChannel.php      # Channel management
    GrpcClient.php       # Main gRPC client
    Interceptors/
      AuthInterceptor.php
      RetryInterceptor.php
      TimeoutInterceptor.php

proto/                   # Protobuf definitions (from xai-proto)
  chat.proto
  models.proto
  common.proto
```

#### Phase 2: GrpcClient Implementation

```php
<?php

namespace Displace\XaiSdk\Grpc;

use Grpc\Channel;
use Grpc\ChannelCredentials;

class GrpcClient
{
    private const DEFAULT_ENDPOINT = 'api.x.ai:443';

    private Channel $channel;
    private array $interceptors = [];

    public function __construct(
        ?string $apiKey = null,
        string $endpoint = self::DEFAULT_ENDPOINT,
        float $timeout = 300.0,
        array $channelOptions = [],
    ) {
        $key = $apiKey ?? $this->getApiKeyFromEnvironment();

        // Create secure channel with TLS
        $this->channel = new Channel($endpoint, [
            'credentials' => ChannelCredentials::createSsl(),
            'grpc.default_authority' => 'api.x.ai',
            ...$channelOptions,
        ]);

        // Add interceptors
        $this->interceptors[] = new AuthInterceptor($key);
        $this->interceptors[] = new TimeoutInterceptor($timeout);
        $this->interceptors[] = new RetryInterceptor(
            maxAttempts: 5,
            initialBackoff: 0.1,
            maxBackoff: 1.0,
            backoffMultiplier: 2.0,
        );
    }

    public function getChatClient(): ChatServiceClient
    {
        return new ChatServiceClient($this->channel, $this->interceptors);
    }

    public function getModelsClient(): ModelsServiceClient
    {
        return new ModelsServiceClient($this->channel, $this->interceptors);
    }
}
```

#### Phase 3: Service Client Usage

```php
<?php

use Displace\XaiSdk\Grpc\GrpcClient;
use Displace\XaiSdk\Grpc\Generated\Chat\ChatCompletionRequest;
use Displace\XaiSdk\Grpc\Generated\Chat\Message;

$client = new GrpcClient();
$chatClient = $client->getChatClient();

// Build request using generated protobuf classes
$request = new ChatCompletionRequest();
$request->setModel('grok-3');

$message = new Message();
$message->setRole('user');
$message->setContent('Hello, Grok!');
$request->setMessages([$message]);

// Make the call
[$response, $status] = $chatClient->CreateChatCompletion($request)->wait();

if ($status->code === \Grpc\STATUS_OK) {
    echo $response->getChoices()[0]->getMessage()->getContent();
} else {
    throw new GrpcException($status->details, $status->code);
}
```

### Level of Effort

| Aspect | Estimate |
|--------|----------|
| **Proto Definitions** | 1 week (obtain/define) |
| **Build Pipeline Setup** | 1 week (protoc integration) |
| **Core GrpcClient** | 2 weeks |
| **Service Clients** | 2-3 weeks |
| **Interceptors** | 1-2 weeks |
| **Async gRPC** | 2 weeks |
| **Testing** | 2 weeks |
| **Documentation** | 1 week |
| **Total** | **12-15 weeks** |

**Complexity**: High

### Risks and Considerations

1. **PHP gRPC Limitations**: PHP can only create gRPC *clients*, not servers
2. **Extension Required**: Users must install `grpc` PECL extension
3. **Proto Availability**: Need access to xAI's official .proto files (xai-proto repository)
4. **Maintenance Burden**: Must track proto updates and regenerate code
5. **Debugging Difficulty**: Binary protocols are harder to inspect than JSON
6. **Hosting Constraints**: Shared hosting typically lacks gRPC extension
7. **Parallel Implementations**: Must maintain both REST and gRPC clients

**Recommendation**: Implement as optional feature, keeping REST as primary transport.

---

## 3. Voice Agent API (WebSocket)

### Overview

In December 2025, xAI launched the Grok Voice Agent API, enabling real-time voice conversations with Grok models. The API features:

- **Sub-second latency**: Average time-to-first-audio under 1 second
- **100+ languages**: Automatic language detection and response
- **Tool integration**: Web search, X search, custom functions
- **OpenAI Realtime API compatibility**: Similar protocol structure

### API Specifications

**WebSocket Endpoint**: `wss://api.x.ai/v1/realtime`

**Supported Audio Formats**:
| Format | Description | Use Case |
|--------|-------------|----------|
| PCM (Linear16) | Uncompressed, 8-48 kHz | High quality |
| G.711 u-law | Compressed, telephony | Twilio, Vonage |
| G.711 A-law | Compressed, telephony | International PSTN |

**Voice Options**:
| Voice | Gender | Tone | Use Case |
|-------|--------|------|----------|
| Ara | Female | Warm, friendly | Default, conversational |
| Rex | Male | Confident, clear | Business applications |
| Sal | Neutral | Smooth, balanced | Versatile contexts |
| Eve | Female | Energetic, upbeat | Interactive experiences |
| Leo | Male | Authoritative | Instructional content |

**Pricing**: $0.05/minute ($3.00/hour)

### WebSocket Implementation Requirements

#### PHP WebSocket Libraries

**Recommended: Ratchet/Pawl**

Pawl is an async WebSocket client built on ReactPHP:

```json
{
    "require": {
        "ratchet/pawl": "^0.4.3",
        "react/event-loop": "^1.4",
        "react/socket": "^1.9"
    }
}
```

**Alternatives**:
- `textalk/websocket`: Synchronous, simpler but blocking
- `hoa/websocket`: Mature but less maintained
- `amphp/websocket-client`: For Amp-based applications

### Technical Architecture

```
                                  +-----------------+
                                  |   Voice Agent   |
                                  |   Application   |
                                  +--------+--------+
                                           |
                        +------------------+------------------+
                        |                                     |
                +-------v-------+                    +--------v--------+
                |   WebSocket   |                    |   Audio I/O     |
                |   Client      |                    |   Handler       |
                |   (Pawl)      |                    |   (Streams)     |
                +-------+-------+                    +--------+--------+
                        |                                     |
                        |    Bidirectional Messages           |
                        |    (JSON + Base64 Audio)            |
                        |                                     |
                +-------v---------------------------------------v-------+
                |                   wss://api.x.ai/v1/realtime         |
                +------------------------------------------------------+
```

### API Design

```php
<?php

namespace Displace\XaiSdk\Voice;

use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Configuration for voice agent sessions.
 */
class VoiceConfig
{
    public function __construct(
        public readonly string $model = 'grok-voice',
        public readonly string $voice = 'ara',
        public readonly string $inputFormat = 'pcm16',
        public readonly int $inputSampleRate = 16000,
        public readonly string $outputFormat = 'pcm16',
        public readonly int $outputSampleRate = 24000,
        public readonly array $tools = [],
        public readonly ?string $instructions = null,
    ) {}
}

/**
 * Real-time voice agent client.
 */
class VoiceAgentClient
{
    private const REALTIME_ENDPOINT = 'wss://api.x.ai/v1/realtime';

    private string $apiKey;
    private LoopInterface $loop;
    private ?WebSocket $connection = null;

    public function __construct(
        ?string $apiKey = null,
        ?LoopInterface $loop = null,
    ) {
        $this->apiKey = $apiKey ?? getenv('XAI_API_KEY');
        $this->loop = $loop ?? \React\EventLoop\Loop::get();
    }

    /**
     * Establishes a voice session.
     *
     * @return PromiseInterface<VoiceSession>
     */
    public function connect(VoiceConfig $config = new VoiceConfig()): PromiseInterface
    {
        return \Ratchet\Client\connect(
            self::REALTIME_ENDPOINT,
            [],
            ['Authorization' => 'Bearer ' . $this->apiKey],
            $this->loop
        )->then(function (WebSocket $conn) use ($config) {
            $this->connection = $conn;
            return new VoiceSession($conn, $config, $this->loop);
        });
    }
}

/**
 * Active voice conversation session.
 */
class VoiceSession
{
    private WebSocket $connection;
    private VoiceConfig $config;
    private LoopInterface $loop;

    /** @var callable[] */
    private array $eventHandlers = [];

    public function __construct(
        WebSocket $connection,
        VoiceConfig $config,
        LoopInterface $loop,
    ) {
        $this->connection = $connection;
        $this->config = $config;
        $this->loop = $loop;

        $this->setupMessageHandler();
        $this->sendSessionConfig();
    }

    /**
     * Sends audio input to the model.
     *
     * @param string $audioData Raw audio bytes (PCM16, G.711, etc.)
     */
    public function sendAudio(string $audioData): void
    {
        $this->connection->send(json_encode([
            'type' => 'input_audio_buffer.append',
            'audio' => base64_encode($audioData),
        ]));
    }

    /**
     * Streams audio from a readable stream.
     */
    public function streamAudioFrom(ReadableStreamInterface $stream): void
    {
        $stream->on('data', function ($chunk) {
            $this->sendAudio($chunk);
        });

        $stream->on('end', function () {
            $this->commitAudio();
        });
    }

    /**
     * Commits the audio buffer, signaling end of user speech.
     */
    public function commitAudio(): void
    {
        $this->connection->send(json_encode([
            'type' => 'input_audio_buffer.commit',
        ]));
    }

    /**
     * Sends a text message (for hybrid text/voice interactions).
     */
    public function sendText(string $text): void
    {
        $this->connection->send(json_encode([
            'type' => 'conversation.item.create',
            'item' => [
                'type' => 'message',
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $text],
                ],
            ],
        ]));

        $this->connection->send(json_encode([
            'type' => 'response.create',
        ]));
    }

    /**
     * Registers an event handler.
     *
     * Events: 'audio', 'transcript', 'tool_call', 'error', 'close'
     */
    public function on(string $event, callable $handler): self
    {
        $this->eventHandlers[$event][] = $handler;
        return $this;
    }

    /**
     * Closes the session.
     */
    public function close(): void
    {
        $this->connection->close();
    }

    private function setupMessageHandler(): void
    {
        $this->connection->on('message', function ($msg) {
            $data = json_decode((string) $msg, true);
            $this->handleServerEvent($data);
        });

        $this->connection->on('close', function () {
            $this->emit('close');
        });
    }

    private function handleServerEvent(array $event): void
    {
        match ($event['type'] ?? '') {
            'response.audio.delta' => $this->emit('audio', base64_decode($event['delta'])),
            'response.audio_transcript.delta' => $this->emit('transcript', $event['delta']),
            'response.function_call_arguments.done' => $this->handleToolCall($event),
            'error' => $this->emit('error', $event['error']),
            default => null,
        };
    }

    private function handleToolCall(array $event): void
    {
        $this->emit('tool_call', [
            'call_id' => $event['call_id'],
            'name' => $event['name'],
            'arguments' => json_decode($event['arguments'], true),
        ]);
    }

    private function emit(string $event, mixed $data = null): void
    {
        foreach ($this->eventHandlers[$event] ?? [] as $handler) {
            $handler($data);
        }
    }

    private function sendSessionConfig(): void
    {
        $this->connection->send(json_encode([
            'type' => 'session.update',
            'session' => [
                'model' => $this->config->model,
                'voice' => $this->config->voice,
                'input_audio_format' => $this->config->inputFormat,
                'output_audio_format' => $this->config->outputFormat,
                'tools' => $this->config->tools,
                'instructions' => $this->config->instructions,
            ],
        ]));
    }
}
```

### Example Usage

```php
<?php

use Displace\XaiSdk\Voice\VoiceAgentClient;
use Displace\XaiSdk\Voice\VoiceConfig;

$client = new VoiceAgentClient();

$config = new VoiceConfig(
    voice: 'rex',
    instructions: 'You are a helpful customer service agent.',
    tools: [
        [
            'type' => 'function',
            'name' => 'lookup_order',
            'description' => 'Look up order status by order ID',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'order_id' => ['type' => 'string'],
                ],
                'required' => ['order_id'],
            ],
        ],
    ],
);

$client->connect($config)->then(function ($session) {
    echo "Connected to voice session\n";

    // Handle incoming audio
    $session->on('audio', function ($audioData) {
        // Play audio through speaker or forward to WebRTC peer
        playAudio($audioData);
    });

    // Handle transcripts for logging
    $session->on('transcript', function ($text) {
        echo "Assistant: $text\n";
    });

    // Handle tool calls
    $session->on('tool_call', function ($call) use ($session) {
        if ($call['name'] === 'lookup_order') {
            $result = lookupOrder($call['arguments']['order_id']);
            $session->sendToolResult($call['call_id'], $result);
        }
    });

    // Stream microphone input
    $microphone = getMicrophoneStream();
    $session->streamAudioFrom($microphone);
});

// Run the event loop
\React\EventLoop\Loop::get()->run();
```

### Real-Time Audio Streaming Considerations

#### Audio Buffer Management

```php
<?php

/**
 * Manages audio buffering for optimal streaming performance.
 */
class AudioBuffer
{
    private string $buffer = '';
    private int $chunkSize;
    private int $sampleRate;

    public function __construct(
        int $chunkSize = 4096,  // ~128ms at 16kHz mono PCM16
        int $sampleRate = 16000,
    ) {
        $this->chunkSize = $chunkSize;
        $this->sampleRate = $sampleRate;
    }

    public function append(string $data): array
    {
        $this->buffer .= $data;
        $chunks = [];

        while (strlen($this->buffer) >= $this->chunkSize) {
            $chunks[] = substr($this->buffer, 0, $this->chunkSize);
            $this->buffer = substr($this->buffer, $this->chunkSize);
        }

        return $chunks;
    }

    public function flush(): string
    {
        $remaining = $this->buffer;
        $this->buffer = '';
        return $remaining;
    }
}
```

#### Voice Activity Detection (VAD)

```php
<?php

/**
 * Simple energy-based voice activity detection.
 */
class VoiceActivityDetector
{
    private float $threshold;
    private int $holdTime;
    private bool $speaking = false;
    private int $silenceSamples = 0;

    public function __construct(
        float $threshold = 0.01,
        int $holdTimeMs = 300,
        int $sampleRate = 16000,
    ) {
        $this->threshold = $threshold;
        $this->holdTime = (int) ($holdTimeMs * $sampleRate / 1000);
    }

    /**
     * Processes audio and returns speech state.
     *
     * @param string $pcm16Data Raw PCM16 audio
     * @return array{speaking: bool, just_started: bool, just_stopped: bool}
     */
    public function process(string $pcm16Data): array
    {
        $energy = $this->calculateEnergy($pcm16Data);
        $wasSpeaking = $this->speaking;

        if ($energy > $this->threshold) {
            $this->speaking = true;
            $this->silenceSamples = 0;
        } else {
            $this->silenceSamples += strlen($pcm16Data) / 2;
            if ($this->silenceSamples > $this->holdTime) {
                $this->speaking = false;
            }
        }

        return [
            'speaking' => $this->speaking,
            'just_started' => $this->speaking && !$wasSpeaking,
            'just_stopped' => !$this->speaking && $wasSpeaking,
        ];
    }

    private function calculateEnergy(string $pcm16Data): float
    {
        $samples = unpack('v*', $pcm16Data);
        $sum = 0;

        foreach ($samples as $sample) {
            // Convert unsigned to signed
            if ($sample > 32767) {
                $sample -= 65536;
            }
            $sum += $sample * $sample;
        }

        return sqrt($sum / count($samples)) / 32768;
    }
}
```

### Telephony Integration

The Voice Agent API supports SIP integration via Twilio and Vonage:

```php
<?php

/**
 * Twilio MediaStream integration for telephony.
 */
class TwilioMediaBridge
{
    private VoiceSession $voiceSession;
    private WebSocket $twilioWs;

    public function __construct(VoiceSession $voiceSession)
    {
        $this->voiceSession = $voiceSession;
    }

    /**
     * Handles incoming Twilio MediaStream WebSocket.
     */
    public function handleTwilioConnection(WebSocket $twilioWs): void
    {
        $this->twilioWs = $twilioWs;

        // Forward Twilio audio to xAI
        $twilioWs->on('message', function ($msg) {
            $data = json_decode((string) $msg, true);

            if ($data['event'] === 'media') {
                // Twilio sends u-law encoded audio
                $audio = base64_decode($data['media']['payload']);
                $this->voiceSession->sendAudio($audio);
            }
        });

        // Forward xAI audio back to Twilio
        $this->voiceSession->on('audio', function ($audio) {
            $this->twilioWs->send(json_encode([
                'event' => 'media',
                'streamSid' => $this->streamSid,
                'media' => [
                    'payload' => base64_encode($audio),
                ],
            ]));
        });
    }
}
```

### Level of Effort

| Aspect | Estimate |
|--------|----------|
| **WebSocket Client Setup** | 1 week |
| **VoiceSession Core** | 2-3 weeks |
| **Audio Buffer Management** | 1 week |
| **Event Handling System** | 1 week |
| **Tool Call Integration** | 1-2 weeks |
| **Voice Activity Detection** | 1 week |
| **Telephony Bridges** | 2 weeks |
| **Testing** | 2-3 weeks |
| **Documentation & Examples** | 1-2 weeks |
| **Total** | **12-17 weeks** |

**Complexity**: High

### Risks and Considerations

1. **Event-Driven Requirement**: Voice API requires ReactPHP or similar event loop
2. **Audio Processing**: PHP is not ideal for real-time audio processing
3. **Latency Sensitivity**: Network jitter affects conversation quality
4. **Browser Limitation**: Cannot connect WebSocket directly to xAI from browser (needs proxy)
5. **Hosting Requirements**: Requires long-running PHP process (Swoole, RoadRunner, or CLI)
6. **Testing Challenges**: Real-time audio testing is complex
7. **LiveKit Dependency**: Production use may require LiveKit integration
8. **Codec Support**: May need FFmpeg for audio format conversion

**Recommendation**: Implement as optional feature for advanced use cases. Consider providing a separate package (`displace/xai-voice`) to avoid adding event-loop dependencies to the core SDK.

---

## Implementation Priority

Based on value-to-effort ratio and ecosystem readiness:

### Recommended Order

| Priority | Feature | Rationale |
|----------|---------|-----------|
| **1** | Full Async Support | Enables Voice API; improves throughput; foundation for future features |
| **2** | Voice Agent API | High-visibility feature; competitive differentiation; requires async |
| **3** | gRPC Support | Complex; requires proto files; REST API is sufficient for most uses |

### Alternative Prioritization (If Proto Files Available)

If xAI publishes official `.proto` files publicly:

| Priority | Feature | Rationale |
|----------|---------|-----------|
| **1** | gRPC Support | Direct parity with Python SDK; better performance |
| **2** | Full Async Support | Async gRPC for high-throughput |
| **3** | Voice Agent API | Built on async gRPC streaming |

---

## Timeline Considerations

### Dependencies Between Features

```
                    +----------------+
                    |  Async Support |
                    +-------+--------+
                            |
                            | Required for
                            |
            +---------------+---------------+
            |                               |
    +-------v-------+               +-------v-------+
    | Voice Agent   |               | Async gRPC    |
    | API           |               | (Optional)    |
    +---------------+               +---------------+
```

### Suggested Release Plan

| Version | Features | Timeline |
|---------|----------|----------|
| **v1.1** | Bug fixes, improved streaming | Q1 2026 |
| **v2.0-alpha** | Async support (ReactPHP) | Q2 2026 |
| **v2.0-beta** | Voice Agent API | Q3 2026 |
| **v2.0** | Stable release | Q4 2026 |
| **v2.1** | gRPC support (if proto available) | 2027 |

### Resource Requirements

| Feature | Developers | Duration |
|---------|------------|----------|
| Async Support | 1-2 | 3 months |
| Voice API | 1-2 | 3-4 months |
| gRPC | 2 | 4 months |

**Total for v2.0**: 2 developers, 6-8 months

---

## Appendix: Research Sources

### Official Documentation
- [xAI Developer Docs](https://docs.x.ai)
- [Grok Voice Agent API Guide](https://docs.x.ai/docs/guides/voice)
- [xAI Python SDK](https://github.com/xai-org/xai-sdk-python)

### PHP Libraries
- [ReactPHP](https://reactphp.org/)
- [ReactPHP HTTP Client](https://github.com/reactphp/http)
- [Ratchet/Pawl WebSocket Client](https://github.com/ratchetphp/Pawl)
- [gRPC PHP Quick Start](https://grpc.io/docs/languages/php/quickstart/)
- [Google Cloud PHP gRPC Guide](https://cloud.google.com/php/grpc)

### Technical References
- [PHP Fibers and Async](https://medium.com/@mohamadshahkhajeh/async-php-in-2025-beyond-workers-with-fibers-reactphp-and-amp-e7de384c3ea6)
- [gRPC PHP Laravel Implementation](https://jaystechbites.com/posts/2024/grpc-php-laravel-implementation-guide/)
- [LiveKit xAI Voice Plugin](https://docs.livekit.io/agents/models/realtime/plugins/xai/)

### Related Announcements
- [Grok Voice Agent API Launch](https://x.ai/news/grok-voice-agent-api)
- [xAI LiveKit Partnership](https://blog.livekit.io/xai-livekit-partnership-grok-voice-agent-api/)
