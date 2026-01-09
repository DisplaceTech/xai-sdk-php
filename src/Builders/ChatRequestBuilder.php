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

namespace Displace\XaiSdk\Builders;

use Displace\XaiSdk\Chat\Chat;
use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Chat\Messages\Content;
use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Chat\Messages\SystemMessage;
use Displace\XaiSdk\Chat\Messages\UserMessage;
use Displace\XaiSdk\Exceptions\InvalidArgumentException;
use Displace\XaiSdk\Responses\ChatResponse;
use Displace\XaiSdk\Tools\Tool;
use Displace\XaiSdk\Tools\ToolChoice;
use Displace\XaiSdk\XaiClient;
use Generator;

/**
 * Fluent builder for constructing chat requests.
 *
 * Provides a more discoverable API for building complex chat requests
 * with many optional parameters.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Builders\ChatRequestBuilder;
 *
 * $response = ChatRequestBuilder::create($client)
 *     ->model('grok-3')
 *     ->systemPrompt('You are a helpful assistant')
 *     ->userMessage('Hello!')
 *     ->temperature(0.7)
 *     ->maxTokens(1000)
 *     ->send();
 * ```
 */
final class ChatRequestBuilder
{
    private ?string $model = null;

    /** @var array<Message> */
    private array $messages = [];

    private ?string $user = null;

    private ?int $maxTokens = null;

    private ?int $seed = null;

    /** @var array<string>|null */
    private ?array $stop = null;

    private ?float $temperature = null;

    private ?float $topP = null;

    private ?bool $logprobs = null;

    private ?int $topLogprobs = null;

    /** @var array<Tool>|null */
    private ?array $tools = null;

    /** @var string|array<string, mixed>|null */
    private string|array|null $toolChoice = null;

    private ?bool $parallelToolCalls = null;

    /** @var string|array<string, mixed>|null */
    private string|array|null $responseFormat = null;

    private ?float $frequencyPenalty = null;

    private ?float $presencePenalty = null;

    private ?string $reasoningEffort = null;

    /** @var array<string, mixed>|null */
    private ?array $searchParameters = null;

    private ?bool $storeMessages = null;

    private ?string $previousResponseId = null;

    private ?int $maxTurns = null;

    /**
     * Creates a new ChatRequestBuilder.
     *
     * @param XaiClient $client The xAI client.
     */
    private function __construct(
        private readonly XaiClient $client,
    ) {
    }

    /**
     * Creates a new builder instance.
     *
     * @param XaiClient $client The xAI client.
     *
     * @return self
     */
    public static function create(XaiClient $client): self
    {
        return new self($client);
    }

    /**
     * Sets the model to use.
     *
     * @param string $model The model name (e.g., 'grok-3', 'grok-3-latest').
     *
     * @return $this
     */
    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Sets the system prompt.
     *
     * This creates a system message at the beginning of the conversation.
     *
     * @param string $content The system prompt content.
     *
     * @return $this
     */
    public function systemPrompt(string $content): self
    {
        // Remove any existing system messages and prepend the new one
        $this->messages = array_filter(
            $this->messages,
            fn (Message $m) => $m->getRole() !== 'system',
        );
        array_unshift($this->messages, new SystemMessage([Content::text($content)]));

        return $this;
    }

    /**
     * Adds a user message.
     *
     * @param string $content The user message content.
     *
     * @return $this
     */
    public function userMessage(string $content): self
    {
        $this->messages[] = new UserMessage([Content::text($content)]);

        return $this;
    }

    /**
     * Adds a user message with images.
     *
     * @param string $text The text content.
     * @param array<string> $imageUrls URLs of images to include.
     * @param string $detail Image detail level ('auto', 'low', 'high').
     *
     * @return $this
     */
    public function userMessageWithImages(string $text, array $imageUrls, string $detail = 'auto'): self
    {
        $contentParts = [Content::text($text)];

        foreach ($imageUrls as $url) {
            $contentParts[] = Content::imageUrl($url, $detail);
        }

        $this->messages[] = new UserMessage($contentParts);

        return $this;
    }

    /**
     * Adds an assistant message.
     *
     * Useful for few-shot examples or seeding the conversation.
     *
     * @param string $content The assistant's response content.
     *
     * @return $this
     */
    public function assistantMessage(string $content): self
    {
        $this->messages[] = new AssistantMessage($content);

        return $this;
    }

