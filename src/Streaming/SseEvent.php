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

/**
 * Represents a Server-Sent Event (SSE).
 *
 * An SSE event can contain an event type, data, ID, and retry interval.
 * For xAI API responses, the data field typically contains JSON-encoded
 * chat completion chunks.
 */
readonly class SseEvent
{
    /**
     * Creates a new SSE event.
     *
     * @param string|null $event The event type (e.g., 'message')
     * @param string $data The event data (typically JSON)
     * @param string|null $id The event ID
     * @param int|null $retry Reconnection time in milliseconds
     */
    public function __construct(
        public ?string $event = null,
        public string $data = '',
        public ?string $id = null,
        public ?int $retry = null,
    ) {
    }

    /**
     * Checks if this event signals the end of the stream.
     *
     * The xAI API uses "[DONE]" as the data field to signal stream completion.
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return trim($this->data) === '[DONE]';
    }

    /**
     * Gets the event data as a decoded JSON array.
     *
     * @return array<string, mixed>|null The decoded data, or null if parsing fails
     */
    public function getJson(): ?array
    {
        if ($this->isDone() || $this->data === '') {
            return null;
        }

        $decoded = json_decode($this->data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * Checks if the event has data.
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return $this->data !== '';
    }
}
