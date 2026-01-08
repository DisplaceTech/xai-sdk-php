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

use Displace\XaiSdk\Exceptions\ApiException;
use Displace\XaiSdk\Exceptions\AuthenticationException;
use Displace\XaiSdk\Exceptions\BadRequestException;
use Displace\XaiSdk\Exceptions\RateLimitException;
use Displace\XaiSdk\Exceptions\ServerException;
use Displace\XaiSdk\Exceptions\XaiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 compliant HTTP client wrapper for xAI API.
 *
 * This class wraps a PSR-18 HTTP client (defaulting to Guzzle) and provides
 * xAI-specific functionality such as authentication, base URL handling,
 * error mapping, and optional retry logic.
 */
class HttpClient
{
    private const DEFAULT_BASE_URL = 'https://api.x.ai/v1';

    private const SDK_VERSION = '1.0.0';

    private ClientInterface $client;

    private string $baseUrl;

    private string $apiKey;

    private HttpConfig $config;

    /** @var array<string, string> */
    private array $defaultHeaders;

    /**
     * Creates a new HttpClient instance.
     *
     * @param string $apiKey The xAI API key for authentication
     * @param ClientInterface|null $client PSR-18 HTTP client (defaults to Guzzle)
     * @param string $baseUrl The base URL for API requests
     * @param HttpConfig|null $config HTTP configuration (timeouts, retries, etc.)
     * @param array<string, string> $headers Additional default headers
     */
    public function __construct(
        string $apiKey,
        ?ClientInterface $client = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?HttpConfig $config = null,
        array $headers = [],
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->config = $config ?? HttpConfig::default();

        $this->client = $client ?? $this->createDefaultClient();

        $this->defaultHeaders = array_merge([
            'Authorization' => sprintf('Bearer %s', $this->apiKey),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => sprintf('xai-php-sdk/%s PHP/%s', self::SDK_VERSION, PHP_VERSION),
            'X-SDK-Version' => sprintf('php/%s', self::SDK_VERSION),
            'X-SDK-Language' => sprintf('php/%s', PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION),
        ], $headers);
    }

    /**
     * Sends a GET request.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $query Query parameters
     * @param array<string, string> $headers Additional headers
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    public function get(
        string $path,
        array $query = [],
        array $headers = [],
    ): ResponseInterface {
        $uri = $this->buildUri($path, $query);
        $request = new Request('GET', $uri, $this->mergeHeaders($headers));

        return $this->sendWithRetry($request);
    }

    /**
     * Sends a POST request with JSON body.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $body Request body (will be JSON encoded)
     * @param array<string, string> $headers Additional headers
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    public function post(
        string $path,
        array $body = [],
        array $headers = [],
    ): ResponseInterface {
        $uri = $this->buildUri($path);
        $request = new Request(
            'POST',
            $uri,
            $this->mergeHeaders($headers),
            json_encode($body, JSON_THROW_ON_ERROR),
        );

        return $this->sendWithRetry($request);
    }

    /**
     * Sends a POST request and returns a streaming response.
     *
     * Note: Streaming requests do not support automatic retries.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $body Request body (will be JSON encoded)
     * @param array<string, string> $headers Additional headers
     *
     * @throws XaiException
     *
     * @return StreamingResponse
     */
    public function postStream(
        string $path,
        array $body = [],
        array $headers = [],
    ): StreamingResponse {
        // Ensure stream is set in body for streaming requests
        $body['stream'] = true;

        $uri = $this->buildUri($path);
        $streamHeaders = array_merge($headers, [
            'Accept' => 'text/event-stream',
        ]);

        $request = new Request(
            'POST',
            $uri,
            $this->mergeHeaders($streamHeaders),
            json_encode($body, JSON_THROW_ON_ERROR),
        );

        // Streaming requests don't support retry (can't replay a stream)
        $response = $this->send($request, stream: true);

        return new StreamingResponse($response);
    }

