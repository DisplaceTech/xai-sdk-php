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

namespace Displace\XaiSdk\Chat\Messages;

/**
 * Interface for chat messages.
 */
interface Message
{
    /**
     * Gets the role of this message (system, user, assistant, tool).
     */
    public function getRole(): string;

    /**
     * Converts the message to an array for API requests.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
