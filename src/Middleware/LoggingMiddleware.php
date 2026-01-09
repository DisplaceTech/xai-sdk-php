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

namespace Displace\XaiSdk\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Middleware that logs HTTP requests and responses.
 *
 * Useful for debugging and monitoring API interactions. Sensitive data
 * like authorization headers can be redacted.
 *
 * @example
 * ```php
 * $logger = new MonologLogger('xai');
 * $middleware = new LoggingMiddleware($logger);
 *
 * // With custom log level
 * $middleware = new LoggingMiddleware($logger, LogLevel::INFO);
 *
 * // With body logging disabled
 * $middleware = new LoggingMiddleware($logger, logBody: false);
 * ```
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    /**
     * Headers to redact from logs.
     *
     * @var array<string>
     */
    private const SENSITIVE_HEADERS = [
        'Authorization',
        'X-Api-Key',
    ];

    /**
     * Creates a new logging middleware.
     *
     * @param LoggerInterface $logger The PSR-3 logger.
     * @param string $level The log level for messages.
     * @param bool $logBody Whether to log request/response bodies.
     * @param int $maxBodyLength Maximum body length to log (0 for unlimited).
     * @param array<string> $redactHeaders Additional headers to redact.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $level = LogLevel::DEBUG,
        private readonly bool $logBody = true,
        private readonly int $maxBodyLength = 1000,
        private readonly array $redactHeaders = [],
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $requestId = $this->generateRequestId();
        $startTime = hrtime(true);

        $this->logRequest($request, $requestId);

        try {
            $response = $next($request);
        } finally {
            $duration = (hrtime(true) - $startTime) / 1_000_000; // Convert to ms
        }

        $this->logResponse($response, $requestId, $duration);

        return $response;
    }

    /**
     * Logs the outgoing request.
     *
     * @param RequestInterface $request The HTTP request.
     * @param string $requestId The request ID for correlation.
     */
    private function logRequest(RequestInterface $request, string $requestId): void
    {
        $context = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->redactHeaders($request->getHeaders()),
        ];

        if ($this->logBody && $request->getBody()->getSize() > 0) {
            $body = $this->truncateBody((string) $request->getBody());
            $context['body'] = $body;
            $request->getBody()->rewind();
        }

        $this->logger->log(
            $this->level,
            sprintf('API Request: %s %s', $request->getMethod(), $request->getUri()->getPath()),
            $context,
        );
    }

    /**
     * Logs the incoming response.
     *
     * @param ResponseInterface $response The HTTP response.
     * @param string $requestId The request ID for correlation.
     * @param float $durationMs The request duration in milliseconds.
     */
    private function logResponse(ResponseInterface $response, string $requestId, float $durationMs): void
    {
        $context = [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'duration_ms' => round($durationMs, 2),
            'headers' => $this->redactHeaders($response->getHeaders()),
        ];

        if ($this->logBody) {
            $body = (string) $response->getBody();
            $response->getBody()->rewind();
            $context['body'] = $this->truncateBody($body);
        }

        $level = $this->level;

        if ($response->getStatusCode() >= 400) {
            $level = $response->getStatusCode() >= 500 ? LogLevel::ERROR : LogLevel::WARNING;
        }

        $this->logger->log(
            $level,
            sprintf('API Response: %d in %.2fms', $response->getStatusCode(), $durationMs),
            $context,
        );
    }

    /**
     * Generates a unique request ID.
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Redacts sensitive headers.
     *
     * @param array<string, array<string>> $headers The headers.
     *
     * @return array<string, array<string>>
     */
    private function redactHeaders(array $headers): array
    {
        $sensitive = array_merge(self::SENSITIVE_HEADERS, $this->redactHeaders);
        $result = [];

        foreach ($headers as $name => $values) {
            if ($this->isSensitiveHeader($name, $sensitive)) {
                $result[$name] = ['[REDACTED]'];
            } else {
                $result[$name] = $values;
            }
        }

        return $result;
    }

    /**
     * Checks if a header is sensitive.
     *
     * @param string $name The header name.
     * @param array<string> $sensitive List of sensitive header names.
     *
     * @return bool
     */
    private function isSensitiveHeader(string $name, array $sensitive): bool
    {
        $lowerName = strtolower($name);

        foreach ($sensitive as $sensitiveHeader) {
            if (strtolower($sensitiveHeader) === $lowerName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Truncates a body to the maximum length.
     *
     * @param string $body The body content.
     *
     * @return string
     */
    private function truncateBody(string $body): string
    {
        if ($this->maxBodyLength === 0 || strlen($body) <= $this->maxBodyLength) {
            return $body;
        }

        return substr($body, 0, $this->maxBodyLength) . '... [truncated]';
    }
}
