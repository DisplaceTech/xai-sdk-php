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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Exception thrown for server errors (HTTP 5xx).
 *
 * This exception is thrown when the API returns a server error response
 * (500, 502, 503, 504, etc.). These errors are typically temporary and
 * may be resolved by retrying the request.
 */
class ServerException extends XaiException
{
    /**
     * Creates a new ServerException instance.
     *
     * @param string $message The exception message
     * @param int $code The HTTP status code (5xx)
     * @param bool $retryable Whether this error is likely retryable
     * @param Throwable|null $previous The previous exception for chaining
     * @param RequestInterface|null $request The HTTP request that caused the exception
     * @param ResponseInterface|null $response The HTTP response received
     */
    public function __construct(
        string $message = 'A server error occurred. Please try again later.',
        int $code = 500,
        public readonly bool $retryable = true,
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous, $request, $response);
    }

    /**
     * Creates an exception from an HTTP response.
     *
     * @param RequestInterface $request The original request
     * @param ResponseInterface $response The response that triggered the exception
     * @return self
     */
    public static function fromResponse(
        RequestInterface $request,
        ResponseInterface $response,
    ): self {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        $body->rewind();
        $content = $body->getContents();

        $data = json_decode($content, true);

        // Determine message based on status code
        $defaultMessage = match ($statusCode) {
            500 => 'Internal server error. Please try again later.',
            502 => 'Bad gateway. The server received an invalid response.',
            503 => 'Service unavailable. The server is temporarily overloaded.',
            504 => 'Gateway timeout. The server did not respond in time.',
            default => 'A server error occurred. Please try again later.',
        };

        // Handle OpenAI-style error format
        if (isset($data['error']) && is_array($data['error'])) {
            $message = $data['error']['message'] ?? $defaultMessage;
        } else {
            $message = $data['message'] ?? $data['detail'] ?? $defaultMessage;
        }

        // 5xx errors are generally retryable, except for some cases
        $retryable = $statusCode !== 501; // 501 Not Implemented is not retryable

        return new self($message, $statusCode, $retryable, null, $request, $response);
    }

    /**
     * Checks if this error is likely to be resolved by retrying.
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
