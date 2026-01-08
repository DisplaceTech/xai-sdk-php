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

namespace Displace\XaiSdk\Tools;

/**
 * Server-side tool for web search.
 *
 * This tool enables the model to perform web searches and access online content
 * during conversation. It can be configured to restrict or exclude specific domains
 * and enable image understanding capabilities.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\WebSearch;
 *
 * // Create a web search tool that excludes certain domains
 * $webSearch = new WebSearch(
 *     excludedDomains: ['spam-site.com', 'unwanted.com'],
 *     enableImageUnderstanding: true,
 * );
 * ```
 */
readonly class WebSearch implements ServerSideTool
{
    public const TYPE = 'web_search';

    /**
     * Creates a new WebSearch instance.
     *
     * @param array<string>|null $excludedDomains Domains to exclude from search results.
     *        A maximum of 5 websites can be excluded. Cannot be used with $allowedDomains.
     * @param array<string>|null $allowedDomains Domains to restrict search results to.
     *        A maximum of 5 websites can be allowed. Cannot be used with $excludedDomains.
     * @param bool $enableImageUnderstanding Enable image understanding during web search.
     */
    public function __construct(
        public ?array $excludedDomains = null,
        public ?array $allowedDomains = null,
        public bool $enableImageUnderstanding = false,
    ) {
        if ($excludedDomains !== null && $allowedDomains !== null) {
            throw new \InvalidArgumentException(
                'Cannot set both excludedDomains and allowedDomains. Use one or the other.'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $config = [
            'enable_image_understanding' => $this->enableImageUnderstanding,
        ];

        if ($this->excludedDomains !== null) {
            $config['excluded_domains'] = $this->excludedDomains;
        }

        if ($this->allowedDomains !== null) {
            $config['allowed_domains'] = $this->allowedDomains;
        }

        return [
            self::TYPE => $config,
        ];
    }
}
