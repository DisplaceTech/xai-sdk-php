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

namespace Displace\XaiSdk\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * Wrapper for streaming HTTP responses.
 *
 * This class provides access to the underlying PSR-7 response and stream
 * for processing Server-Sent Events (SSE) or other streaming content.
 * It handles proper resource cleanup when the response is no longer needed.
 */
final class StreamingResponse
{
    private ResponseInterface $response;

    private StreamInterface $stream;

    private bool $closed = false;

    /**
     * Creates a new StreamingResponse instance.
     *
     * @param ResponseInterface $response The PSR-7 response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $this->stream = $response->getBody();
    }

    /**
     * Destructor ensures stream resources are properly released.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Gets the underlying PSR-7 response.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Gets the response stream.
     *
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Gets the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Gets a response header value.
     *
     * @param string $name The header name
     *
     * @return string The header value, or empty string if not present
     */
    public function getHeader(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * Checks if the stream has been closed.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Checks if the stream has reached its end.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return $this->closed || $this->stream->eof();
    }

    /**
     * Reads bytes from the stream.
     *
     * @param int $length Number of bytes to read
     *
     * @return string
     */
    public function read(int $length): string
    {
        if ($this->closed) {
            return '';
        }

        return $this->stream->read($length);
    }

    /**
     * Reads a single line from the stream.
     *
     * This method reads until a newline character is encountered.
     * Useful for parsing SSE streams line by line.
     *
     * @param int $maxLength Maximum bytes to read
     *
     * @return string|null The line content, or null if EOF
     */
    public function readLine(int $maxLength = 4096): ?string
    {
        if ($this->closed || $this->stream->eof()) {
            return null;
        }

        $line = '';
        $bytesRead = 0;

        while (! $this->stream->eof() && $bytesRead < $maxLength) {
            $char = $this->stream->read(1);

            if ($char === '') {
                break;
            }

            $bytesRead++;

            if ($char === "\n") {
                break;
            }

            $line .= $char;
        }

        // Remove trailing carriage return if present (for CRLF line endings)
        return rtrim($line, "\r");
    }

    /**
     * Gets the content type of the response.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->response->getHeaderLine('Content-Type');
    }

    /**
     * Checks if this is a Server-Sent Events stream.
     *
     * @return bool
     */
    public function isEventStream(): bool
    {
        $contentType = $this->getContentType();

        return str_contains($contentType, 'text/event-stream');
    }

    /**
     * Detaches the stream from this wrapper.
     *
     * After calling this method, the stream will no longer be managed
     * by this class and will not be closed on destruction.
     *
     * @return StreamInterface The detached stream
     */
    public function detach(): StreamInterface
    {
        $this->closed = true;

        return $this->stream;
    }

    /**
     * Closes the stream and releases resources.
     *
     * This method is idempotent and safe to call multiple times.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        try {
            $this->stream->close();
        } catch (Throwable) {
            // Ignore close errors - stream may already be closed
        }
    }
}
