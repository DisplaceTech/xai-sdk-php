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

namespace Displace\XaiSdk\Streaming;

use Displace\XaiSdk\Http\StreamingResponse;
use Generator;

/**
 * Handler for streaming chat completion responses.
 *
 * This class provides a high-level interface for processing streaming
 * responses, including accumulating content, tracking token usage,
 * and handling tool calls.
 */
class StreamHandler
{
    private StreamingResponse $response;

    private StreamIterator $iterator;

    /** @var array<int, array<string, mixed>> */
    private array $chunks = [];

    private string $accumulatedContent = '';

    private string $accumulatedReasoningContent = '';

    /** @var array<string, mixed>|null */
    private ?array $usage = null;

    /** @var array<array<string, mixed>> */
    private array $toolCalls = [];

    private ?string $finishReason = null;

    private ?string $responseId = null;

    private ?string $model = null;

    /**
     * Creates a new stream handler.
     *
     * @param StreamingResponse $response The streaming response to handle
     */
    public function __construct(StreamingResponse $response)
    {
        $this->response = $response;
        $this->iterator = new StreamIterator($response);
    }

    /**
     * Creates a stream handler from a streaming response.
     *
     * @param StreamingResponse $response The streaming response
     *
     * @return self
     */
    public static function fromResponse(StreamingResponse $response): self
    {
        return new self($response);
    }

    /**
     * Iterates over chunks, yielding each chunk and accumulating content.
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function stream(): Generator
    {
        foreach ($this->iterator as $index => $chunk) {
            $this->processChunk($chunk);
            yield $index => $chunk;
        }
    }

    /**
     * Consumes the entire stream and returns accumulated data.
     *
     * @return array<string, mixed> The accumulated response data
     */
    public function consume(): array
    {
        foreach ($this->stream() as $chunk) {
            // Just iterate to accumulate
        }

        return $this->getAccumulatedData();
    }

    /**
     * Gets the accumulated content from all chunks.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->accumulatedContent;
    }

    /**
     * Gets the accumulated reasoning content from all chunks.
     *
     * @return string
     */
    public function getReasoningContent(): string
    {
        return $this->accumulatedReasoningContent;
    }

    /**
     * Gets the usage statistics.
     *
     * @return array<string, mixed>|null
     */
    public function getUsage(): ?array
    {
        return $this->usage;
    }

    /**
     * Gets all tool calls from the stream.
     *
     * @return array<array<string, mixed>>
     */
    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    /**
     * Gets the finish reason.
     *
     * @return string|null
     */
    public function getFinishReason(): ?string
    {
        return $this->finishReason;
    }

    /**
     * Gets the response ID.
     *
     * @return string|null
     */
    public function getResponseId(): ?string
    {
        return $this->responseId;
    }

    /**
     * Gets the model name.
     *
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Gets all accumulated data as an array.
     *
     * @return array<string, mixed>
     */
    public function getAccumulatedData(): array
    {
        return [
            'id' => $this->responseId,
            'model' => $this->model,
            'content' => $this->accumulatedContent,
            'reasoning_content' => $this->accumulatedReasoningContent,
            'tool_calls' => $this->toolCalls,
            'finish_reason' => $this->finishReason,
            'usage' => $this->usage,
        ];
    }

    /**
     * Processes a chunk and accumulates its data.
     *
     * @param array<string, mixed> $chunk The chunk to process
     */
    private function processChunk(array $chunk): void
    {
        $this->chunks[] = $chunk;

        // Extract response-level data
        if (isset($chunk['id'])) {
            $this->responseId = $chunk['id'];
        }

        if (isset($chunk['model'])) {
            $this->model = $chunk['model'];
        }

        // Process choices
        if (isset($chunk['choices']) && is_array($chunk['choices'])) {
            foreach ($chunk['choices'] as $choice) {
                $this->processChoice($choice);
            }
        }

        // Update usage if present (typically only in final chunk)
        if (isset($chunk['usage']) && is_array($chunk['usage'])) {
            $this->usage = $chunk['usage'];
        }
    }

    /**
     * Processes a choice from a chunk.
     *
     * @param array<string, mixed> $choice The choice to process
     */
    private function processChoice(array $choice): void
    {
        // Extract delta content
        if (isset($choice['delta']) && is_array($choice['delta'])) {
            $delta = $choice['delta'];

            // Accumulate content
            if (isset($delta['content'])) {
                $this->accumulatedContent .= $delta['content'];
            }

            // Accumulate reasoning content
            if (isset($delta['reasoning_content'])) {
                $this->accumulatedReasoningContent .= $delta['reasoning_content'];
            }

            // Process tool calls
            if (isset($delta['tool_calls']) && is_array($delta['tool_calls'])) {
                $this->processToolCallDeltas($delta['tool_calls']);
            }
        }

        // Track finish reason
        if (isset($choice['finish_reason']) && $choice['finish_reason'] !== null) {
            $this->finishReason = $choice['finish_reason'];
        }
    }

    /**
     * Processes tool call deltas from a chunk.
     *
     * Tool calls may be split across multiple chunks. This method
     * accumulates the tool call arguments.
     *
     * @param array<array<string, mixed>> $toolCallDeltas The tool call deltas
     */
    private function processToolCallDeltas(array $toolCallDeltas): void
    {
        foreach ($toolCallDeltas as $delta) {
            $index = $delta['index'] ?? 0;

            // Initialize tool call if first time seeing this index
            if (! isset($this->toolCalls[$index])) {
                $this->toolCalls[$index] = [
                    'id' => $delta['id'] ?? '',
                    'type' => $delta['type'] ?? 'function',
                    'function' => [
                        'name' => '',
                        'arguments' => '',
                    ],
                ];
            }

            // Update tool call ID if present
            if (isset($delta['id'])) {
                $this->toolCalls[$index]['id'] = $delta['id'];
            }

            // Update function details
            if (isset($delta['function']) && is_array($delta['function'])) {
                $function = $delta['function'];

                if (isset($function['name'])) {
                    $this->toolCalls[$index]['function']['name'] .= $function['name'];
                }

                if (isset($function['arguments'])) {
                    $this->toolCalls[$index]['function']['arguments'] .= $function['arguments'];
                }
            }
        }
    }
}
