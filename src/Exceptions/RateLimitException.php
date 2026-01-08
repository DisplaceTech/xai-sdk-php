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
 * Exception thrown when the API rate limit is exceeded.
 *
 * This exception is thrown when the API returns a 429 Too Many Requests response.
 * It provides information about when the client can retry the request.
 */
class RateLimitException extends XaiException
{
    /**
     * Creates a new RateLimitException instance.
     *
     * @param string $message The exception message
     * @param int|null $retryAfter Seconds to wait before retrying
     * @param Throwable|null $previous The previous exception for chaining
     * @param RequestInterface|null $request The HTTP request that caused the exception
     * @param ResponseInterface|null $response The HTTP response received
     */
    public function __construct(
        string $message = 'Rate limit exceeded. Please retry after the specified time.',
        public readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, 429, $previous, $request, $response);
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
        $body = $response->getBody();
        $body->rewind();
        $content = $body->getContents();

        $data = json_decode($content, true);
        $message = $data['error']['message']
            ?? $data['message']
            ?? 'Rate limit exceeded. Please retry after the specified time.';

        // Parse Retry-After header
        $retryAfter = null;
        if ($response->hasHeader('Retry-After')) {
            $retryAfterHeader = $response->getHeaderLine('Retry-After');
            if (is_numeric($retryAfterHeader)) {
                $retryAfter = (int) $retryAfterHeader;
            }
        }

        // Also check for retry_after in response body
        if ($retryAfter === null && isset($data['retry_after'])) {
            $retryAfter = (int) $data['retry_after'];
        }

        return new self($message, $retryAfter, null, $request, $response);
    }

    /**
     * Gets the number of seconds to wait before retrying.
     *
     * @return int|null Seconds to wait, or null if not specified
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
