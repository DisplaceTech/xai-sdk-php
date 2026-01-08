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

namespace Displace\XaiSdk\Tests\Unit\Streaming;

use Displace\XaiSdk\Streaming\SseEvent;
use Displace\XaiSdk\Tests\TestCase;

final class SseEventTest extends TestCase
{
    public function test_default_values(): void
    {
        $event = new SseEvent();

        $this->assertNull($event->event);
        $this->assertSame('', $event->data);
        $this->assertNull($event->id);
        $this->assertNull($event->retry);
    }

    public function test_event_type_can_be_set(): void
    {
        $event = new SseEvent(event: 'message');

        $this->assertSame('message', $event->event);
    }

    public function test_data_can_be_set(): void
    {
        $event = new SseEvent(data: '{"content": "Hello"}');

        $this->assertSame('{"content": "Hello"}', $event->data);
    }

    public function test_id_can_be_set(): void
    {
        $event = new SseEvent(id: 'event-123');

        $this->assertSame('event-123', $event->id);
    }

    public function test_retry_can_be_set(): void
    {
        $event = new SseEvent(retry: 3000);

        $this->assertSame(3000, $event->retry);
    }

    public function test_isDone_returns_true_for_done_marker(): void
    {
        $event = new SseEvent(data: '[DONE]');

        $this->assertTrue($event->isDone());
    }

    public function test_isDone_returns_true_with_whitespace(): void
    {
        $event = new SseEvent(data: '  [DONE]  ');

        $this->assertTrue($event->isDone());
    }

    public function test_isDone_returns_false_for_regular_data(): void
    {
        $event = new SseEvent(data: '{"content": "Hello"}');

        $this->assertFalse($event->isDone());
    }

    public function test_isDone_returns_false_for_empty_data(): void
    {
        $event = new SseEvent(data: '');

        $this->assertFalse($event->isDone());
    }

    public function test_getJson_returns_decoded_data(): void
    {
        $data = ['content' => 'Hello', 'id' => 123];
        $event = new SseEvent(data: json_encode($data));

        $this->assertSame($data, $event->getJson());
    }

    public function test_getJson_returns_null_for_done(): void
    {
        $event = new SseEvent(data: '[DONE]');

        $this->assertNull($event->getJson());
    }

    public function test_getJson_returns_null_for_empty_data(): void
    {
        $event = new SseEvent(data: '');

        $this->assertNull($event->getJson());
    }

    public function test_getJson_returns_null_for_invalid_json(): void
    {
        $event = new SseEvent(data: 'not valid json');

        $this->assertNull($event->getJson());
    }

    public function test_getJson_handles_nested_objects(): void
    {
        $data = [
            'choices' => [
                [
                    'delta' => [
                        'content' => 'Hello',
                    ],
                ],
            ],
        ];
        $event = new SseEvent(data: json_encode($data));

        $result = $event->getJson();
        $this->assertSame('Hello', $result['choices'][0]['delta']['content']);
    }

    public function test_hasData_returns_true_for_non_empty(): void
    {
        $event = new SseEvent(data: 'some data');

        $this->assertTrue($event->hasData());
    }

    public function test_hasData_returns_false_for_empty(): void
    {
        $event = new SseEvent(data: '');

        $this->assertFalse($event->hasData());
    }

    public function test_event_is_readonly(): void
    {
        $event = new SseEvent(
            event: 'message',
            data: '{"test": true}',
            id: 'event-1',
            retry: 5000
        );

        // Verify readonly by checking property access
        $this->assertSame('message', $event->event);
        $this->assertSame('{"test": true}', $event->data);
        $this->assertSame('event-1', $event->id);
        $this->assertSame(5000, $event->retry);
    }

    public function test_all_parameters_can_be_set(): void
    {
        $event = new SseEvent(
            event: 'chat',
            data: '{"role": "assistant"}',
            id: 'msg-456',
            retry: 1000
        );

        $this->assertSame('chat', $event->event);
        $this->assertSame('{"role": "assistant"}', $event->data);
        $this->assertSame('msg-456', $event->id);
        $this->assertSame(1000, $event->retry);
    }
}
