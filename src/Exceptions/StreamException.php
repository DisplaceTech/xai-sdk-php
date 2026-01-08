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

namespace Displace\XaiSdk\Exceptions;

use Throwable;

/**
 * Exception thrown for streaming-related errors.
 *
 * This exception is thrown when errors occur during streaming response
 * processing, such as malformed SSE data, JSON parse errors in chunks,
 * or unexpected stream termination.
 */
class StreamException extends XaiException
{
    /**
     * Creates a new StreamException instance.
     *
     * @param string $message The exception message
     * @param Throwable|null $previous The previous exception for chaining
     */
    public function __construct(
        string $message = 'A streaming error occurred.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Creates an exception for malformed SSE data.
     *
     * @param string $data The malformed data
     * @param Throwable|null $previous The previous exception
     * @return self
     */
    public static function malformedData(string $data, ?Throwable $previous = null): self
    {
        $preview = strlen($data) > 100 ? substr($data, 0, 100) . '...' : $data;
        return new self(
            sprintf('Malformed SSE data received: %s', $preview),
            $previous,
        );
    }

    /**
     * Creates an exception for JSON parse errors in stream chunks.
     *
     * @param string $json The JSON that failed to parse
     * @param Throwable|null $previous The JSON exception
     * @return self
     */
    public static function jsonParseError(string $json, ?Throwable $previous = null): self
    {
        $preview = strlen($json) > 100 ? substr($json, 0, 100) . '...' : $json;
        return new self(
            sprintf('Failed to parse JSON from stream: %s', $preview),
            $previous,
        );
    }

    /**
     * Creates an exception for unexpected stream termination.
     *
     * @param string|null $reason The reason for termination if known
     * @return self
     */
    public static function unexpectedTermination(?string $reason = null): self
    {
        $message = 'Stream terminated unexpectedly';
        if ($reason !== null) {
            $message .= ': ' . $reason;
        }
        return new self($message);
    }

    /**
     * Creates an exception for stream read errors.
     *
     * @param Throwable|null $previous The previous exception
     * @return self
     */
    public static function readError(?Throwable $previous = null): self
    {
        return new self('Error reading from stream', $previous);
    }
}
