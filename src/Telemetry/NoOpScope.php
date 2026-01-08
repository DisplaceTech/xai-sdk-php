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

namespace Displace\XaiSdk\Telemetry;

use OpenTelemetry\Context\ScopeInterface;

/**
 * A no-operation scope.
 *
 * Used when tracing is disabled.
 */
class NoOpScope implements ScopeInterface
{
    /**
     * {@inheritDoc}
     */
    public function detach(): int
    {
        return 0;
    }
}
