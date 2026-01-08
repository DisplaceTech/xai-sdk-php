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

namespace Displace\XaiSdk\Chat;

use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Exceptions\InvalidArgumentException;
use Displace\XaiSdk\Resources\ChatResource;
use Displace\XaiSdk\Responses\ChatResponse;
use Displace\XaiSdk\Responses\StreamChunk;
use Displace\XaiSdk\Streaming\SseParser;
use Displace\XaiSdk\Tools\Tool;
use Generator;

/**
 * Stateful chat conversation manager.
 *
 * This class manages a conversation with an xAI model, tracking message history
 * and providing methods for sampling responses. It mirrors the Python SDK's
 * chat interface, allowing multi-turn conversations with append/sample pattern.
 *
 * @example
 * ```php
 * use function Displace\XaiSdk\Chat\{system, user};
 *
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     messages: [system('You talk like a pirate.')]
 * );
 *
 * $chat->append(user('How are you?'));
 * $response = $chat->sample();
 * echo $response->getContent();
 * $chat->append($response);
 * ```
 */
class Chat
{
    /** @var array<Message> */
    private array $messages;

    /** @var array<Tool>|null */
    private ?array $tools;

    /**
     * Creates a new Chat instance.
     *
     * @param ChatResource $resource The chat resource for API calls.
     * @param string $model The model to use.
     * @param array<Message> $messages Initial messages.
     * @param string|null $user User identifier.
     * @param int|null $maxTokens Maximum tokens to generate.
     * @param int|null $seed Random seed.
     * @param array<string>|null $stop Stop sequences.
     * @param float|null $temperature Sampling temperature.
     * @param float|null $topP Nucleus sampling probability.
     * @param bool|null $logprobs Return log probabilities.
     * @param int|null $topLogprobs Number of top log probabilities.
     * @param array<Tool>|null $tools Available tools.
     * @param string|array<string, mixed>|null $toolChoice Tool selection mode.
     * @param bool|null $parallelToolCalls Allow parallel tool calls.
     * @param string|array<string, mixed>|null $responseFormat Response format.
     * @param float|null $frequencyPenalty Frequency penalty.
     * @param float|null $presencePenalty Presence penalty.
     * @param string|null $reasoningEffort Reasoning effort level.
     * @param array<string, mixed>|null $searchParameters Search parameters.
     * @param bool|null $storeMessages Store messages on backend.
     * @param string|null $previousResponseId Previous response ID.
     * @param int|null $maxTurns Maximum agentic turns.
     */
    public function __construct(
        private readonly ChatResource $resource,
        private readonly string $model,
        array $messages = [],
        private readonly ?string $user = null,
        private readonly ?int $maxTokens = null,
        private readonly ?int $seed = null,
        private readonly ?array $stop = null,
        private readonly ?float $temperature = null,
        private readonly ?float $topP = null,
        private readonly ?bool $logprobs = null,
        private readonly ?int $topLogprobs = null,
        ?array $tools = null,
        private readonly string|array|null $toolChoice = null,
        private readonly ?bool $parallelToolCalls = null,
        private readonly string|array|null $responseFormat = null,
        private readonly ?float $frequencyPenalty = null,
        private readonly ?float $presencePenalty = null,
        private readonly ?string $reasoningEffort = null,
        private readonly ?array $searchParameters = null,
        private readonly ?bool $storeMessages = null,
        private readonly ?string $previousResponseId = null,
        private readonly ?int $maxTurns = null,
    ) {
        $this->messages = $messages;
        $this->tools = $tools;
    }

    /**
     * Appends a message to the conversation history.
     *
     * This method adds a message to the chat's message sequence. It accepts
     * Message objects (from helper functions like user(), system(), assistant())
     * or ChatResponse objects from previous interactions.
     *
     * @param Message|ChatResponse $message The message or response to append.
     *
     * @throws InvalidArgumentException If the message type is not recognized.
     *
     * @return $this For method chaining.
     */
    public function append(Message|ChatResponse $message): self
    {
        if ($message instanceof ChatResponse) {
            $this->messages[] = $message->toMessage();
        } else {
            $this->messages[] = $message;
        }

        return $this;
    }

