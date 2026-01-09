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

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Server-side tool for X (Twitter) search.
 *
 * This tool enables the model to search X posts and access social media
 * content during conversation. It can be configured with date ranges,
 * user handle filters, and media understanding capabilities.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\XSearch;
 *
 * // Create an X search tool for recent posts from specific users
 * $xSearch = new XSearch(
 *     fromDate: new DateTime('2025-01-01'),
 *     allowedXHandles: ['xai', 'elonmusk'],
 *     enableImageUnderstanding: true,
 *     enableVideoUnderstanding: true,
 * );
 * ```
 */
readonly class XSearch implements ServerSideTool
{
    public const TYPE = 'x_search';

    /**
     * Creates a new XSearch instance.
     *
     * @param DateTimeInterface|null $fromDate Start date for search results.
     * @param DateTimeInterface|null $toDate End date for search results.
     * @param array<string>|null $allowedXHandles X usernames (without @) to limit results to.
     *                                            Cannot be used with $excludedXHandles.
     * @param array<string>|null $excludedXHandles X usernames (without @) to exclude.
     *                                             Cannot be used with $allowedXHandles.
     * @param bool $enableImageUnderstanding Enable image understanding for posts.
     * @param bool $enableVideoUnderstanding Enable video understanding for posts.
     */
    public function __construct(
        public ?DateTimeInterface $fromDate = null,
        public ?DateTimeInterface $toDate = null,
        public ?array $allowedXHandles = null,
        public ?array $excludedXHandles = null,
        public bool $enableImageUnderstanding = false,
        public bool $enableVideoUnderstanding = false,
    ) {
        if ($allowedXHandles !== null && $excludedXHandles !== null) {
            throw new InvalidArgumentException(
                'Cannot set both allowedXHandles and excludedXHandles. Use one or the other.',
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
            'enable_video_understanding' => $this->enableVideoUnderstanding,
        ];

        if ($this->fromDate !== null) {
            $config['from_date'] = $this->fromDate->format(DateTimeInterface::RFC3339);
        }

        if ($this->toDate !== null) {
            $config['to_date'] = $this->toDate->format(DateTimeInterface::RFC3339);
        }

        if ($this->allowedXHandles !== null) {
            $config['allowed_x_handles'] = $this->allowedXHandles;
        }

        if ($this->excludedXHandles !== null) {
            $config['excluded_x_handles'] = $this->excludedXHandles;
        }

        return [
            self::TYPE => $config,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toResponsesArray(): array
    {
        $result = [
            'type' => self::TYPE,
        ];

        if ($this->fromDate !== null) {
            $result['from_date'] = $this->fromDate->format('Y-m-d');
        }

        if ($this->toDate !== null) {
            $result['to_date'] = $this->toDate->format('Y-m-d');
        }

        if ($this->allowedXHandles !== null) {
            $result['x_handles'] = $this->allowedXHandles;
        }

        if ($this->excludedXHandles !== null) {
            $result['excluded_x_handles'] = $this->excludedXHandles;
        }

        return $result;
    }
}
