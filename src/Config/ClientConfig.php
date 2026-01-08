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

namespace Displace\XaiSdk\Config;

/**
 * Configuration for the xAI API client.
 */
readonly class ClientConfig
{
    /**
     * Default API host.
     */
    public const string DEFAULT_API_HOST = 'https://api.x.ai';

    /**
     * Default timeout in seconds.
     */
    public const int DEFAULT_TIMEOUT = 900;

    /**
     * Creates a new client configuration.
     *
     * @param string|null $apiKey The API key for authentication. If null, reads from XAI_API_KEY environment variable.
     * @param string $apiHost The API host URL.
     * @param int $timeout Request timeout in seconds.
     * @param array<string, string> $headers Additional headers to send with each request.
     */
    public function __construct(
        public ?string $apiKey = null,
        public string $apiHost = self::DEFAULT_API_HOST,
        public int $timeout = self::DEFAULT_TIMEOUT,
        public array $headers = [],
    ) {}

    /**
     * Gets the API key, falling back to environment variable if not set.
     *
     * @throws \RuntimeException If no API key is available.
     */
    public function getApiKey(): string
    {
        $key = $this->apiKey ?? getenv('XAI_API_KEY');

        if ($key === false || $key === '') {
            throw new \RuntimeException(
                'No API key provided. Set the XAI_API_KEY environment variable or pass an api_key parameter.'
            );
        }

        return $key;
    }

    /**
     * Gets the base URL for API requests.
     */
    public function getBaseUrl(): string
    {
        return rtrim($this->apiHost, '/');
    }
}