    /**
     * Sends a DELETE request.
     *
     * @param string $path The API endpoint path
     * @param array<string, string> $headers Additional headers
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    public function delete(
        string $path,
        array $headers = [],
    ): ResponseInterface {
        $uri = $this->buildUri($path);
        $request = new Request('DELETE', $uri, $this->mergeHeaders($headers));

        return $this->sendWithRetry($request);
    }

    /**
     * Sends a PUT request with JSON body.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $body Request body (will be JSON encoded)
     * @param array<string, string> $headers Additional headers
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    public function put(
        string $path,
        array $body = [],
        array $headers = [],
    ): ResponseInterface {
        $uri = $this->buildUri($path);
        $request = new Request(
            'PUT',
            $uri,
            $this->mergeHeaders($headers),
            json_encode($body, JSON_THROW_ON_ERROR),
        );

        return $this->sendWithRetry($request);
    }

    /**
     * Sends a PATCH request with JSON body.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $body Request body (will be JSON encoded)
     * @param array<string, string> $headers Additional headers
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    public function patch(
        string $path,
        array $body = [],
        array $headers = [],
    ): ResponseInterface {
        $uri = $this->buildUri($path);
        $request = new Request(
            'PATCH',
            $uri,
            $this->mergeHeaders($headers),
            json_encode($body, JSON_THROW_ON_ERROR),
        );

        return $this->sendWithRetry($request);
    }

    /**
     * Sends a request using the PSR-18 client.
     *
     * @param RequestInterface $request The request to send
     * @param bool $stream Whether to stream the response
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    public function send(RequestInterface $request, bool $stream = false): ResponseInterface
    {
        try {
            // If using Guzzle with streaming, we need to handle it specially
            if ($stream && $this->client instanceof GuzzleClient) {
                /** @var ResponseInterface $response */
                $response = $this->client->send($request, [
                    'stream' => true,
                    'timeout' => $this->config->timeout,
                    'connect_timeout' => $this->config->connectTimeout,
                ]);
            } elseif ($this->client instanceof GuzzleClient) {
                /** @var ResponseInterface $response */
                $response = $this->client->send($request, [
                    'timeout' => $this->config->timeout,
                    'connect_timeout' => $this->config->connectTimeout,
                ]);
            } else {
                $response = $this->client->sendRequest($request);
            }

            $this->handleErrorResponse($request, $response);

            return $response;
        } catch (ConnectException $e) {
            throw ApiException::connectionError(
                $e->getMessage(),
                $request,
                $e,
            );
        } catch (ClientExceptionInterface $e) {
            throw ApiException::connectionError(
                $e->getMessage(),
                $request,
                $e,
            );
        }
    }

    /**
     * Gets the base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Gets the HTTP configuration.
     *
     * @return HttpConfig
     */
    public function getConfig(): HttpConfig
    {
        return $this->config;
    }

    /**
     * Gets the API key (masked for security).
     *
     * @return string
     */
    public function getMaskedApiKey(): string
    {
        if (strlen($this->apiKey) <= 8) {
            return str_repeat('*', strlen($this->apiKey));
        }

        return substr($this->apiKey, 0, 4) . '...' . substr($this->apiKey, -4);
    }

    /**
     * Sends a request with retry logic.
     *
     * @param RequestInterface $request The request to send
     *
     * @throws XaiException
     *
     * @return ResponseInterface
     */
    private function sendWithRetry(RequestInterface $request): ResponseInterface
    {
        $lastException = null;
        $attempts = 0;
        $maxAttempts = $this->config->maxRetries + 1;

        while ($attempts < $maxAttempts) {
            try {
                $response = $this->send($request);

                // Check if we should retry based on status code
                $statusCode = $response->getStatusCode();

                if ($attempts < $this->config->maxRetries && $this->config->shouldRetryStatusCode($statusCode)) {
                    // For rate limits, use Retry-After header if present
                    $delay = $this->getRetryDelay($response, $attempts);
                    usleep((int) ($delay * 1_000_000));
                    $attempts++;
                    continue;
                }

                return $response;
            } catch (ApiException $e) {
                $lastException = $e;

                // Only retry on connection errors if configured
                if (! $this->config->retryOnConnectionError || $e->getErrorType() !== 'connection_error') {
                    throw $e;
                }

                if ($attempts >= $this->config->maxRetries) {
                    throw $e;
                }

                $delay = $this->config->calculateRetryDelay($attempts);
                usleep((int) ($delay * 1_000_000));
                $attempts++;
            }
        }

        // This should not be reached, but just in case
        throw $lastException ?? new ApiException('Request failed after retries');
    }

    /**
     * Creates the default Guzzle HTTP client.
     *
     * @return ClientInterface
     */
    private function createDefaultClient(): ClientInterface
    {
        return new GuzzleClient([
            'timeout' => $this->config->timeout,
            'connect_timeout' => $this->config->connectTimeout,
            'http_errors' => false, // We handle errors ourselves
        ]);
    }

    /**
     * Builds a full URI from a path and optional query parameters.
     *
     * @param string $path The API endpoint path
     * @param array<string, mixed> $query Query parameters
     *
     * @return Uri
     */
    private function buildUri(string $path, array $query = []): Uri
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return new Uri($url);
    }

    /**
     * Merges additional headers with default headers.
     *
     * @param array<string, string> $headers Additional headers
     *
     * @return array<string, string>
     */
    private function mergeHeaders(array $headers): array
    {
        return array_merge($this->defaultHeaders, $headers);
    }

    /**
     * Gets the retry delay, considering Retry-After header for rate limits.
     *
     * @param ResponseInterface $response The response
     * @param int $attempt The current attempt number
     *
     * @return float Delay in seconds
     */
    private function getRetryDelay(ResponseInterface $response, int $attempt): float
    {
        // Check for Retry-After header (used by rate limit responses)
        if ($response->hasHeader('Retry-After')) {
            $retryAfter = $response->getHeaderLine('Retry-After');

            if (is_numeric($retryAfter)) {
                return (float) $retryAfter;
            }
        }

        return $this->config->calculateRetryDelay($attempt);
    }

    /**
     * Handles error responses by throwing appropriate exceptions.
     *
     * Maps HTTP status codes to specific exception types:
     * - 400: BadRequestException (invalid parameters)
     * - 401: AuthenticationException (invalid API key)
     * - 403: ApiException (forbidden)
     * - 404: ApiException (not found)
     * - 429: RateLimitException (rate limited)
     * - 5xx: ServerException (server errors)
     * - Other: ApiException (general errors)
     *
     * @param RequestInterface $request The original request
     * @param ResponseInterface $response The response to check
     *
     * @throws XaiException
     */
    private function handleErrorResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        // Success responses (2xx)
        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        // Map status codes to appropriate exceptions
        match (true) {
            $statusCode === 400 => throw BadRequestException::fromResponse($request, $response),
            $statusCode === 401 => throw AuthenticationException::fromResponse($request, $response),
            $statusCode === 429 => throw RateLimitException::fromResponse($request, $response),
            $statusCode >= 500 && $statusCode < 600 => throw ServerException::fromResponse($request, $response),
            default => throw ApiException::fromResponse($request, $response),
        };
    }
}
