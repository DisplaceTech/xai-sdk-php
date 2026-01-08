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
 * Exception thrown for bad request errors (HTTP 400).
 *
 * This exception is thrown when the API returns a 400 Bad Request response,
 * typically due to invalid request parameters, malformed JSON, or
 * validation errors.
 */
class BadRequestException extends XaiException
{
    /**
     * Creates a new BadRequestException instance.
     *
     * @param string $message The exception message
     * @param string|null $parameter The invalid parameter name, if known
     * @param Throwable|null $previous The previous exception for chaining
     * @param RequestInterface|null $request The HTTP request that caused the exception
     * @param ResponseInterface|null $response The HTTP response received
     */
    public function __construct(
        string $message = 'Bad request. Please check your request parameters.',
        public readonly ?string $parameter = null,
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, 400, $previous, $request, $response);
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

        // Handle OpenAI-style error format
        if (isset($data['error']) && is_array($data['error'])) {
            $error = $data['error'];
            $message = $error['message'] ?? 'Bad request. Please check your request parameters.';
            $parameter = $error['param'] ?? null;
        } else {
            $message = $data['message'] ?? $data['detail'] ?? 'Bad request. Please check your request parameters.';
            $parameter = $data['param'] ?? $data['parameter'] ?? null;
        }

        return new self($message, $parameter, null, $request, $response);
    }

    /**
     * Gets the invalid parameter name, if available.
     *
     * @return string|null The parameter name, or null if not specified
     */
    public function getParameter(): ?string
    {
        return $this->parameter;
    }
}
