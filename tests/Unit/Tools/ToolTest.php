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

use Displace\XaiSdk\Tools\Tool;
use PHPUnit\Framework\TestCase;

final class ToolTest extends TestCase
{
    public function test_creates_tool_with_name_description_and_parameters(): void
    {
        $parameters = [
            'type' => 'object',
            'properties' => [
                'city' => [
                    'type' => 'string',
                    'description' => 'The name of the city',
                ],
            ],
            'required' => ['city'],
        ];

        $tool = new Tool(
            name: 'get_weather',
            description: 'Get the weather for a city',
            parameters: $parameters,
        );

        $this->assertSame('get_weather', $tool->name);
        $this->assertSame('Get the weather for a city', $tool->description);
        $this->assertSame($parameters, $tool->parameters);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $parameters = [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string'],
            ],
        ];

        $tool = new Tool(
            name: 'search',
            description: 'Search for something',
            parameters: $parameters,
        );

        $array = $tool->toArray();

        $this->assertSame('function', $array['type']);
        $this->assertSame('search', $array['function']['name']);
        $this->assertSame('Search for something', $array['function']['description']);
        $this->assertSame($parameters, $array['function']['parameters']);
    }

    public function test_from_callable_extracts_function_info(): void
    {
        $callable = function (string $city, int $days = 5): string {
            return "Weather for {$city} for {$days} days";
        };

        $tool = Tool::fromCallable($callable, 'forecast', 'Get weather forecast');

        $this->assertSame('forecast', $tool->name);
        $this->assertSame('Get weather forecast', $tool->description);
        $this->assertSame('object', $tool->parameters['type']);
        $this->assertArrayHasKey('city', $tool->parameters['properties']);
        $this->assertArrayHasKey('days', $tool->parameters['properties']);
        $this->assertSame('string', $tool->parameters['properties']['city']['type']);
        $this->assertSame('integer', $tool->parameters['properties']['days']['type']);
        $this->assertContains('city', $tool->parameters['required']);
        $this->assertNotContains('days', $tool->parameters['required']);
    }

    public function test_from_callable_uses_docblock_description(): void
    {
        /**
         * Get the weather for a location.
         *
         * @param string $location The location to check.
         */
        $callable = function (string $location): string {
            return "Weather for {$location}";
        };

        $tool = Tool::fromCallable($callable, 'weather');

        $this->assertSame('weather', $tool->name);
        $this->assertSame('Get the weather for a location.', $tool->description);
    }

    public function test_php_type_to_json_schema_type_mapping(): void
    {
        $callable = function (
            int $intParam,
            float $floatParam,
            bool $boolParam,
            array $arrayParam,
            string $stringParam,
        ): void {
        };

        $tool = Tool::fromCallable($callable, 'type_test', 'Test types');

        $this->assertSame('integer', $tool->parameters['properties']['intParam']['type']);
        $this->assertSame('number', $tool->parameters['properties']['floatParam']['type']);
        $this->assertSame('boolean', $tool->parameters['properties']['boolParam']['type']);
        $this->assertSame('array', $tool->parameters['properties']['arrayParam']['type']);
        $this->assertSame('string', $tool->parameters['properties']['stringParam']['type']);
    }
}
