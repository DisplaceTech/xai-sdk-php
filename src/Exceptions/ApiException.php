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
 * Exception thrown for general API errors.
 *
 * This exception is thrown for API errors that don't fall into more specific
 * categories like authentication or rate limiting. It includes the error type
 * and code returned by the API when available.
 */
class ApiException extends XaiException
{
    /**
     * Creates a new ApiException instance.
     *
     * @param string $message The exception message
     * @param int $code The HTTP status code
     * @param string|null $errorType The API error type (e.g., 'invalid_request_error')
     * @param string|null $errorCode The API error code
     * @param Throwable|null $previous The previous exception for chaining
     * @param RequestInterface|null $request The HTTP request that caused the exception
     * @param ResponseInterface|null $response The HTTP response received
     */
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?string $errorType = null,
        public readonly ?string $errorCode = null,
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
     *
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

        // Handle OpenAI-style error format
        if (isset($data['error']) && is_array($data['error'])) {
            $error = $data['error'];
            $message = $error['message'] ?? 'An unknown API error occurred.';
            $errorType = $error['type'] ?? null;
            $errorCode = $error['code'] ?? null;
        } else {
            $message = $data['message'] ?? $data['detail'] ?? 'An unknown API error occurred.';
            $errorType = $data['type'] ?? null;
            $errorCode = $data['code'] ?? null;
        }

        return new self(
            $message,
            $statusCode,
            $errorType,
            $errorCode,
            null,
            $request,
            $response,
        );
    }

    /**
     * Creates an exception for a connection error.
     *
     * @param string $message The error message
     * @param RequestInterface|null $request The request that failed
     * @param Throwable|null $previous The previous exception
     *
     * @return self
     */
    public static function connectionError(
        string $message,
        ?RequestInterface $request = null,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf('Connection error: %s', $message),
            0,
            'connection_error',
            null,
            $previous,
            $request,
            null,
        );
    }

    /**
     * Creates an exception for a timeout error.
     *
     * @param RequestInterface|null $request The request that timed out
     * @param Throwable|null $previous The previous exception
     *
     * @return self
     */
    public static function timeout(
        ?RequestInterface $request = null,
        ?Throwable $previous = null,
    ): self {
        return new self(
            'The request timed out.',
            0,
            'timeout_error',
            null,
            $previous,
            $request,
            null,
        );
    }

    /**
     * Gets the API error type.
     *
     * @return string|null The error type, or null if not provided
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * Gets the API error code.
     *
     * @return string|null The error code, or null if not provided
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
