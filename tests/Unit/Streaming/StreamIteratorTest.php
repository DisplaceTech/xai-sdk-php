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
use Displace\XaiSdk\Streaming\StreamIterator;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

final class StreamIteratorTest extends TestCase
{
    public function test_iterates_over_json_chunks(): void
    {
        $chunk1 = json_encode(['id' => '1', 'data' => 'first']);
        $chunk2 = json_encode(['id' => '2', 'data' => 'second']);
        $sseData = "data: {$chunk1}\n\ndata: {$chunk2}\n\n";

        $iterator = $this->createIterator($sseData);
        $chunks = iterator_to_array($iterator);

        $this->assertCount(2, $chunks);
        $this->assertEquals('first', $chunks[0]['data']);
        $this->assertEquals('second', $chunks[1]['data']);
    }

    public function test_skips_done_event(): void
    {
        $chunk = json_encode(['id' => '1', 'data' => 'test']);
        $sseData = "data: {$chunk}\n\ndata: [DONE]\n\n";

        $iterator = $this->createIterator($sseData);
        $chunks = iterator_to_array($iterator);

        $this->assertCount(1, $chunks);
    }

    public function test_skips_empty_events(): void
    {
        $chunk = json_encode(['id' => '1']);
        $sseData = "data: \n\ndata: {$chunk}\n\n";

        $iterator = $this->createIterator($sseData);
        $chunks = iterator_to_array($iterator);

        // Empty data yields no chunk, only the valid JSON chunk
        $this->assertCount(1, $chunks);
    }

    public function test_skips_invalid_json(): void
    {
        $validChunk = json_encode(['id' => '1']);
        $sseData = "data: {invalid json}\n\ndata: {$validChunk}\n\n";

        $iterator = $this->createIterator($sseData);
        $chunks = iterator_to_array($iterator);

        // Invalid JSON is skipped via getJson() returning null
        $this->assertCount(1, $chunks);
        $this->assertEquals('1', $chunks[0]['id']);
    }

    public function test_current_returns_chunk_data(): void
    {
        $chunk = json_encode(['key' => 'value']);
        $sseData = "data: {$chunk}\n\n";

        $iterator = $this->createIterator($sseData);

        $this->assertEquals(['key' => 'value'], $iterator->current());
    }

    public function test_key_returns_index(): void
    {
        $chunk1 = json_encode(['id' => '1']);
        $chunk2 = json_encode(['id' => '2']);
        $sseData = "data: {$chunk1}\n\ndata: {$chunk2}\n\n";

        $iterator = $this->createIterator($sseData);

        $this->assertEquals(1, $iterator->key());
        $iterator->next();
        $this->assertEquals(2, $iterator->key());
    }

    public function test_valid_returns_true_when_data_available(): void
    {
        $chunk = json_encode(['id' => '1']);
        $sseData = "data: {$chunk}\n\n";

        $iterator = $this->createIterator($sseData);

        $this->assertTrue($iterator->valid());
    }

    public function test_valid_returns_false_at_end(): void
    {
        $chunk = json_encode(['id' => '1']);
        $sseData = "data: {$chunk}\n\n";

        $iterator = $this->createIterator($sseData);
        $iterator->next();

        $this->assertFalse($iterator->valid());
    }

    public function test_to_array_collects_all_chunks(): void
    {
        $chunk1 = json_encode(['n' => 1]);
        $chunk2 = json_encode(['n' => 2]);
        $chunk3 = json_encode(['n' => 3]);
        $sseData = "data: {$chunk1}\n\ndata: {$chunk2}\n\ndata: {$chunk3}\n\n";

        $iterator = $this->createIterator($sseData);
        $chunks = $iterator->toArray();

        $this->assertCount(3, $chunks);
        $this->assertEquals(1, $chunks[0]['n']);
        $this->assertEquals(2, $chunks[1]['n']);
        $this->assertEquals(3, $chunks[2]['n']);
    }

    public function test_from_response_creates_iterator(): void
    {
        $chunk = json_encode(['test' => true]);
        $sseData = "data: {$chunk}\n\n";
        $stream = $this->createStreamingResponse($sseData);

        $iterator = StreamIterator::fromResponse($stream);

        $this->assertInstanceOf(StreamIterator::class, $iterator);
        $this->assertEquals(['test' => true], $iterator->current());
    }

    public function test_handles_chat_completion_chunks(): void
    {
        $chunk1 = json_encode([
            'id' => 'chatcmpl-123',
            'model' => 'grok-3',
            'choices' => [['index' => 0, 'delta' => ['content' => 'Hello']]],
        ]);
        $chunk2 = json_encode([
            'id' => 'chatcmpl-123',
            'model' => 'grok-3',
            'choices' => [['index' => 0, 'delta' => ['content' => ' World']]],
        ]);
        $sseData = "data: {$chunk1}\n\ndata: {$chunk2}\n\ndata: [DONE]\n\n";

        $iterator = $this->createIterator($sseData);
        $chunks = $iterator->toArray();

        $this->assertCount(2, $chunks);
        $this->assertEquals('Hello', $chunks[0]['choices'][0]['delta']['content']);
        $this->assertEquals(' World', $chunks[1]['choices'][0]['delta']['content']);
    }

    public function test_empty_stream_yields_no_chunks(): void
    {
        $iterator = $this->createIterator('');
        $chunks = iterator_to_array($iterator);

        $this->assertCount(0, $chunks);
    }

    public function test_only_done_yields_no_chunks(): void
    {
        $iterator = $this->createIterator("data: [DONE]\n\n");
        $chunks = iterator_to_array($iterator);

        $this->assertCount(0, $chunks);
    }

    private function createIterator(string $sseData): StreamIterator
    {
        return new StreamIterator($this->createStreamingResponse($sseData));
    }

    private function createStreamingResponse(string $data): StreamingResponse
    {
        $stream = Utils::streamFor($data);
        $response = new Response(200, ['Content-Type' => 'text/event-stream'], $stream);

        return new StreamingResponse($response);
    }
}
