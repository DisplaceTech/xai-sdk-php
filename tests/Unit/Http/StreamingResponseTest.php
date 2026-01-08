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

namespace Displace\XaiSdk\Tests\Unit\Http;

use Displace\XaiSdk\Http\StreamingResponse;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

final class StreamingResponseTest extends TestCase
{
    public function test_get_status_code(): void
    {
        $response = $this->createStreamingResponse(200, '');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_get_header(): void
    {
        $response = $this->createStreamingResponse(200, '', [
            'Content-Type' => 'text/event-stream',
        ]);

        $this->assertEquals('text/event-stream', $response->getHeader('Content-Type'));
    }

    public function test_get_content_type(): void
    {
        $response = $this->createStreamingResponse(200, '', [
            'Content-Type' => 'text/event-stream; charset=utf-8',
        ]);

        $this->assertEquals('text/event-stream; charset=utf-8', $response->getContentType());
    }

    public function test_is_event_stream_returns_true_for_sse(): void
    {
        $response = $this->createStreamingResponse(200, '', [
            'Content-Type' => 'text/event-stream',
        ]);

        $this->assertTrue($response->isEventStream());
    }

    public function test_is_event_stream_returns_false_for_json(): void
    {
        $response = $this->createStreamingResponse(200, '', [
            'Content-Type' => 'application/json',
        ]);

        $this->assertFalse($response->isEventStream());
    }

    public function test_read_returns_bytes(): void
    {
        $response = $this->createStreamingResponse(200, 'Hello World');

        $this->assertEquals('Hello', $response->read(5));
    }

    public function test_eof_returns_false_when_data_remains(): void
    {
        $response = $this->createStreamingResponse(200, 'data');

        $this->assertFalse($response->eof());
    }

    public function test_eof_returns_true_after_reading_past_end(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $response->read(4);
        // Stream only knows it's EOF after trying to read more
        $response->read(1);

        $this->assertTrue($response->eof());
    }

    public function test_read_line_returns_line(): void
    {
        $response = $this->createStreamingResponse(200, "line1\nline2\nline3");

        $this->assertEquals('line1', $response->readLine());
        $this->assertEquals('line2', $response->readLine());
        $this->assertEquals('line3', $response->readLine());
    }

    public function test_read_line_handles_crlf(): void
    {
        $response = $this->createStreamingResponse(200, "line1\r\nline2\r\n");

        $this->assertEquals('line1', $response->readLine());
        $this->assertEquals('line2', $response->readLine());
    }

    public function test_read_line_returns_null_at_eof(): void
    {
        $response = $this->createStreamingResponse(200, "line\n");
        $response->readLine(); // reads "line"
        $response->readLine(); // may return empty string for the empty after newline

        // After consuming all data, should return null
        $this->assertNull($response->readLine());
    }

    public function test_close_closes_stream(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $response->close();

        $this->assertTrue($response->isClosed());
    }

    public function test_close_is_idempotent(): void
    {
        $response = $this->createStreamingResponse(200, 'data');

        $response->close();
        $response->close(); // Should not throw

        $this->assertTrue($response->isClosed());
    }

    public function test_read_returns_empty_after_close(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $response->close();

        $this->assertEquals('', $response->read(10));
    }

    public function test_eof_returns_true_after_close(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $response->close();

        $this->assertTrue($response->eof());
    }

    public function test_read_line_returns_null_after_close(): void
    {
        $response = $this->createStreamingResponse(200, "line\n");
        $response->close();

        $this->assertNull($response->readLine());
    }

    public function test_detach_returns_stream(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $stream = $response->detach();

        $this->assertNotNull($stream);
        $this->assertEquals('data', $stream->getContents());
    }

    public function test_detach_marks_as_closed(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $response->detach();

        $this->assertTrue($response->isClosed());
    }

    public function test_get_response_returns_psr_response(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $psr = $response->getResponse();

        $this->assertEquals(200, $psr->getStatusCode());
    }

    public function test_get_stream_returns_stream_interface(): void
    {
        $response = $this->createStreamingResponse(200, 'data');
        $stream = $response->getStream();

        $this->assertEquals('data', $stream->getContents());
    }

    private function createStreamingResponse(int $status, string $body, array $headers = []): StreamingResponse
    {
        $stream = Utils::streamFor($body);
        $response = new Response($status, $headers, $stream);

        return new StreamingResponse($response);
    }
}