    /**
     * Adds a raw message.
     *
     * @param Message $message The message to add.
     *
     * @return $this
     */
    public function message(Message $message): self
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Adds multiple messages.
     *
     * @param array<Message> $messages The messages to add.
     *
     * @return $this
     */
    public function messages(array $messages): self
    {
        foreach ($messages as $message) {
            $this->messages[] = $message;
        }

        return $this;
    }

    /**
     * Sets the user identifier.
     *
     * @param string $user A unique identifier for the end-user.
     *
     * @return $this
     */
    public function user(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Sets the maximum tokens to generate.
     *
     * @param int $maxTokens Maximum number of tokens.
     *
     * @return $this
     */
    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * Sets the random seed for deterministic sampling.
     *
     * @param int $seed The random seed.
     *
     * @return $this
     */
    public function seed(int $seed): self
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Sets stop sequences.
     *
     * @param array<string> $sequences Sequences that cause generation to stop.
     *
     * @return $this
     */
    public function stop(array $sequences): self
    {
        $this->stop = $sequences;

        return $this;
    }

    /**
     * Sets the sampling temperature.
     *
     * Higher values (e.g., 1.0) make output more random, lower values (e.g., 0.2)
     * make it more focused and deterministic.
     *
     * @param float $temperature Temperature between 0 and 2.
     *
     * @return $this
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Sets the nucleus sampling probability.
     *
     * @param float $topP Probability threshold (0 to 1).
     *
     * @return $this
     */
    public function topP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    /**
     * Enables log probability output.
     *
     * @param bool $enabled Whether to include log probabilities.
     * @param int|null $topLogprobs Number of top log probabilities to return.
     *
     * @return $this
     */
    public function withLogprobs(bool $enabled = true, ?int $topLogprobs = null): self
    {
        $this->logprobs = $enabled;
        $this->topLogprobs = $topLogprobs;

        return $this;
    }

    /**
     * Adds a tool the model can call.
     *
     * @param Tool $tool The tool to add.
     *
     * @return $this
     */
    public function withTool(Tool $tool): self
    {
        if ($this->tools === null) {
            $this->tools = [];
        }

        $this->tools[] = $tool;

        return $this;
    }

    /**
     * Adds multiple tools.
     *
     * @param array<Tool> $tools The tools to add.
     *
     * @return $this
     */
    public function withTools(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->withTool($tool);
        }

        return $this;
    }

    /**
     * Sets the tool choice mode.
     *
     * @param string|ToolChoice $choice The tool choice ('auto', 'none', 'required', or specific tool).
     *
     * @return $this
     */
    public function toolChoice(string|ToolChoice $choice): self
    {
        if ($choice instanceof ToolChoice) {
            $this->toolChoice = $choice->toArray();
        } else {
            $this->toolChoice = $choice;
        }

        return $this;
    }

    /**
     * Enables or disables parallel tool calls.
     *
     * @param bool $enabled Whether to allow parallel tool calls.
     *
     * @return $this
     */
    public function parallelToolCalls(bool $enabled = true): self
    {
        $this->parallelToolCalls = $enabled;

        return $this;
    }

    /**
     * Sets the response format to JSON.
     *
     * @return $this
     */
    public function jsonResponse(): self
    {
        $this->responseFormat = ['type' => 'json_object'];

        return $this;
    }

    /**
     * Sets the response format with a JSON schema.
     *
     * @param array<string, mixed> $schema The JSON schema.
     * @param string|null $name Optional name for the schema.
     *
     * @return $this
     */
    public function jsonSchema(array $schema, ?string $name = null): self
    {
        $format = [
            'type' => 'json_schema',
            'json_schema' => [
                'schema' => $schema,
            ],
        ];

        if ($name !== null) {
            $format['json_schema']['name'] = $name;
        }

        $this->responseFormat = $format;

        return $this;
    }

    /**
     * Sets a custom response format.
     *
     * @param string|array<string, mixed> $format The response format.
     *
     * @return $this
     */
    public function responseFormat(string|array $format): self
    {
        $this->responseFormat = $format;

        return $this;
    }

    /**
     * Sets the frequency penalty.
     *
     * Positive values penalize new tokens based on their frequency in the text so far.
     *
     * @param float $penalty Penalty between -2.0 and 2.0.
     *
     * @return $this
     */
    public function frequencyPenalty(float $penalty): self
    {
        $this->frequencyPenalty = $penalty;

        return $this;
    }

