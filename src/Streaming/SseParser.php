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
 * Parser for Server-Sent Events (SSE) streams.
 *
 * This parser reads from a streaming response and yields individual SSE events.
 * It handles the SSE wire format including multi-line data fields, comments,
 * and proper event boundary detection.
 *
 * @see https://html.spec.whatwg.org/multipage/server-sent-events.html
 */
class SseParser
{
    private StreamingResponse $response;

    /**
     * Creates a new SSE parser.
     *
     * @param StreamingResponse $response The streaming response to parse
     */
    public function __construct(StreamingResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Creates a parser from a streaming response.
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
     * Parses the stream and yields SSE events.
     *
     * @return Generator<SseEvent>
     */
    public function parse(): Generator
    {
        $event = null;
        $data = [];
        $id = null;
        $retry = null;

        while (! $this->response->eof()) {
            $line = $this->response->readLine();

            // Null means EOF
            if ($line === null) {
                break;
            }

            // Empty line signals end of event
            if ($line === '') {
                if ($data !== []) {
                    yield new SseEvent(
                        event: $event,
                        data: implode("\n", $data),
                        id: $id,
                        retry: $retry,
                    );
                }

                // Reset for next event
                $event = null;
                $data = [];
                $id = null;
                $retry = null;
                continue;
            }

            // Skip comments (lines starting with colon)
            if (str_starts_with($line, ':')) {
                continue;
            }

            // Parse field: value
            $colonPos = strpos($line, ':');

            if ($colonPos === false) {
                // Line with no colon is treated as field name with empty value
                $field = $line;
                $value = '';
            } else {
                $field = substr($line, 0, $colonPos);
                $value = substr($line, $colonPos + 1);

                // Remove single leading space from value if present
                if (str_starts_with($value, ' ')) {
                    $value = substr($value, 1);
                }
            }

            match ($field) {
                'event' => $event = $value,
                'data' => $data[] = $value,
                'id' => $id = $value,
                'retry' => $retry = ctype_digit($value) ? (int) $value : null,
                default => null, // Ignore unknown fields
            };
        }

        // Yield final event if there's data without trailing newline
        if ($data !== []) {
            yield new SseEvent(
                event: $event,
                data: implode("\n", $data),
                id: $id,
                retry: $retry,
            );
        }
    }
}
