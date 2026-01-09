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

namespace Displace\XaiSdk\Tests\Unit\Resources;

use Displace\XaiSdk\Chat\DeferredCompletion;
use Displace\XaiSdk\Exceptions\DeferredCompletionException;
use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Resources\ChatResource;
use Displace\XaiSdk\Responses\ChatResponse;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;

final class ChatResourceDeferredTest extends TestCase
{
    private ClientInterface&MockObject $mockClient;

    private HttpClient $httpClient;

    private ChatResource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(ClientInterface::class);
        $this->httpClient = new HttpClient('test-api-key', $this->mockClient);
        $this->resource = new ChatResource($this->httpClient);
    }

    public function test_createDeferred_returns_deferred_completion(): void
    {
        $responseData = $this->loadJsonFixture('deferred_completion_pending.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $uri = (string) $request->getUri();

                return str_contains($uri, '/chat/completions/deferred')
                    && $request->getMethod() === 'POST';
            }))
            ->willReturn($response);

        $result = $this->resource->createDeferred([
            'model' => 'grok-3',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);

        $this->assertInstanceOf(DeferredCompletion::class, $result);
        $this->assertSame('def-abc123', $result->id);
        $this->assertTrue($result->isPending());
    }

    public function test_createDeferred_removes_stream_from_payload(): void
    {
        $responseData = $this->loadJsonFixture('deferred_completion_pending.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode($request->getBody()->getContents(), true);

                return ! isset($body['stream']);
            }))
            ->willReturn($response);

        $this->resource->createDeferred([
            'model' => 'grok-3',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'stream' => true, // This should be removed
        ]);
    }

    public function test_retrieveDeferred_returns_deferred_status(): void
    {
        $responseData = $this->loadJsonFixture('deferred_completion_pending.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $uri = (string) $request->getUri();

                return str_contains($uri, '/chat/completions/deferred/def-abc123')
                    && $request->getMethod() === 'GET';
            }))
            ->willReturn($response);

        $result = $this->resource->retrieveDeferred('def-abc123');

        $this->assertInstanceOf(DeferredCompletion::class, $result);
        $this->assertSame('def-abc123', $result->id);
    }

    public function test_retrieveDeferred_returns_completed_with_result(): void
    {
        $responseData = $this->loadJsonFixture('deferred_completion_completed.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $result = $this->resource->retrieveDeferred('def-abc123');

        $this->assertTrue($result->isCompleted());
        $this->assertNotNull($result->result);
        $this->assertInstanceOf(ChatResponse::class, $result->result);
    }

    public function test_awaitDeferred_returns_result_on_completion(): void
    {
        $responseData = $this->loadJsonFixture('deferred_completion_completed.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $result = $this->resource->awaitDeferred('def-abc123', timeout: 10, pollInterval: 1);

        $this->assertInstanceOf(ChatResponse::class, $result);
        $this->assertSame('chatcmpl-def456', $result->id);
    }

    public function test_awaitDeferred_throws_on_failure(): void
    {
        $responseData = $this->loadJsonFixture('deferred_completion_failed.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->expectException(DeferredCompletionException::class);
        $this->expectExceptionMessage('Deferred completion def-abc123 failed');

        $this->resource->awaitDeferred('def-abc123', timeout: 10, pollInterval: 1);
    }

    public function test_awaitDeferred_throws_on_cancellation(): void
    {
        $responseData = [
            'id' => 'def-abc123',
            'status' => 'cancelled',
            'created_at' => 1704067200,
        ];
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->expectException(DeferredCompletionException::class);
        $this->expectExceptionMessage('was cancelled');

        $this->resource->awaitDeferred('def-abc123', timeout: 10, pollInterval: 1);
    }

    public function test_awaitDeferred_polls_until_completion(): void
    {
        $pendingData = $this->loadJsonFixture('deferred_completion_pending.json');
        $completedData = $this->loadJsonFixture('deferred_completion_completed.json');

        $pendingResponse = new Response(200, [], json_encode($pendingData));
        $completedResponse = new Response(200, [], json_encode($completedData));

        $matcher = $this->exactly(2);
        $this->mockClient
            ->expects($matcher)
            ->method('sendRequest')
            ->willReturnCallback(function () use ($matcher, $pendingResponse, $completedResponse) {
                return match ($matcher->numberOfInvocations()) {
                    1 => $pendingResponse,
                    2 => $completedResponse,
                };
            });

        // Use a very short poll interval for testing
        $result = $this->resource->awaitDeferred('def-abc123', timeout: 10, pollInterval: 1);

        $this->assertInstanceOf(ChatResponse::class, $result);
    }

    public function test_awaitDeferred_throws_on_result_missing(): void
    {
        // Completed but no result
        $responseData = [
            'id' => 'def-abc123',
            'status' => 'completed',
            'created_at' => 1704067200,
            'completed_at' => 1704067210,
            // Missing 'result'
        ];
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->expectException(DeferredCompletionException::class);
        $this->expectExceptionMessage('result is missing');

        $this->resource->awaitDeferred('def-abc123', timeout: 10, pollInterval: 1);
    }
}
