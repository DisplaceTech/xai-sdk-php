# Framework Integration Packages

## Overview

Framework-specific integration packages bridge the gap between a generic PHP SDK and the conventions, tooling, and developer expectations of specific frameworks. While the base `displace/xai-sdk-php` package is fully functional in any PHP environment, dedicated Laravel and Symfony packages would provide:

1. **Reduced Boilerplate**: Eliminate repetitive service configuration code
2. **Framework Conventions**: Follow established patterns developers already know
3. **Native Integration**: Leverage framework-specific features (queues, caching, logging)
4. **Testing Support**: Framework-aware testing utilities and mocks
5. **Developer Experience**: IDE autocompletion, artisan/console commands, configuration publishing

### Industry Precedent

This approach mirrors successful patterns from other AI SDK ecosystems:

| SDK | Base Package | Laravel Package | Symfony Bundle |
|-----|--------------|-----------------|----------------|
| OpenAI PHP | `openai-php/client` | `openai-php/laravel` | Community bundles |
| Claude PHP | `claude-php/claude-php-sdk` | `claude-php/claude-php-sdk-laravel` | Service config guides |
| Anthropic | `anthropic-ai/sdk` | Community integrations | Service config guides |

The OpenAI Laravel integration (`openai-php/laravel`) is particularly well-executed and serves as a model for what `displace/xai-laravel` could become.

---

## displace/xai-laravel

### Value Proposition

**Why Laravel developers would want this vs using the base SDK:**

1. **Zero Configuration**: Package auto-discovery means `composer require` and go
2. **Environment-Driven Config**: `.env` variables automatically map to SDK configuration
3. **Facade Access**: `XAI::chat()->create(...)` instead of manual instantiation
4. **Native Queue Support**: Dispatch AI operations to Laravel queues with one line
5. **Horizon Visibility**: Monitor AI job processing alongside other jobs
6. **Testing Utilities**: `XAI::fake()` for testing without hitting the API
7. **Logging Integration**: API calls automatically log through Laravel's logger
8. **Cache Integration**: Response caching through Laravel's cache drivers

**Problems it solves:**

- Eliminates service container binding boilerplate
- Provides consistent configuration management
- Enables easy mocking for unit tests
- Integrates AI operations with existing job infrastructure
- Unifies logging across the application

**Developer experience improvements:**

```php
// Without Laravel package (current approach)
use Displace\XaiSdk\XaiClient;

$client = new XaiClient(
    apiKey: config('services.xai.key'),
    timeout: config('services.xai.timeout'),
);
$chat = $client->chat->create(model: 'grok-3');

// With Laravel package
use XAI;

$chat = XAI::chat()->create(model: 'grok-3');
```

### Package Structure

```
displace/xai-laravel/
├── src/
│   ├── Facades/
│   │   └── XAI.php                    # Facade class
│   ├── XAIServiceProvider.php         # Service provider
│   └── Console/
│       └── InstallCommand.php         # php artisan xai:install
├── config/
│   └── xai.php                        # Publishable config
├── composer.json
├── LICENSE
└── README.md
```

### Service Provider

```php
<?php

namespace Displace\XAILaravel;

use Displace\XaiSdk\XaiClient;
use Illuminate\Support\ServiceProvider;

class XAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/xai.php', 'xai');

        $this->app->singleton(XaiClient::class, function ($app) {
            $config = $app['config']['xai'];

            return new XaiClient(
                apiKey: $config['api_key'],
                baseUrl: $config['base_url'],
                timeout: $config['timeout'],
                headers: $config['headers'] ?? [],
            );
        });

        $this->app->alias(XaiClient::class, 'xai');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/xai.php' => config_path('xai.php'),
            ], 'xai-config');

            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }
}
```

### Facade

