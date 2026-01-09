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

namespace Displace\XaiSdk;

use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Http\HttpConfig;
use Displace\XaiSdk\Resources\ChatResource;
use Displace\XaiSdk\Resources\CollectionsResource;
use Displace\XaiSdk\Resources\FilesResource;
use Displace\XaiSdk\Resources\ImageResource;
use Displace\XaiSdk\Resources\ModelsResource;
use Displace\XaiSdk\Resources\ResponsesResource;
use Displace\XaiSdk\Resources\TokenizerResource;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Main client for the xAI API.
 *
 * This is the primary entry point for interacting with xAI's API. It provides
 * access to all API resources through property accessors.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\XaiClient;
 * use function Displace\XaiSdk\Chat\{system, user};
 *
 * // Create client (uses XAI_API_KEY environment variable)
 * $client = new XaiClient();
 *
 * // Or provide API key explicitly
 * $client = new XaiClient(apiKey: 'your-api-key');
 *
 * // Create a chat conversation
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     messages: [system('You are a helpful assistant.')]
 * );
 *
 * $chat->append(user('Hello!'));
 * $response = $chat->sample();
 * echo $response->getContent();
 * ```
 *
 * @property-read ChatResource $chat Access to chat completions API.
 * @property-read ResponsesResource $responses Access to responses API (for server-side tools).
 * @property-read ImageResource $image Access to image generation API.
 * @property-read ModelsResource $models Access to models API.
 * @property-read TokenizerResource $tokenize Access to tokenization API.
 * @property-read FilesResource $files Access to files API.
 * @property-read CollectionsResource $collections Access to collections API (RAG).
 */
class XaiClient
{
    private const DEFAULT_BASE_URL = 'https://api.x.ai/v1';

    private const DEFAULT_TIMEOUT = 300.0;

    private HttpClient $httpClient;

    private ?ChatResource $chatResource = null;

    private ?ResponsesResource $responsesResource = null;

    private ?ImageResource $imageResource = null;

    private ?ModelsResource $modelsResource = null;

    private ?TokenizerResource $tokenizerResource = null;

    private ?FilesResource $filesResource = null;

    private ?CollectionsResource $collectionsResource = null;

    /**
     * Creates a new xAI client.
     *
     * @param string|null $apiKey API key for authentication. If null, reads from XAI_API_KEY environment variable.
     * @param ClientInterface|null $httpClient PSR-18 HTTP client (defaults to Guzzle).
     * @param string $baseUrl Base URL for API requests.
     * @param float $timeout Request timeout in seconds.
     * @param array<string, string> $headers Additional headers to send with each request.
     *
     * @throws RuntimeException If no API key is available.
     */
    public function __construct(
        ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = self::DEFAULT_TIMEOUT,
        array $headers = [],
    ) {
        $key = $apiKey ?? $this->getApiKeyFromEnvironment();

        $this->httpClient = new HttpClient(
            apiKey: $key,
            client: $httpClient,
            baseUrl: $baseUrl,
            config: new HttpConfig(timeout: $timeout),
            headers: $headers,
        );
    }

    /**
     * Gets API resources via property access.
     *
     * @param string $name The resource name.
     *
     * @throws InvalidArgumentException If the property doesn't exist.
     *
     * @return ChatResource|ResponsesResource|ImageResource|ModelsResource|TokenizerResource|FilesResource|CollectionsResource
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'chat' => $this->getChatResource(),
            'responses' => $this->getResponsesResource(),
            'image' => $this->getImageResource(),
            'models' => $this->getModelsResource(),
            'tokenize' => $this->getTokenizerResource(),
            'files' => $this->getFilesResource(),
            'collections' => $this->getCollectionsResource(),
            default => throw new InvalidArgumentException(sprintf('Unknown property: %s', $name)),
        };
    }

    /**
     * Checks if a property exists.
     *
     * @param string $name The property name.
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return match ($name) {
            'chat', 'responses', 'image', 'models', 'tokenize', 'files', 'collections' => true,
            default => false,
        };
    }

    /**
     * Gets the underlying HTTP client.
     *
     * Useful for advanced usage or debugging.
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Gets the chat resource.
     *
     * @return ChatResource
     */
    private function getChatResource(): ChatResource
    {
        if ($this->chatResource === null) {
            $this->chatResource = new ChatResource($this->httpClient);
        }

        return $this->chatResource;
    }

    /**
     * Gets the responses resource.
     *
     * @return ResponsesResource
     */
    private function getResponsesResource(): ResponsesResource
    {
        if ($this->responsesResource === null) {
            $this->responsesResource = new ResponsesResource($this->httpClient);
        }

        return $this->responsesResource;
    }

    /**
     * Gets the image resource.
     *
     * @return ImageResource
     */
    private function getImageResource(): ImageResource
    {
        if ($this->imageResource === null) {
            $this->imageResource = new ImageResource($this->httpClient);
        }

        return $this->imageResource;
    }

    /**
     * Gets the models resource.
     *
     * @return ModelsResource
     */
    private function getModelsResource(): ModelsResource
    {
        if ($this->modelsResource === null) {
            $this->modelsResource = new ModelsResource($this->httpClient);
        }

        return $this->modelsResource;
    }

    /**
     * Gets the tokenizer resource.
     *
     * @return TokenizerResource
     */
    private function getTokenizerResource(): TokenizerResource
    {
        if ($this->tokenizerResource === null) {
            $this->tokenizerResource = new TokenizerResource($this->httpClient);
        }

        return $this->tokenizerResource;
    }

    /**
     * Gets the files resource.
     *
     * @return FilesResource
     */
    private function getFilesResource(): FilesResource
    {
        if ($this->filesResource === null) {
            $this->filesResource = new FilesResource($this->httpClient);
        }

        return $this->filesResource;
    }

    /**
     * Gets the collections resource.
     *
     * @return CollectionsResource
     */
    private function getCollectionsResource(): CollectionsResource
    {
        if ($this->collectionsResource === null) {
            $this->collectionsResource = new CollectionsResource($this->httpClient);
        }

        return $this->collectionsResource;
    }

    /**
     * Gets the API key from environment variable.
     *
     * @throws RuntimeException If the environment variable is not set.
     *
     * @return string
     */
    private function getApiKeyFromEnvironment(): string
    {
        $key = getenv('XAI_API_KEY');

        if ($key === false || $key === '') {
            throw new RuntimeException(
                'No API key provided. Set the XAI_API_KEY environment variable ' .
                'or pass an apiKey parameter to the constructor.',
            );
        }

        return $key;
    }
}
