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
 * Interface for server-side tools.
 *
 * Server-side tools are executed by xAI's servers rather than client-side.
 */
interface ServerSideTool
{
    /**
     * Gets the tool type identifier.
     *
     * @return string The tool type (e.g., 'web_search', 'x_search', 'code_execution').
     */
    public function getType(): string;

    /**
     * Converts the tool to an array representation for API requests.
     *
     * This format is used for /chat/completions endpoint (legacy).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Converts the tool to an array representation for /v1/responses endpoint.
     *
     * The responses endpoint uses a different format with 'type' at the root level.
     *
     * @return array<string, mixed>
     */
    public function toResponsesArray(): array;
}