```php
<?php

namespace Displace\XAILaravel\Facades;

use Displace\XaiSdk\XaiClient;
use Displace\XaiSdk\Resources\ChatResource;
use Displace\XaiSdk\Resources\ImageResource;
use Displace\XaiSdk\Resources\ModelsResource;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ChatResource chat()
 * @method static ImageResource image()
 * @method static ModelsResource models()
 * @method static void fake(array $responses = [])
 * @method static void assertSent(callable $callback)
 *
 * @see \Displace\XaiSdk\XaiClient
 */
class XAI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XaiClient::class;
    }

    /**
     * Register a fake XAI client for testing.
     */
    public static function fake(array $responses = []): void
    {
        static::swap(new FakeClient($responses));
    }
}
```

### Config File Structure

```php
<?php
// config/xai.php

return [
    /*
    |--------------------------------------------------------------------------
    | xAI API Key
    |--------------------------------------------------------------------------
    |
    | Your xAI API key. You can find this in your xAI console at
    | https://console.x.ai/. The key should start with 'xai-'.
    |
    */
    'api_key' => env('XAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for xAI API requests. You should not need to change this
    | unless you are using a proxy or custom endpoint.
    |
    */
    'base_url' => env('XAI_BASE_URL', 'https://api.x.ai/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for a response from the API.
    | Streaming requests may need higher timeouts for long generations.
    |
    */
    'timeout' => (float) env('XAI_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default model to use when creating chat conversations.
    |
    */
    'default_model' => env('XAI_DEFAULT_MODEL', 'grok-3'),

    /*
    |--------------------------------------------------------------------------
    | Additional Headers
    |--------------------------------------------------------------------------
    |
    | Additional headers to send with each API request. Useful for
    | custom tracking or proxy authentication.
    |
    */
    'headers' => [
        // 'X-Custom-Header' => 'value',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for xAI API requests. When enabled, requests and
    | responses will be logged to the specified channel.
    |
    */
    'logging' => [
        'enabled' => env('XAI_LOGGING_ENABLED', false),
        'channel' => env('XAI_LOG_CHANNEL', 'stack'),
        'level' => env('XAI_LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Enable response caching for identical requests. This can significantly
    | reduce API costs for repeated queries. Only non-streaming requests
    | are cached.
    |
    */
    'cache' => [
        'enabled' => env('XAI_CACHE_ENABLED', false),
        'store' => env('XAI_CACHE_STORE', null), // null uses default
        'ttl' => env('XAI_CACHE_TTL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Default queue settings for dispatching xAI jobs.
    |
    */
    'queue' => [
        'connection' => env('XAI_QUEUE_CONNECTION', null), // null uses default
        'queue' => env('XAI_QUEUE_NAME', 'xai'),
    ],
];
```

### Artisan Commands

```php
<?php

namespace Displace\XAILaravel\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'xai:install';

    protected $description = 'Install the xAI configuration file';

    public function handle(): int
    {
        $this->info('Installing xAI SDK for Laravel...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'xai-config',
        ]);

        // Add environment variables to .env.example
        $this->addEnvironmentVariables();

        $this->info('xAI SDK installed successfully!');
        $this->newLine();
        $this->line('Add your API key to .env:');
        $this->line('  XAI_API_KEY=your-api-key');

        return Command::SUCCESS;
    }

    protected function addEnvironmentVariables(): void
    {
        $envExample = base_path('.env.example');

        if (! file_exists($envExample)) {
            return;
        }

        $contents = file_get_contents($envExample);

        if (str_contains($contents, 'XAI_API_KEY')) {
            return;
        }

        file_put_contents($envExample, $contents . "\n# xAI Configuration\nXAI_API_KEY=\n");
    }
}
```

### Features

#### 1. Auto-Discovery and Configuration

Laravel 5.5+ package auto-discovery automatically registers the service provider:

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "Displace\\XAILaravel\\XAIServiceProvider"
            ],
            "aliases": {
                "XAI": "Displace\\XAILaravel\\Facades\\XAI"
            }
        }
    }
}
```

#### 2. Facade Usage

```php
use XAI;
use function Displace\XaiSdk\Chat\{system, user};

// Create a chat
$chat = XAI::chat()->create(
    model: 'grok-3',
    messages: [system('You are a helpful assistant.')]
);

$chat->append(user('Hello!'));
$response = $chat->sample();

// List models
$models = XAI::models()->list();

