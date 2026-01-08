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

use Displace\XaiSdk\Http\StreamingResponse;
use Displace\XaiSdk\Streaming\SseParser;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

final class SseParserTest extends TestCase
{
    public function test_parses_simple_data_event(): void
    {
        $stream = $this->createStreamFromString("data: hello world\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('hello world', $events[0]->data);
    }

    public function test_parses_json_data_event(): void
    {
        $json = json_encode(['key' => 'value', 'number' => 42]);
        $stream = $this->createStreamFromString("data: {$json}\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals($json, $events[0]->data);
        $this->assertEquals(['key' => 'value', 'number' => 42], $events[0]->getJson());
    }

    public function test_parses_event_with_type(): void
    {
        $stream = $this->createStreamFromString("event: message\ndata: hello\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('message', $events[0]->event);
        $this->assertEquals('hello', $events[0]->data);
    }

    public function test_parses_event_with_id(): void
    {
        $stream = $this->createStreamFromString("id: 123\ndata: hello\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('123', $events[0]->id);
    }

    public function test_parses_event_with_retry(): void
    {
        $stream = $this->createStreamFromString("retry: 5000\ndata: hello\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals(5000, $events[0]->retry);
    }

    public function test_parses_multiline_data(): void
    {
        $stream = $this->createStreamFromString("data: line1\ndata: line2\ndata: line3\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals("line1\nline2\nline3", $events[0]->data);
    }

    public function test_parses_multiple_events(): void
    {
        $sseData = "data: first\n\ndata: second\n\ndata: third\n\n";
        $stream = $this->createStreamFromString($sseData);
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(3, $events);
        $this->assertEquals('first', $events[0]->data);
        $this->assertEquals('second', $events[1]->data);
        $this->assertEquals('third', $events[2]->data);
    }

    public function test_ignores_comments(): void
    {
        $sseData = ": this is a comment\ndata: hello\n: another comment\n\n";
        $stream = $this->createStreamFromString($sseData);
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('hello', $events[0]->data);
    }

    public function test_handles_done_marker(): void
    {
        $sseData = "data: {\"content\": \"test\"}\n\ndata: [DONE]\n\n";
        $stream = $this->createStreamFromString($sseData);
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(2, $events);
        $this->assertFalse($events[0]->isDone());
        $this->assertTrue($events[1]->isDone());
    }

    public function test_handles_data_with_colon(): void
    {
        $stream = $this->createStreamFromString("data: key: value\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('key: value', $events[0]->data);
    }

    public function test_removes_single_leading_space_from_value(): void
    {
        $stream = $this->createStreamFromString("data:  two spaces\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals(' two spaces', $events[0]->data);
    }

    public function test_handles_field_without_colon(): void
    {
        // A line with no colon is treated as field name with empty value
        $stream = $this->createStreamFromString("data\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('', $events[0]->data);
    }

    public function test_handles_crlf_line_endings(): void
    {
        $stream = $this->createStreamFromString("data: hello\r\n\r\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('hello', $events[0]->data);
    }

    public function test_handles_empty_events(): void
    {
        // Empty lines without data should not produce events
        $stream = $this->createStreamFromString("\n\ndata: hello\n\n\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('hello', $events[0]->data);
    }

    public function test_ignores_unknown_fields(): void
    {
        $stream = $this->createStreamFromString("data: hello\nunknown: value\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('hello', $events[0]->data);
    }

    public function test_handles_invalid_retry_value(): void
    {
        $stream = $this->createStreamFromString("retry: abc\ndata: hello\n\n");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertNull($events[0]->retry);
    }

    public function test_parses_chat_completion_stream(): void
    {
        $chunk1 = json_encode([
            'id' => 'chatcmpl-123',
            'model' => 'grok-3',
            'choices' => [['delta' => ['content' => 'Hello']]],
        ]);
        $chunk2 = json_encode([
            'id' => 'chatcmpl-123',
            'model' => 'grok-3',
            'choices' => [['delta' => ['content' => ' world']]],
        ]);

        $sseData = "data: {$chunk1}\n\ndata: {$chunk2}\n\ndata: [DONE]\n\n";
        $stream = $this->createStreamFromString($sseData);
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(3, $events);

        $json1 = $events[0]->getJson();
        $this->assertEquals('chatcmpl-123', $json1['id']);
        $this->assertEquals('Hello', $json1['choices'][0]['delta']['content']);

        $json2 = $events[1]->getJson();
        $this->assertEquals(' world', $json2['choices'][0]['delta']['content']);

        $this->assertTrue($events[2]->isDone());
    }

    public function test_static_from_response_creates_parser(): void
    {
        $stream = $this->createStreamFromString("data: test\n\n");
        $parser = SseParser::fromResponse($stream);

        $this->assertInstanceOf(SseParser::class, $parser);
    }

    public function test_handles_stream_without_trailing_newline(): void
    {
        // Some streams might not have a trailing empty line
        $stream = $this->createStreamFromString("data: hello\n\ndata: world");
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        // First event should be parsed normally
        $this->assertGreaterThanOrEqual(1, count($events));
        $this->assertEquals('hello', $events[0]->data);
    }

    public function test_full_event_with_all_fields(): void
    {
        $sseData = "event: update\nid: msg-1\nretry: 3000\ndata: {\"status\": \"ok\"}\n\n";
        $stream = $this->createStreamFromString($sseData);
        $parser = new SseParser($stream);

        $events = iterator_to_array($parser->parse());

        $this->assertCount(1, $events);
        $this->assertEquals('update', $events[0]->event);
        $this->assertEquals('msg-1', $events[0]->id);
        $this->assertEquals(3000, $events[0]->retry);
        $this->assertEquals(['status' => 'ok'], $events[0]->getJson());
    }

    /**
     * Creates a StreamingResponse from a string for testing.
     */
    private function createStreamFromString(string $data): StreamingResponse
    {
        $stream = Utils::streamFor($data);
        $response = new Response(200, ['Content-Type' => 'text/event-stream'], $stream);

        return new StreamingResponse($response);
    }
}
