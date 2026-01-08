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

namespace Displace\XaiSdk\Tests\Unit\Telemetry;

use Displace\XaiSdk\Telemetry\NoOpScope;
use PHPUnit\Framework\TestCase;

final class NoOpScopeTest extends TestCase
{
    protected function setUp(): void
    {
        if (! interface_exists(\OpenTelemetry\Context\ScopeInterface::class)) {
            $this->markTestSkipped('OpenTelemetry SDK not installed.');
        }
    }

    public function test_implements_scope_interface(): void
    {
        $scope = new NoOpScope();

        $this->assertInstanceOf(\OpenTelemetry\Context\ScopeInterface::class, $scope);
    }

    public function test_detach_returns_zero(): void
    {
        $scope = new NoOpScope();

        $result = $scope->detach();

        $this->assertSame(0, $result);
    }

    public function test_multiple_detach_calls_return_zero(): void
    {
        $scope = new NoOpScope();

        $this->assertSame(0, $scope->detach());
        $this->assertSame(0, $scope->detach());
        $this->assertSame(0, $scope->detach());
    }
}
