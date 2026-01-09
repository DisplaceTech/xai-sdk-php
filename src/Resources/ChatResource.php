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

namespace Displace\XaiSdk\Resources;

use Displace\XaiSdk\Chat\Chat;
use Displace\XaiSdk\Chat\DeferredCompletion;
use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Exceptions\DeferredCompletionException;
use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Http\StreamingResponse;
use Displace\XaiSdk\Responses\ChatResponse;
use Displace\XaiSdk\Tools\ServerSideTool;
use Displace\XaiSdk\Tools\Tool;

/**
 * Resource for chat completions API.
 *
 * This class provides access to the chat completions endpoint, allowing you
 * to create chat conversations with various models and configurations.
 */
class ChatResource
{
    private const ENDPOINT = '/chat/completions';

    private const DEFERRED_ENDPOINT = '/chat/completions/deferred';

    /**
     * Creates a new ChatResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * Creates a new stateful chat conversation.
     *
     * This is the primary method for creating chat interactions. It returns a
     * Chat instance that manages the conversation state and allows multi-turn
     * interactions.
     *
     * @param string $model The model to use (e.g., 'grok-3', 'grok-3-latest').
     * @param array<Message> $messages Initial messages for the conversation.
     * @param string|null $user A unique identifier for the end-user.
     * @param int|null $maxTokens Maximum tokens to generate.
     * @param int|null $seed Random seed for deterministic sampling.
     * @param array<string>|null $stop Stop sequences.
     * @param float|null $temperature Sampling temperature (0-2).
     * @param float|null $topP Nucleus sampling probability.
     * @param bool|null $logprobs Whether to return log probabilities.
     * @param int|null $topLogprobs Number of top log probabilities to return.
     * @param array<Tool>|null $tools Tools the model may call.
     * @param string|array<string, mixed>|null $toolChoice Tool selection mode.
     * @param bool|null $parallelToolCalls Whether to allow parallel tool calls.
     * @param string|array<string, mixed>|null $responseFormat Response format specification.
     * @param float|null $frequencyPenalty Frequency penalty (-2.0 to 2.0).
     * @param float|null $presencePenalty Presence penalty (-2.0 to 2.0).
     * @param string|null $reasoningEffort Reasoning effort level ('low' or 'high').
     * @param array<string, mixed>|null $searchParameters Search parameters for web/X search.
     * @param bool|null $storeMessages Whether to store messages on xAI backend.
     * @param string|null $previousResponseId Previous response ID for conversation branching.
     * @param int|null $maxTurns Maximum agentic turns.
     *
     * @return Chat A new chat conversation instance.
     */
    public function create(
        string $model,
        array $messages = [],
        ?string $user = null,
        ?int $maxTokens = null,
        ?int $seed = null,
        ?array $stop = null,
        ?float $temperature = null,
        ?float $topP = null,
        ?bool $logprobs = null,
        ?int $topLogprobs = null,
        ?array $tools = null,
        string|array|null $toolChoice = null,
        ?bool $parallelToolCalls = null,
        string|array|null $responseFormat = null,
        ?float $frequencyPenalty = null,
        ?float $presencePenalty = null,
        ?string $reasoningEffort = null,
        ?array $searchParameters = null,
        ?bool $storeMessages = null,
        ?string $previousResponseId = null,
        ?int $maxTurns = null,
    ): Chat {
        return new Chat(
            resource: $this,
            model: $model,
            messages: $messages,
            user: $user,
            maxTokens: $maxTokens,
            seed: $seed,
            stop: $stop,
            temperature: $temperature,
            topP: $topP,
            logprobs: $logprobs,
            topLogprobs: $topLogprobs,
            tools: $tools,
            toolChoice: $toolChoice,
            parallelToolCalls: $parallelToolCalls,
            responseFormat: $responseFormat,
            frequencyPenalty: $frequencyPenalty,
            presencePenalty: $presencePenalty,
            reasoningEffort: $reasoningEffort,
            searchParameters: $searchParameters,
            storeMessages: $storeMessages,
            previousResponseId: $previousResponseId,
            maxTurns: $maxTurns,
        );
    }

