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

use Displace\XaiSdk\Tools\FunctionCall;
use PHPUnit\Framework\TestCase;

final class FunctionCallTest extends TestCase
{
    public function test_creates_function_call_with_name_and_arguments(): void
    {
        $functionCall = new FunctionCall(
            name: 'get_weather',
            arguments: '{"city":"London","units":"C"}',
        );

        $this->assertSame('get_weather', $functionCall->name);
        $this->assertSame('{"city":"London","units":"C"}', $functionCall->arguments);
    }

    public function test_get_decoded_arguments_returns_array(): void
    {
        $functionCall = new FunctionCall(
            name: 'test',
            arguments: '{"key":"value","number":42,"nested":{"a":1}}',
        );

        $decoded = $functionCall->getDecodedArguments();

        $this->assertSame('value', $decoded['key']);
        $this->assertSame(42, $decoded['number']);
        $this->assertSame(['a' => 1], $decoded['nested']);
    }

    public function test_get_decoded_arguments_throws_on_invalid_json(): void
    {
        $functionCall = new FunctionCall(
            name: 'test',
            arguments: 'not valid json',
        );

        $this->expectException(\JsonException::class);
        $functionCall->getDecodedArguments();
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $functionCall = new FunctionCall(
            name: 'search',
            arguments: '{"query":"test"}',
        );

        $array = $functionCall->toArray();

        $this->assertSame('search', $array['name']);
        $this->assertSame('{"query":"test"}', $array['arguments']);
    }

    public function test_from_array_creates_function_call(): void
    {
        $data = [
            'name' => 'calculate',
            'arguments' => '{"x":1,"y":2}',
        ];

        $functionCall = FunctionCall::fromArray($data);

        $this->assertSame('calculate', $functionCall->name);
        $this->assertSame('{"x":1,"y":2}', $functionCall->arguments);
    }
}
