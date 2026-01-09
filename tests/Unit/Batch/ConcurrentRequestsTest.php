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

namespace Displace\XaiSdk\Tests\Unit\Batch;

use Displace\XaiSdk\Batch\ConcurrentRequests;
use Displace\XaiSdk\Batch\RequestResult;
use Displace\XaiSdk\Exceptions\BatchException;
use Displace\XaiSdk\Tests\TestCase;
use Displace\XaiSdk\XaiClient;
use Exception;
use Mockery;
use RuntimeException;

final class ConcurrentRequestsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_run_with_empty_callbacks(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::run($client, []);

        $this->assertSame([], $results);
    }

    public function test_run_executes_callbacks_in_order(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::run($client, [
            fn ($c) => 'first',
            fn ($c) => 'second',
            fn ($c) => 'third',
        ]);

        $this->assertCount(3, $results);
        $this->assertSame('first', $results[0]->getValue());
        $this->assertSame('second', $results[1]->getValue());
        $this->assertSame('third', $results[2]->getValue());
    }

    public function test_run_handles_failures_per_request(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::run($client, [
            fn ($c) => 'success',
            fn ($c) => throw new Exception('fail'),
            fn ($c) => 'another success',
        ]);

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]->isSuccess());
        $this->assertTrue($results[1]->isFailure());
        $this->assertTrue($results[2]->isSuccess());

        $this->assertSame('success', $results[0]->getValue());
        $this->assertSame('fail', $results[1]->getError()->getMessage());
        $this->assertSame('another success', $results[2]->getValue());
    }

    public function test_run_passes_client_to_callbacks(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::run($client, [
            fn ($c) => $c === $client ? 'correct' : 'wrong',
        ]);

        $this->assertSame('correct', $results[0]->getValue());
    }

    public function test_run_throws_on_invalid_concurrency(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $this->expectException(BatchException::class);
        $this->expectExceptionMessage('Concurrency must be at least 1');

        ConcurrentRequests::run($client, [fn ($c) => 'test'], concurrency: 0);
    }

    public function test_all_returns_all_values(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::all($client, [
            fn ($c) => 'first',
            fn ($c) => 'second',
        ]);

        $this->assertSame(['first', 'second'], array_values($results));
    }

    public function test_all_throws_on_any_failure(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $this->expectException(BatchException::class);
        $this->expectExceptionMessage('1 of 3 requests failed');

        ConcurrentRequests::all($client, [
            fn ($c) => 'success',
            fn ($c) => throw new Exception('fail'),
            fn ($c) => 'success',
        ]);
    }

    public function test_all_provides_failure_details(): void
    {
        $client = Mockery::mock(XaiClient::class);

        try {
            ConcurrentRequests::all($client, [
                fn ($c) => 'success',
                fn ($c) => throw new RuntimeException('error 1'),
                fn ($c) => throw new RuntimeException('error 2'),
            ]);
            $this->fail('Expected BatchException');
        } catch (BatchException $e) {
            $this->assertSame(2, $e->getFailureCount());
            $this->assertContains(1, $e->getFailedIndices());
            $this->assertContains(2, $e->getFailedIndices());
            $this->assertSame('error 1', $e->getFailure(1)?->getMessage());
            $this->assertSame('error 2', $e->getFailure(2)?->getMessage());
        }
    }

    public function test_settled_returns_only_successes(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::settled($client, [
            fn ($c) => 'first',
            fn ($c) => throw new Exception('fail'),
            fn ($c) => 'third',
        ]);

        $this->assertCount(2, $results);
        $this->assertSame('first', $results[0]);
        $this->assertSame('third', $results[2]);
        $this->assertArrayNotHasKey(1, $results);
    }

    public function test_settled_returns_empty_on_all_failures(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::settled($client, [
            fn ($c) => throw new Exception('fail 1'),
            fn ($c) => throw new Exception('fail 2'),
        ]);

        $this->assertSame([], $results);
    }

    public function test_concurrency_batching(): void
    {
        $client = Mockery::mock(XaiClient::class);
        $executionOrder = [];

        $results = ConcurrentRequests::run($client, [
            function ($c) use (&$executionOrder) {
                $executionOrder[] = 0;

                return 'a';
            },
            function ($c) use (&$executionOrder) {
                $executionOrder[] = 1;

                return 'b';
            },
            function ($c) use (&$executionOrder) {
                $executionOrder[] = 2;

                return 'c';
            },
        ], concurrency: 2);

        $this->assertCount(3, $results);
        // All callbacks should be executed (order depends on batching)
        $this->assertCount(3, $executionOrder);
    }

    public function test_results_are_request_result_instances(): void
    {
        $client = Mockery::mock(XaiClient::class);

        $results = ConcurrentRequests::run($client, [
            fn ($c) => 'test',
        ]);

        $this->assertInstanceOf(RequestResult::class, $results[0]);
    }
}