    /**
     * Sends a chat completion request (non-streaming).
     *
     * This is typically called internally by the Chat class, but can be used
     * directly for one-off requests.
     *
     * @param array<string, mixed> $payload The request payload.
     *
     * @return ChatResponse The completion response.
     */
    public function complete(array $payload): ChatResponse
    {
        // Ensure stream is false for non-streaming requests
        $payload['stream'] = false;

        $response = $this->httpClient->post(self::ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return ChatResponse::fromArray($data);
    }

    /**
     * Sends a chat completion request with streaming.
     *
     * Returns a StreamingResponse that can be iterated to receive chunks.
     *
     * @param array<string, mixed> $payload The request payload.
     *
     * @return StreamingResponse The streaming response.
     */
    public function stream(array $payload): StreamingResponse
    {
        return $this->httpClient->postStream(self::ENDPOINT, $payload);
    }

    /**
     * Builds the request payload from configuration.
     *
     * @param string $model The model to use.
     * @param array<Message> $messages The conversation messages.
     * @param array<string, mixed> $options Additional options.
     *
     * @return array<string, mixed> The request payload.
     */
    public function buildPayload(string $model, array $messages, array $options = []): array
    {
        $payload = [
            'model' => $model,
            'messages' => array_map(fn (Message $m) => $m->toArray(), $messages),
        ];

        // Add optional parameters that are set
        $optionalParams = [
            'user',
            'max_tokens',
            'seed',
            'stop',
            'temperature',
            'top_p',
            'logprobs',
            'top_logprobs',
            'tool_choice',
            'parallel_tool_calls',
            'response_format',
            'frequency_penalty',
            'presence_penalty',
            'reasoning_effort',
            'search_parameters',
            'store_messages',
            'previous_response_id',
            'max_turns',
            'n',
        ];

        foreach ($optionalParams as $param) {
            if (isset($options[$param])) {
                $payload[$param] = $options[$param];
            }
        }

        // Handle tools separately - supports both Tool and ServerSideTool
        if (isset($options['tools']) && $options['tools'] !== []) {
            $payload['tools'] = array_map(
                fn (Tool|ServerSideTool $tool) => $tool->toArray(),
                $options['tools'],
            );
        }

        return $payload;
    }

    /**
     * Creates a deferred chat completion request.
     *
     * This is useful for long-running requests that might timeout with the
     * synchronous endpoint. The returned DeferredCompletion can be polled
     * to check for completion.
     *
     * @param array<string, mixed> $payload The request payload.
     *
     * @return DeferredCompletion The deferred completion handle.
     */
    public function createDeferred(array $payload): DeferredCompletion
    {
        // Deferred requests don't support streaming
        unset($payload['stream']);

        $response = $this->httpClient->post(self::DEFERRED_ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return DeferredCompletion::fromArray($data);
    }

    /**
     * Retrieves the status of a deferred completion.
     *
     * @param string $id The deferred completion ID.
     *
     * @return DeferredCompletion The current status of the deferred completion.
     */
    public function retrieveDeferred(string $id): DeferredCompletion
    {
        $response = $this->httpClient->get(self::DEFERRED_ENDPOINT . '/' . $id);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return DeferredCompletion::fromArray($data);
    }

    /**
     * Waits for a deferred completion to finish.
     *
     * This method polls the deferred completion until it is finished
     * (completed, failed, or cancelled) or until the timeout is reached.
     *
     * @param string $id The deferred completion ID.
     * @param int $timeout Maximum time to wait in seconds (default: 300).
     * @param int $pollInterval Time between polls in seconds (default: 5).
     *
     * @throws DeferredCompletionException If the completion fails, is cancelled, or times out.
     *
     * @return ChatResponse The completed chat response.
     */
    public function awaitDeferred(
        string $id,
        int $timeout = 300,
        int $pollInterval = 5,
    ): ChatResponse {
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            $deferred = $this->retrieveDeferred($id);

            if ($deferred->isCompleted()) {
                $result = $deferred->getResult();

                if ($result === null) {
                    throw DeferredCompletionException::resultMissing($id);
                }

                return $result;
            }

            if ($deferred->isFailed()) {
                throw DeferredCompletionException::failed($id, $deferred->getError());
            }

            if ($deferred->isCancelled()) {
                throw DeferredCompletionException::cancelled($id);
            }

            // Wait before next poll
            sleep($pollInterval);
        }

        throw DeferredCompletionException::timeout($id, $timeout);
    }
}
