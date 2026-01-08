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
 * Exception thrown when authentication fails.
 *
 * This exception is thrown when the API returns a 401 Unauthorized response,
 * typically due to an invalid, expired, or missing API key.
 */
class AuthenticationException extends XaiException
{
    /**
     * Creates a new AuthenticationException instance.
     *
     * @param string $message The exception message
     * @param Throwable|null $previous The previous exception for chaining
     * @param RequestInterface|null $request The HTTP request that caused the exception
     * @param ResponseInterface|null $response The HTTP response received
     */
    public function __construct(
        string $message = 'Authentication failed. Please check your API key.',
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, 401, $previous, $request, $response);
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
            ?? 'Authentication failed. Please check your API key.';

        return new self($message, null, $request, $response);
    }
}