// Generate images
$image = XAI::image()->generate(
    prompt: 'A sunset over mountains',
    model: 'grok-2-image'
);
```

#### 3. Config Publishing

```bash
php artisan vendor:publish --tag=xai-config
```

#### 4. Queue Integration for Background Processing

```php
<?php

namespace App\Jobs;

use Displace\XaiSdk\XaiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAIRequest implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public string $prompt,
        public string $model = 'grok-3',
    ) {
        $this->onQueue(config('xai.queue.queue'));
    }

    public function handle(XaiClient $client): void
    {
        $chat = $client->chat->create(model: $this->model);
        $chat->append(user($this->prompt));
        $response = $chat->sample();

        // Store result, dispatch events, etc.
        event(new AIResponseReceived($response));
    }
}

// Usage
ProcessAIRequest::dispatch('Summarize this document...');
```

#### 5. Horizon Integration

The queue integration automatically works with Laravel Horizon. Configure in `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-xai' => [
            'connection' => 'redis',
            'queue' => ['xai'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 600, // Long timeout for AI operations
        ],
    ],
],
```

#### 6. Logging Integration

```php
// In XAIServiceProvider, optionally wrap client with logging decorator
if (config('xai.logging.enabled')) {
    $this->app->extend(XaiClient::class, function ($client, $app) {
        return new LoggingClientDecorator(
            $client,
            $app['log']->channel(config('xai.logging.channel')),
            config('xai.logging.level'),
        );
    });
}
```

#### 7. Cache Integration

```php
// Cache wrapper for repeated identical requests
public function getCachedResponse(string $cacheKey, callable $request): mixed
{
    if (! config('xai.cache.enabled')) {
        return $request();
    }

    return Cache::store(config('xai.cache.store'))
        ->remember($cacheKey, config('xai.cache.ttl'), $request);
}
```

### Example Usage

```php
<?php

namespace App\Http\Controllers;

use App\Models\Document;
use XAI;
use function Displace\XaiSdk\Chat\{system, user};

class SummaryController extends Controller
{
    public function summarize(Document $document)
    {
        $chat = XAI::chat()->create(
            model: config('xai.default_model'),
            messages: [
                system('You are a document summarization expert.'),
            ],
        );

        $chat->append(user("Summarize this document:\n\n{$document->content}"));
        $response = $chat->sample();

        return response()->json([
            'summary' => $response->getContent(),
        ]);
    }