    /**
     * Sets the presence penalty.
     *
     * Positive values penalize new tokens based on whether they appear in the text so far.
     *
     * @param float $penalty Penalty between -2.0 and 2.0.
     *
     * @return $this
     */
    public function presencePenalty(float $penalty): self
    {
        $this->presencePenalty = $penalty;

        return $this;
    }

    /**
     * Sets the reasoning effort level.
     *
     * @param string $effort Either 'low' or 'high'.
     *
     * @return $this
     */
    public function reasoningEffort(string $effort): self
    {
        $this->reasoningEffort = $effort;

        return $this;
    }

    /**
     * Configures web/X search parameters.
     *
     * @param array<string, mixed> $parameters The search parameters.
     *
     * @return $this
     */
    public function searchParameters(array $parameters): self
    {
        $this->searchParameters = $parameters;

        return $this;
    }

    /**
     * Sets whether to store messages on the xAI backend.
     *
     * @param bool $store Whether to store messages.
     *
     * @return $this
     */
    public function storeMessages(bool $store = true): self
    {
        $this->storeMessages = $store;

        return $this;
    }

    /**
     * Sets the previous response ID for conversation branching.
     *
     * @param string $responseId The previous response ID.
     *
     * @return $this
     */
    public function previousResponseId(string $responseId): self
    {
        $this->previousResponseId = $responseId;

        return $this;
    }

    /**
     * Sets the maximum agentic turns.
     *
     * @param int $maxTurns Maximum number of turns.
     *
     * @return $this
     */
    public function maxTurns(int $maxTurns): self
    {
        $this->maxTurns = $maxTurns;

        return $this;
    }

    /**
     * Builds and returns the Chat instance.
     *
     * @throws InvalidArgumentException If model is not set.
     *
     * @return Chat The configured chat instance.
     */
    public function build(): Chat
    {
        if ($this->model === null) {
            throw new InvalidArgumentException(
                'Model is required. Call model() before building.',
            );
        }

        return $this->client->chat->create(
            model: $this->model,
            messages: $this->messages,
            user: $this->user,
            maxTokens: $this->maxTokens,
            seed: $this->seed,
            stop: $this->stop,
            temperature: $this->temperature,
            topP: $this->topP,
            logprobs: $this->logprobs,
            topLogprobs: $this->topLogprobs,
            tools: $this->tools,
            toolChoice: $this->toolChoice,
            parallelToolCalls: $this->parallelToolCalls,
            responseFormat: $this->responseFormat,
            frequencyPenalty: $this->frequencyPenalty,
            presencePenalty: $this->presencePenalty,
            reasoningEffort: $this->reasoningEffort,
            searchParameters: $this->searchParameters,
            storeMessages: $this->storeMessages,
            previousResponseId: $this->previousResponseId,
            maxTurns: $this->maxTurns,
        );
    }

    /**
     * Sends the request and returns the response.
     *
     * This is a convenience method that builds the chat and immediately samples.
     *
     * @throws InvalidArgumentException If model is not set or no messages provided.
     *
     * @return ChatResponse The chat response.
     */
    public function send(): ChatResponse
    {
        return $this->build()->sample();
    }

    /**
     * Sends the request and streams the response.
     *
     * @throws InvalidArgumentException If model is not set or no messages provided.
     *
     * @return Generator<int, array{ChatResponse, \Displace\XaiSdk\Responses\StreamChunk}>
     */
    public function stream(): Generator
    {
        return $this->build()->stream();
    }

    /**
     * Resets the builder to its initial state.
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->model = null;
        $this->messages = [];
        $this->user = null;
        $this->maxTokens = null;
        $this->seed = null;
        $this->stop = null;
        $this->temperature = null;
        $this->topP = null;
        $this->logprobs = null;
        $this->topLogprobs = null;
        $this->tools = null;
        $this->toolChoice = null;
        $this->parallelToolCalls = null;
        $this->responseFormat = null;
        $this->frequencyPenalty = null;
        $this->presencePenalty = null;
        $this->reasoningEffort = null;
        $this->searchParameters = null;
        $this->storeMessages = null;
        $this->previousResponseId = null;
        $this->maxTurns = null;

        return $this;
    }
}
