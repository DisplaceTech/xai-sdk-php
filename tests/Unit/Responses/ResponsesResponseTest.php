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

namespace Displace\XaiSdk\Tests\Unit\Responses;

use Displace\XaiSdk\Responses\OutputItem;
use Displace\XaiSdk\Responses\ResponsesResponse;
use Displace\XaiSdk\Responses\Usage;
use PHPUnit\Framework\TestCase;

final class ResponsesResponseTest extends TestCase
{
    public function test_from_array_parses_basic_response(): void
    {
        $data = [
            'id' => 'resp_123',
            'status' => 'completed',
            'model' => 'grok-3',
            'output' => [],
        ];

        $response = ResponsesResponse::fromArray($data);

        $this->assertSame('resp_123', $response->id);
        $this->assertSame('completed', $response->status);
        $this->assertSame('grok-3', $response->model);
        $this->assertSame([], $response->output);
    }

    public function test_from_array_parses_output_items(): void
    {
        $data = [
            'id' => 'resp_123',
            'status' => 'completed',
            'output' => [
                [
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        ['type' => 'output_text', 'text' => 'Hello world'],
                    ],
                ],
            ],
        ];

        $response = ResponsesResponse::fromArray($data);

        $this->assertCount(1, $response->output);
        $this->assertInstanceOf(OutputItem::class, $response->output[0]);
        $this->assertSame('message', $response->output[0]->type);
    }

    public function test_get_content_extracts_text_from_messages(): void
    {
        $data = [
            'id' => 'resp_123',
            'status' => 'completed',
            'output' => [
                [
                    'type' => 'custom_tool_call',
                    'name' => 'x_search',
                    'status' => 'completed',
                ],
                [
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        ['type' => 'output_text', 'text' => 'Based on my search, '],
                        ['type' => 'output_text', 'text' => 'here are the results.'],
                    ],
                ],
            ],
        ];

        $response = ResponsesResponse::fromArray($data);
        $content = $response->getContent();

        $this->assertSame('Based on my search, here are the results.', $content);
    }

    public function test_get_tool_calls_returns_only_tool_calls(): void
    {
        $data = [
            'id' => 'resp_123',
            'status' => 'completed',
            'output' => [
                [
                    'type' => 'custom_tool_call',
                    'name' => 'x_search',
                    'status' => 'completed',
                ],
                [
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [],
                ],
                [
                    'type' => 'custom_tool_call',
                    'name' => 'web_search',
                    'status' => 'completed',
                ],
            ],
        ];

        $response = ResponsesResponse::fromArray($data);
        $toolCalls = $response->getToolCalls();

        $this->assertCount(2, $toolCalls);
    }

    public function test_is_completed_returns_true_for_completed_status(): void
    {
        $response = ResponsesResponse::fromArray([
            'id' => 'resp_123',
            'status' => 'completed',
            'output' => [],
        ]);

        $this->assertTrue($response->isCompleted());
    }

    public function test_is_completed_returns_false_for_other_status(): void
    {
        $response = ResponsesResponse::fromArray([
            'id' => 'resp_123',
            'status' => 'in_progress',
            'output' => [],
        ]);

        $this->assertFalse($response->isCompleted());
    }

    public function test_from_array_parses_usage(): void
    {
        $data = [
            'id' => 'resp_123',
            'status' => 'completed',
            'output' => [],
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
        ];

        $response = ResponsesResponse::fromArray($data);

        $this->assertInstanceOf(Usage::class, $response->usage);
        $this->assertSame(100, $response->usage->promptTokens);
        $this->assertSame(50, $response->usage->completionTokens);
        $this->assertSame(150, $response->usage->totalTokens);
    }
}