    /**
     * Samples a response from the model.
     *
     * Sends the current conversation to the API and returns the model's response.
     * The response is NOT automatically added to the conversation history;
     * call append() to add it.
     *
     * @param int $n Number of completions to generate (default 1).
     *
     * @throws InvalidArgumentException If no messages are in the conversation.
     *
     * @return ChatResponse The model's response.
     */
    public function sample(int $n = 1): ChatResponse
    {
        if ($this->messages === []) {
            throw new InvalidArgumentException(
                'Cannot sample: No messages in conversation. ' .
                'Add messages using append() before calling sample().',
            );
        }

        $payload = $this->buildPayload();

        if ($n > 1) {
            $payload['n'] = $n;
        }

        return $this->resource->complete($payload);
    }

    /**
     * Samples multiple responses from the model.
     *
     * Convenience method for sampling multiple completions at once.
     *
     * @param int $n Number of completions to generate.
     *
     * @return array<ChatResponse> Array of responses (one per choice).
     */
    public function sampleBatch(int $n): array
    {
        $response = $this->sample($n);

        // Create a response wrapper for each choice
        return array_map(
            fn (int $index) => new ChatResponse(
                id: $response->id,
                model: $response->model,
                created: $response->created,
                choices: [$response->choices[$index]],
                usage: $response->usage,
                systemFingerprint: $response->systemFingerprint,
            ),
            array_keys($response->choices),
        );
    }

    /**
     * Streams a response from the model.
     *
     * Returns a generator that yields (ChatResponse, StreamChunk) tuples.
     * The ChatResponse accumulates as streaming progresses, while StreamChunk
     * contains only the delta for that iteration.
     *
     * @throws InvalidArgumentException If no messages are in the conversation.
     *
     * @return Generator<int, array{ChatResponse, StreamChunk}>
     */
    public function stream(): Generator
    {
        if ($this->messages === []) {
            throw new InvalidArgumentException(
                'Cannot stream: No messages in conversation. ' .
                'Add messages using append() before calling stream().',
            );
        }

        $payload = $this->buildPayload();
        $streamingResponse = $this->resource->stream($payload);

        $parser = new SseParser($streamingResponse);
        $accumulatedContent = '';
        $accumulatedReasoningContent = '';
        $toolCalls = [];
        $lastResponse = null;

        foreach ($parser->parse() as $event) {
            if ($event->data === '[DONE]') {
                break;
            }

            $data = json_decode($event->data, true, 512, JSON_THROW_ON_ERROR);
            $chunk = StreamChunk::fromArray($data);

            // Accumulate content
            $accumulatedContent .= $chunk->content;

            if ($chunk->reasoningContent !== null) {
                $accumulatedReasoningContent .= $chunk->reasoningContent;
            }

            // Accumulate tool calls
            if ($chunk->toolCalls !== []) {
                foreach ($chunk->toolCalls as $toolCallDelta) {
                    $index = $toolCallDelta['index'] ?? 0;

                    if (! isset($toolCalls[$index])) {
                        $toolCalls[$index] = [
                            'id' => $toolCallDelta['id'] ?? '',
                            'type' => 'function',
                            'function' => [
                                'name' => '',
                                'arguments' => '',
                            ],
                        ];
                    }

                    if (isset($toolCallDelta['id'])) {
                        $toolCalls[$index]['id'] = $toolCallDelta['id'];
                    }

                    if (isset($toolCallDelta['function']['name'])) {
                        $toolCalls[$index]['function']['name'] .= $toolCallDelta['function']['name'];
                    }

                    if (isset($toolCallDelta['function']['arguments'])) {
                        $toolCalls[$index]['function']['arguments'] .= $toolCallDelta['function']['arguments'];
                    }
                }
            }

            // Build accumulated response
            $lastResponse = $this->buildAccumulatedResponse(
                $data,
                $accumulatedContent,
                $accumulatedReasoningContent,
                array_values($toolCalls),
                $chunk->finishReason,
            );

            yield [$lastResponse, $chunk];
        }

        $streamingResponse->close();
    }

