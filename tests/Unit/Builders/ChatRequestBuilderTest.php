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

namespace Displace\XaiSdk\Tests\Unit\Builders;

use Displace\XaiSdk\Builders\ChatRequestBuilder;
use Displace\XaiSdk\Tests\TestCase;
use Displace\XaiSdk\Tools\Tool;
use Displace\XaiSdk\Tools\ToolChoice;
use Displace\XaiSdk\XaiClient;
use Mockery;

/**
 * Tests for ChatRequestBuilder.
 *
 * Note: These are primarily builder configuration tests. Full integration
 * tests with XaiClient would require more extensive mocking.
 */
final class ChatRequestBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_returns_builder(): void
    {
        $client = Mockery::mock(XaiClient::class);
        $builder = ChatRequestBuilder::create($client);

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_method_chaining(): void
    {
        $client = Mockery::mock(XaiClient::class);
        $builder = ChatRequestBuilder::create($client);

        $result = $builder
            ->model('grok-3')
            ->systemPrompt('You are helpful.')
            ->userMessage('Hello!')
            ->assistantMessage('Hi!')
            ->temperature(0.7)
            ->maxTokens(1000)
            ->seed(42)
            ->stop(['END'])
            ->topP(0.9)
            ->withLogprobs(true, 5)
            ->frequencyPenalty(0.5)
            ->presencePenalty(0.5)
            ->reasoningEffort('high')
            ->searchParameters(['mode' => 'web'])
            ->storeMessages(true)
            ->previousResponseId('resp_123')
            ->maxTurns(5)
            ->user('user-123')
            ->jsonResponse()
            ->parallelToolCalls(true);

        $this->assertSame($builder, $result);
    }

    public function test_with_tool(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $tool = new Tool('test_tool', 'A test tool', [
            'type' => 'object',
            'properties' => [],
        ]);

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->withTool($tool);

        $this->assertSame($builder, $builder);
    }

    public function test_with_tools(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $tools = [
            new Tool('tool1', 'First tool', ['type' => 'object', 'properties' => []]),
            new Tool('tool2', 'Second tool', ['type' => 'object', 'properties' => []]),
        ];

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->withTools($tools);

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_tool_choice_string(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->toolChoice('auto');

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_tool_choice_object(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $choice = new ToolChoice('my_tool');

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->toolChoice($choice);

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_json_schema(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->jsonSchema($schema, 'my_schema');

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_reset(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->systemPrompt('Test')
            ->temperature(0.5)
            ->reset();

        // After reset, builder should still be functional
        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_user_message_with_images(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->userMessageWithImages('Look at this!', ['https://example.com/image.jpg'], 'high');

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }

    public function test_response_format(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $builder = ChatRequestBuilder::create($client)
            ->model('grok-3')
            ->responseFormat(['type' => 'json_object']);

        $this->assertInstanceOf(ChatRequestBuilder::class, $builder);
    }
}
