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
 * Configuration for streaming responses.
 */
readonly class StreamConfig
{
    /**
     * Default buffer size in bytes.
     */
    public const int DEFAULT_BUFFER_SIZE = 8192;

    /**
     * Creates a new streaming configuration.
     *
     * @param bool $enabled Whether streaming is enabled.
     * @param int $bufferSize The buffer size for reading stream chunks.
     */
    public function __construct(
        public bool $enabled = true,
        public int $bufferSize = self::DEFAULT_BUFFER_SIZE,
    ) {
    }
}
