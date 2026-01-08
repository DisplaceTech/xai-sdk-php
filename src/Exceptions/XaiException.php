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

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Base exception for all xAI SDK exceptions.
 *
 * This exception serves as the root of the exception hierarchy and can be
 * caught to handle any SDK-related errors. It provides access to the
 * original HTTP request and response when available.
 */
class XaiException extends Exception
{
    /**
     * Creates a new XaiException instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code (typically HTTP status code)
     * @param Throwable|null $previous The previous exception for chaining
     * @param RequestInterface|null $request The HTTP request that caused the exception
     * @param ResponseInterface|null $response The HTTP response received
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?RequestInterface $request = null,
        public readonly ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the HTTP status code from the response, if available.
     *
     * @return int|null The HTTP status code, or null if no response is available
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->response?->getStatusCode();
    }

    /**
     * Gets the response body as a string, if available.
     *
     * @return string|null The response body, or null if no response is available
     */
    public function getResponseBody(): ?string
    {
        if ($this->response === null) {
            return null;
        }

        $body = $this->response->getBody();
        $body->rewind();

        return $body->getContents();
    }

    /**
     * Gets the parsed JSON response body, if available.
     *
     * @return array<string, mixed>|null The parsed response, or null if unavailable
     */
    public function getResponseData(): ?array
    {
        $body = $this->getResponseBody();
        if ($body === null) {
            return null;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }
}