    // Streaming response for real-time UI updates
    public function streamSummary(Document $document)
    {
        return response()->stream(function () use ($document) {
            $chat = XAI::chat()->create(model: 'grok-3');
            $chat->append(user("Summarize: {$document->content}"));

            foreach ($chat->stream() as [$response, $chunk]) {
                echo "data: " . json_encode(['content' => $chunk->content]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
```

### Testing Support

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use XAI;

class SummaryTest extends TestCase
{
    public function test_document_summarization(): void
    {
        XAI::fake([
            'chat.sample' => [
                'content' => 'This is a test summary.',
            ],
        ]);

        $response = $this->postJson('/api/summarize', [
            'document_id' => 1,
        ]);

        $response->assertOk()
            ->assertJson(['summary' => 'This is a test summary.']);

        XAI::assertSent(function ($request) {
            return str_contains($request['messages'][1]['content'], 'Summarize');
        });
    }
}
```

### Repository Structure

**Should this be a separate repo? Yes.**

Reasons for separate repository:

1. **Independent Versioning**: Laravel package can version independently of base SDK
2. **Framework-Specific CI**: Test against multiple Laravel versions (10.x, 11.x)
3. **Focused Issues**: Laravel-specific issues separate from core SDK bugs
4. **Contribution Barriers**: Lower barrier for Laravel-only contributors
5. **Package Discovery**: Separate Packagist listing improves discoverability

**Repository naming:**
- GitHub: `DisplaceTech/xai-laravel`
- Packagist: `displace/xai-laravel`

**Versioning strategy:**

| Base SDK Version | Laravel Package Version | Notes |
|------------------|------------------------|-------|
| 1.0.x | 1.0.x | Initial release, Laravel 10+ |
| 1.1.x | 1.1.x | Feature additions |
| 2.0.x | 2.0.x | Breaking changes sync |

The Laravel package should follow semantic versioning aligned with major base SDK versions but can release minor/patch versions independently for Laravel-specific fixes.

### Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "displace/xai-sdk-php": "^1.0",
        "illuminate/contracts": "^10.0|^11.0",
        "illuminate/support": "^10.0|^11.0"
    },
    "require-dev": {
        "orchestra/testbench": "^8.0|^9.0",
        "phpunit/phpunit": "^10.0|^11.0"
    }
}
```

---

## displace/xai-symfony

### Value Proposition

**Why Symfony developers would want this vs using the base SDK:**

1. **Service Autowiring**: Inject `XaiClient` anywhere with type-hints
2. **YAML/XML Configuration**: Configure via Symfony's familiar config format
3. **Messenger Integration**: Dispatch AI operations to Symfony Messenger queues
4. **Monolog Integration**: Automatic logging through Symfony's logging stack
5. **Cache Pool Integration**: Response caching via Symfony's cache component
6. **Profiler Integration**: Debug panel showing API calls in development
7. **Environment Variables**: Secrets management via Symfony's secrets vault

**Problems it solves:**

- Standardizes service configuration across Symfony applications
- Integrates with Symfony's async message handling
- Provides development debugging tools
- Unifies configuration with existing infrastructure

**Developer experience improvements:**

```php
// Without Symfony bundle (current approach)
// In services.yaml:
// services:
//   Displace\XaiSdk\XaiClient:
//     arguments:
//       $apiKey: '%env(XAI_API_KEY)%'
//       $timeout: '%env(float:XAI_TIMEOUT)%'

// With Symfony bundle
// In config/packages/xai.yaml:
// xai:
//   api_key: '%env(XAI_API_KEY)%'
//   timeout: 300
```

### Package Structure

```
displace/xai-symfony/
├── src/
│   ├── DependencyInjection/
│   │   ├── Configuration.php          # Bundle configuration schema
│   │   └── XAIExtension.php            # Service definitions
│   ├── Message/
│   │   ├── ChatRequest.php             # Messenger message
│   │   └── ChatRequestHandler.php      # Messenger handler
│   ├── DataCollector/
│   │   └── XAIDataCollector.php        # Profiler integration
│   └── XAIBundle.php                   # Bundle class
├── config/
│   └── services.xml                    # Default service definitions
├── templates/
│   └── data_collector/
│       └── xai.html.twig               # Profiler panel template
├── composer.json
├── LICENSE
└── README.md
```

### Bundle Class

```php
<?php

namespace Displace\XAISymfony;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class XAIBundle extends AbstractBundle
{
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $container->import('../config/services.xml');

        $container->services()
            ->get('xai.client')
            ->arg('$apiKey', $config['api_key'])
            ->arg('$baseUrl', $config['base_url'])
            ->arg('$timeout', $config['timeout']);

        // Enable profiler data collector in dev
        if ($config['profiler']['enabled'] ?? false) {
            $container->services()
                ->get('xai.data_collector')
                ->tag('data_collector', [
                    'template' => '@XAI/data_collector/xai.html.twig',
                    'id' => 'xai',
                ]);
        }
    }
}
```

### Extension and Configuration

```php
<?php

namespace Displace\XAISymfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('xai');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Your xAI API key')
                ->end()
                ->scalarNode('base_url')
                    ->defaultValue('https://api.x.ai/v1')
                    ->info('Base URL for API requests')
                ->end()
                ->floatNode('timeout')
                    ->defaultValue(300.0)
                    ->info('Request timeout in seconds')
                ->end()
                ->scalarNode('default_model')
                    ->defaultValue('grok-3')
                    ->info('Default model for chat operations')
                ->end()
                ->arrayNode('profiler')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue('%kernel.debug%')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('messenger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('transport')
                            ->defaultValue('async')
                            ->info('Messenger transport for async operations')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue(false)
                        ->end()
                        ->scalarNode('pool')
                            ->defaultValue('cache.app')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
```

### Service Definitions

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<!-- config/services.xml -->
<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services
        https://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <defaults autowire="true" autoconfigure="true"/>

        <!-- Main XAI Client -->
        <service id="xai.client" class="Displace\XaiSdk\XaiClient">
            <argument key="$apiKey">%xai.api_key%</argument>
            <argument key="$baseUrl">%xai.base_url%</argument>
            <argument key="$timeout">%xai.timeout%</argument>
        </service>

        <!-- Alias for autowiring -->
        <service id="Displace\XaiSdk\XaiClient" alias="xai.client"/>

        <!-- Messenger Handler -->
        <service id="xai.message_handler"
                 class="Displace\XAISymfony\Message\ChatRequestHandler">
            <tag name="messenger.message_handler"/>
        </service>

        <!-- Data Collector (conditionally enabled) -->
        <service id="xai.data_collector"
                 class="Displace\XAISymfony\DataCollector\XAIDataCollector">
            <argument type="service" id="xai.client"/>
        </service>
    </services>
</container>
```

### Features

#### 1. Service Autowiring

```php
<?php

namespace App\Controller;

use Displace\XaiSdk\XaiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use function Displace\XaiSdk\Chat\{system, user};

class ChatController extends AbstractController
{
    public function __construct(
        private readonly XaiClient $xaiClient,
    ) {}

    public function chat(string $message): JsonResponse
    {
        $chat = $this->xaiClient->chat->create(model: 'grok-3');
        $chat->append(user($message));
        $response = $chat->sample();

        return $this->json([
            'response' => $response->getContent(),
        ]);
    }
}
```

#### 2. Configuration via YAML

```yaml
# config/packages/xai.yaml
xai:
    api_key: '%env(XAI_API_KEY)%'
    base_url: '%env(XAI_BASE_URL)%'
    timeout: 300
    default_model: 'grok-3'

    profiler:
        enabled: '%kernel.debug%'

    messenger:
        transport: 'async'

    cache:
        enabled: true
        pool: 'cache.app'
        ttl: 3600

# config/packages/dev/xai.yaml
xai:
    profiler:
        enabled: true
```

#### 3. Messenger Integration for Async Processing

```php
<?php
// src/Message/ChatRequest.php

namespace Displace\XAISymfony\Message;

final class ChatRequest
{
    public function __construct(
        public readonly string $prompt,
        public readonly string $model = 'grok-3',
        public readonly ?string $systemPrompt = null,
        public readonly array $metadata = [],
    ) {}
}
```

```php
<?php
// src/Message/ChatRequestHandler.php

namespace Displace\XAISymfony\Message;

use Displace\XaiSdk\XaiClient;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function Displace\XaiSdk\Chat\{system, user};

#[AsMessageHandler]
final class ChatRequestHandler
{
    public function __construct(
        private readonly XaiClient $client,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(ChatRequest $message): void
    {
        $messages = [];

        if ($message->systemPrompt) {
            $messages[] = system($message->systemPrompt);
        }

        $chat = $this->client->chat->create(
            model: $message->model,
            messages: $messages,
        );

        $chat->append(user($message->prompt));
        $response = $chat->sample();

        $this->dispatcher->dispatch(new ChatResponseReceived(
            response: $response,
            metadata: $message->metadata,
        ));
    }
}
```

Usage:

```php
<?php

namespace App\Controller;

use Displace\XAISymfony\Message\ChatRequest;
use Symfony\Component\Messenger\MessageBusInterface;

class AsyncChatController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function submitChat(Request $request): JsonResponse
    {
        $this->bus->dispatch(new ChatRequest(
            prompt: $request->get('prompt'),
            model: 'grok-3',
            metadata: ['user_id' => $this->getUser()->getId()],
        ));

        return $this->json(['status' => 'processing']);
    }
}
```

Configure routing in `config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        routing:
            'Displace\XAISymfony\Message\ChatRequest': async
```

#### 4. Cache Pool Integration

```php
<?php

namespace Displace\XAISymfony\Cache;

use Displace\XaiSdk\XaiClient;
use Psr\Cache\CacheItemPoolInterface;

class CachedXaiClient
{
    public function __construct(
        private readonly XaiClient $client,
        private readonly CacheItemPoolInterface $cache,
        private readonly int $ttl = 3600,
    ) {}

    public function getCachedChat(string $cacheKey, callable $chatBuilder): mixed
    {
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        $result = $chatBuilder($this->client);

        $item->set($result);
        $item->expiresAfter($this->ttl);
        $this->cache->save($item);

        return $result;
    }
}
```

#### 5. Monolog Integration

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['xai']
    handlers:
        xai:
            type: stream
            path: "%kernel.logs_dir%/xai.log"
            level: debug
            channels: ['xai']
```

```php
<?php

namespace Displace\XAISymfony\Logger;

use Psr\Log\LoggerInterface;

class LoggingClientDecorator
{
    public function __construct(
        private readonly XaiClient $client,
        private readonly LoggerInterface $logger,
    ) {}

    // Wrap client methods with logging
}
```

#### 6. Profiler/Debug Toolbar Integration

```php
<?php

namespace Displace\XAISymfony\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class XAIDataCollector extends AbstractDataCollector
{
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'requests' => $this->getTrackedRequests(),
            'total_tokens' => $this->getTotalTokens(),
            'total_time' => $this->getTotalTime(),
        ];
    }

    public function getName(): string
    {
        return 'xai';
    }

    public static function getTemplate(): ?string
    {
        return '@XAI/data_collector/xai.html.twig';
    }
}
```

### Example Usage

```php
<?php

namespace App\Service;

use Displace\XaiSdk\XaiClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use function Displace\XaiSdk\Chat\{system, user};

class DocumentAnalyzer
{
    public function __construct(
        private readonly XaiClient $xai,
        private readonly CacheInterface $cache,
    ) {}

    public function analyze(string $content): string
    {
        return $this->cache->get(
            'analysis_' . md5($content),
            function (ItemInterface $item) use ($content) {
                $item->expiresAfter(3600);

                $chat = $this->xai->chat->create(
                    model: 'grok-3',
                    messages: [
                        system('You are a document analysis expert.'),
                    ],
                );

                $chat->append(user("Analyze this document:\n\n{$content}"));
                return $chat->sample()->getContent();
            }
        );
    }
}
```

### Repository Structure

**Should this be a separate repo? Yes.**

Same reasoning as Laravel package, plus:

1. **Bundle-Specific Testing**: Test against Symfony 6.4, 7.x
2. **Flex Recipe**: Potential for Symfony Flex recipe distribution
3. **Community Expectations**: Symfony developers expect standalone bundles

**Repository naming:**
- GitHub: `DisplaceTech/xai-symfony`
- Packagist: `displace/xai-symfony`

**Versioning strategy:**

Follow same pattern as Laravel package, aligned with base SDK major versions.

### Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "displace/xai-sdk-php": "^1.0",
        "symfony/config": "^6.4|^7.0",
        "symfony/dependency-injection": "^6.4|^7.0",
        "symfony/http-kernel": "^6.4|^7.0"
    },
    "require-dev": {
        "symfony/messenger": "^6.4|^7.0",
        "symfony/cache": "^6.4|^7.0",
        "symfony/monolog-bundle": "^3.0",
        "symfony/phpunit-bridge": "^6.4|^7.0"
    },
    "suggest": {
        "symfony/messenger": "For async message processing",
        "symfony/cache": "For response caching",
        "symfony/web-profiler-bundle": "For debug toolbar integration"
    }
}
```

---

## Comparison: Framework Package vs Base SDK

| Feature | Base SDK | Laravel Package | Symfony Bundle |
|---------|----------|-----------------|----------------|
| **Installation** | `composer require` | `composer require` + auto-discovery | `composer require` + bundle enable |
| **Configuration** | Constructor args | `.env` + `config/xai.php` | YAML/XML + env vars |
| **Service Access** | Manual instantiation | Facade + DI | Autowiring |
| **Queue Integration** | Manual | Laravel Queues + Horizon | Symfony Messenger |
| **Caching** | Manual | Laravel Cache facade | Symfony Cache pools |
| **Logging** | PSR-3 logger injection | Laravel Log facade | Monolog channels |
| **Testing** | Mock HTTP client | `XAI::fake()` | Symfony test utilities |
| **Debugging** | Manual | Telescope integration | Profiler toolbar |
| **Commands** | N/A | `artisan xai:install` | `bin/console` commands |
| **Config Validation** | Runtime | Config file validation | Symfony Config component |

### When to Use What

**Use Base SDK when:**
- Building framework-agnostic libraries
- Working with micro-frameworks (Slim, Lumen)
- Need maximum control over instantiation
- Building CLI tools or workers
- Framework integration packages don't exist yet

**Use Laravel Package when:**
- Building Laravel applications
- Want zero-configuration setup
- Need queue integration (especially with Horizon)
- Want facade-style API
- Testing with Laravel's testing utilities

**Use Symfony Bundle when:**
- Building Symfony applications
- Want YAML-based configuration
- Need Messenger integration for async
- Want profiler/debug toolbar support
- Using Symfony's dependency injection patterns

---

## Recommendation

### Should These Be Built?

**Yes, but with strategic prioritization.**

### Priority Assessment

| Package | Priority | Effort | Value | Reasoning |
|---------|----------|--------|-------|-----------|
| `displace/xai-laravel` | **High** | Medium | High | Laravel dominates PHP market share; OpenAI Laravel package success proves demand |
| `displace/xai-symfony` | **Medium** | Medium | Medium | Smaller but sophisticated user base; enterprise adoption |

### Implementation Roadmap

#### Phase 1: Laravel Package (Recommended First)

**Timeline:** 2-3 weeks for initial release

1. Week 1: Core service provider, facade, configuration
2. Week 2: Queue integration, testing utilities
3. Week 3: Documentation, examples, testing against Laravel 10/11

**Why Laravel first:**
- Larger community = more feedback
- Simpler patterns = faster development
- Reference implementation for Symfony

#### Phase 2: Symfony Bundle

**Timeline:** 2-3 weeks after Laravel stabilization

1. Week 1: Bundle structure, configuration, autowiring
2. Week 2: Messenger integration, profiler
3. Week 3: Documentation, Symfony 6.4/7.x testing

### Maintenance Considerations

**Estimated Ongoing Effort:**

| Activity | Frequency | Time |
|----------|-----------|------|
| Framework version updates | Quarterly | 2-4 hours each |
| Base SDK version sync | Per SDK release | 1-2 hours |
| Issue triage | Weekly | 1-2 hours |
| Documentation updates | Monthly | 1-2 hours |

**Total: ~10-20 hours/month for both packages**

### Who Should Maintain Them?

**Option 1: Same Team as Base SDK (Recommended)**
- Ensures consistency
- Single source of truth for patterns
- Coordinated releases

**Option 2: Community Maintainers**
- Reduces core team burden
- Requires clear contribution guidelines
- Risk of divergence

**Recommendation:** Start with core team maintenance, establish patterns, then consider community co-maintainers for day-to-day issues.

### Alternative: Documentation-Only Approach

If resources are constrained, an alternative is to provide comprehensive documentation for framework integration without dedicated packages:

```markdown
# Framework Integration Guide

## Laravel Integration
[Step-by-step service provider creation]
[Queue job examples]
[Testing patterns]

## Symfony Integration
[Bundle creation guide]
[Messenger configuration]
[Cache setup]
```

**Pros:**
- Zero maintenance overhead
- Users get working integrations
- Community can create packages

**Cons:**
- Worse developer experience
- No discoverability on Packagist
- Repeated boilerplate in every project

### Final Recommendation

**Build `displace/xai-laravel` first.** The Laravel ecosystem represents the largest opportunity for adoption, and the OpenAI Laravel package demonstrates clear market demand. Use learnings from Laravel to inform the Symfony bundle.

If resource-constrained, start with a documentation-only approach for Symfony while shipping the Laravel package, then build the Symfony bundle based on community feedback.