    /**
     * Gets the current messages in the conversation.
     *
     * @return array<Message>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Gets the model being used.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Builds the request payload for the API.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        $options = [];

        if ($this->user !== null) {
            $options['user'] = $this->user;
        }

        if ($this->maxTokens !== null) {
            $options['max_tokens'] = $this->maxTokens;
        }

        if ($this->seed !== null) {
            $options['seed'] = $this->seed;
        }

        if ($this->stop !== null) {
            $options['stop'] = $this->stop;
        }

        if ($this->temperature !== null) {
            $options['temperature'] = $this->temperature;
        }

        if ($this->topP !== null) {
            $options['top_p'] = $this->topP;
        }

        if ($this->logprobs !== null) {
            $options['logprobs'] = $this->logprobs;
        }

        if ($this->topLogprobs !== null) {
            $options['top_logprobs'] = $this->topLogprobs;
        }

        if ($this->tools !== null) {
            $options['tools'] = $this->tools;
        }

        if ($this->toolChoice !== null) {
            $options['tool_choice'] = $this->toolChoice;
        }

        if ($this->parallelToolCalls !== null) {
            $options['parallel_tool_calls'] = $this->parallelToolCalls;
        }

        if ($this->responseFormat !== null) {
            $options['response_format'] = $this->responseFormat;
        }

        if ($this->frequencyPenalty !== null) {
            $options['frequency_penalty'] = $this->frequencyPenalty;
        }

        if ($this->presencePenalty !== null) {
            $options['presence_penalty'] = $this->presencePenalty;
        }

        if ($this->reasoningEffort !== null) {
            $options['reasoning_effort'] = $this->reasoningEffort;
        }

        if ($this->searchParameters !== null) {
            $options['search_parameters'] = $this->searchParameters;
        }

        if ($this->storeMessages !== null) {
            $options['store_messages'] = $this->storeMessages;
        }

        if ($this->previousResponseId !== null) {
            $options['previous_response_id'] = $this->previousResponseId;
        }

        if ($this->maxTurns !== null) {
            $options['max_turns'] = $this->maxTurns;
        }

        return $this->resource->buildPayload($this->model, $this->messages, $options);
    }

    /**
     * Builds an accumulated ChatResponse from stream data.
     *
     * @param array<string, mixed> $data The latest chunk data.
     * @param string $content Accumulated content.
     * @param string $reasoningContent Accumulated reasoning content.
     * @param array<array<string, mixed>> $toolCalls Accumulated tool calls.
     * @param string|null $finishReason The finish reason if complete.
     *
     * @return ChatResponse
     */
    private function buildAccumulatedResponse(
        array $data,
        string $content,
        string $reasoningContent,
        array $toolCalls,
        ?string $finishReason,
    ): ChatResponse {
        $choice = [
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => $content,
            ],
            'finish_reason' => $finishReason,
        ];

        if ($reasoningContent !== '') {
            $choice['message']['reasoning_content'] = $reasoningContent;
        }

        if ($toolCalls !== []) {
            $choice['message']['tool_calls'] = $toolCalls;
        }

        return ChatResponse::fromArray([
            'id' => $data['id'] ?? '',
            'model' => $data['model'] ?? $this->model,
            'created' => $data['created'] ?? time(),
            'choices' => [$choice],
            'usage' => $data['usage'] ?? [],
            'system_fingerprint' => $data['system_fingerprint'] ?? null,
        ]);
    }
}
