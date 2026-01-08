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
use Iterator;

/**
 * Iterator for streaming chat completion responses.
 *
 * This iterator wraps an SSE parser and yields decoded JSON chunks from
 * the streaming response. It implements PHP's Iterator interface for
 * use in foreach loops.
 *
 * @implements Iterator<int, array<string, mixed>>
 */
class StreamIterator implements Iterator
{
    private SseParser $parser;
    private \Generator $generator;

    /** @var array<string, mixed>|null */
    private ?array $current = null;
    private int $index = 0;
    private bool $valid = true;

    /**
     * Creates a new stream iterator.
     *
     * @param StreamingResponse $response The streaming response to iterate
     */
    public function __construct(StreamingResponse $response)
    {
        $this->parser = new SseParser($response);
        $this->generator = $this->createGenerator();
        $this->next(); // Initialize first element
    }

    /**
     * Creates a generator that yields JSON chunks from SSE events.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function createGenerator(): \Generator
    {
        foreach ($this->parser->parse() as $event) {
            // Skip [DONE] events
            if ($event->isDone()) {
                continue;
            }

            // Skip events without data
            if (!$event->hasData()) {
                continue;
            }

            // Parse JSON data
            $data = $event->getJson();
            if ($data !== null) {
                yield $data;
            }
        }
    }

    /**
     * Returns the current chunk data.
     *
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        return $this->current;
    }

    /**
     * Returns the current index.
     *
     * @return int
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * Advances to the next chunk.
     *
     * @return void
     */
    public function next(): void
    {
        if ($this->generator->valid()) {
            $this->current = $this->generator->current();
            $this->generator->next();
            $this->index++;
        } else {
            $this->valid = false;
            $this->current = null;
        }
    }

    /**
     * Rewinds the iterator.
     *
     * Note: Streaming responses cannot be rewound. This method resets
     * the index but does not restart the stream.
     *
     * @return void
     */
    public function rewind(): void
    {
        // Cannot rewind a stream, but reset index for interface compliance
        $this->index = 0;
    }

    /**
     * Checks if the current position is valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->valid && $this->current !== null;
    }

    /**
     * Creates a stream iterator from a streaming response.
     *
     * @param StreamingResponse $response The streaming response
     * @return self
     */
    public static function fromResponse(StreamingResponse $response): self
    {
        return new self($response);
    }

    /**
     * Collects all chunks into an array.
     *
     * Warning: This loads all chunks into memory. For large streams,
     * prefer iterating directly.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        $chunks = [];
        foreach ($this as $chunk) {
            $chunks[] = $chunk;
        }
        return $chunks;
    }
}
