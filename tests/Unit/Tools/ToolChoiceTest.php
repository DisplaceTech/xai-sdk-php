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

namespace Displace\XaiSdk\Tests\Unit\Tools;

use Displace\XaiSdk\Tools\ToolChoice;
use PHPUnit\Framework\TestCase;

final class ToolChoiceTest extends TestCase
{
    public function test_creates_with_function_name(): void
    {
        $choice = new ToolChoice(functionName: 'get_weather');

        $this->assertSame('get_weather', $choice->functionName);
        $this->assertNull($choice->mode);
    }

    public function test_creates_with_mode(): void
    {
        $choice = new ToolChoice(mode: ToolChoice::MODE_REQUIRED);

        $this->assertNull($choice->functionName);
        $this->assertSame('required', $choice->mode);
    }

    public function test_none_creates_none_mode(): void
    {
        $choice = ToolChoice::none();

        $this->assertSame('none', $choice->mode);
        $this->assertNull($choice->functionName);
    }

    public function test_auto_creates_auto_mode(): void
    {
        $choice = ToolChoice::auto();

        $this->assertSame('auto', $choice->mode);
        $this->assertNull($choice->functionName);
    }

    public function test_required_creates_required_mode(): void
    {
        $choice = ToolChoice::required();

        $this->assertSame('required', $choice->mode);
        $this->assertNull($choice->functionName);
    }

    public function test_function_creates_with_name(): void
    {
        $choice = ToolChoice::function('search');

        $this->assertSame('search', $choice->functionName);
        $this->assertNull($choice->mode);
    }

    public function test_to_array_with_function_name(): void
    {
        $choice = new ToolChoice(functionName: 'calculate');

        $array = $choice->toArray();

        $this->assertIsArray($array);
        $this->assertSame('function', $array['type']);
        $this->assertSame('calculate', $array['function']['name']);
    }

    public function test_to_array_with_mode(): void
    {
        $choice = ToolChoice::required();

        $result = $choice->toArray();

        $this->assertSame('required', $result);
    }

    public function test_to_array_defaults_to_auto(): void
    {
        $choice = new ToolChoice();

        $result = $choice->toArray();

        $this->assertSame('auto', $result);
    }

    public function test_mode_constants(): void
    {
        $this->assertSame('none', ToolChoice::MODE_NONE);
        $this->assertSame('auto', ToolChoice::MODE_AUTO);
        $this->assertSame('required', ToolChoice::MODE_REQUIRED);
    }
}
