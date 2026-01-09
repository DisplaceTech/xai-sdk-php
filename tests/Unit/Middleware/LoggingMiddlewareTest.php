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

namespace Displace\XaiSdk\Tests\Unit\Middleware;

use Displace\XaiSdk\Middleware\LoggingMiddleware;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LoggingMiddlewareTest extends TestCase
{
    public function test_logs_request_and_response(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with($this->anything(), $this->anything(), $this->anything());

        $middleware = new LoggingMiddleware($logger);

        $request = new Request('POST', 'https://api.example.com/chat', [], '{"message":"test"}');
        $response = new Response(200, [], '{"result":"ok"}');

        $middleware->process($request, fn ($req) => $response);
    }

    public function test_redacts_authorization_header(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($context) {
                    if (isset($context['headers']['Authorization'])) {
                        return $context['headers']['Authorization'] === ['[REDACTED]'];
                    }

                    return true;
                }),
            );

        $middleware = new LoggingMiddleware($logger);

        $request = new Request(
            'POST',
            'https://api.example.com/chat',
            ['Authorization' => 'Bearer secret-key'],
            '{}',
        );

        $middleware->process($request, fn ($req) => new Response(200));
    }

    public function test_truncates_long_body(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $foundTruncated = false;

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($context) use (&$foundTruncated) {
                    if (isset($context['body']) && str_contains($context['body'], '[truncated]')) {
                        $foundTruncated = true;
                    }

                    return true;
                }),
            );

        $middleware = new LoggingMiddleware(
            $logger,
            maxBodyLength: 50,
        );

        $longBody = str_repeat('x', 100);
        $request = new Request('POST', 'https://api.example.com/chat', [], $longBody);

        $middleware->process($request, fn ($req) => new Response(200));

        $this->assertTrue($foundTruncated);
    }

    public function test_does_not_log_body_when_disabled(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($context) {
                    return ! isset($context['body']);
                }),
            );

        $middleware = new LoggingMiddleware(
            $logger,
            logBody: false,
        );

        $request = new Request('POST', 'https://api.example.com/chat', [], '{"test":"data"}');

        $middleware->process($request, fn ($req) => new Response(200, [], '{"result":"ok"}'));
    }

    public function test_logs_error_responses_at_higher_level(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logLevels = [];

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->callback(function ($level) use (&$logLevels) {
                    $logLevels[] = $level;

                    return true;
                }),
                $this->anything(),
                $this->anything(),
            );

        $middleware = new LoggingMiddleware($logger);

        $request = new Request('POST', 'https://api.example.com/chat');

        $middleware->process($request, fn ($req) => new Response(500, [], 'Internal Server Error'));

        $this->assertSame(LogLevel::DEBUG, $logLevels[0]); // Request
        $this->assertSame(LogLevel::ERROR, $logLevels[1]); // Response
    }

    public function test_logs_4xx_as_warning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $responseLogLevel = null;

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->callback(function ($level) use (&$responseLogLevel) {
                    $responseLogLevel = $level;

                    return true;
                }),
                $this->anything(),
                $this->anything(),
            );

        $middleware = new LoggingMiddleware($logger);

        $request = new Request('POST', 'https://api.example.com/chat');

        $middleware->process($request, fn ($req) => new Response(400, [], 'Bad Request'));

        $this->assertSame(LogLevel::WARNING, $responseLogLevel);
    }

    public function test_includes_request_id_for_correlation(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $requestIds = [];

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($context) use (&$requestIds) {
                    if (isset($context['request_id'])) {
                        $requestIds[] = $context['request_id'];
                    }

                    return true;
                }),
            );

        $middleware = new LoggingMiddleware($logger);

        $request = new Request('GET', 'https://api.example.com/models');

        $middleware->process($request, fn ($req) => new Response(200));

        $this->assertCount(2, $requestIds);
        $this->assertSame($requestIds[0], $requestIds[1]);
    }

    public function test_includes_duration_in_response_log(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $durationMs = null;

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($context) use (&$durationMs) {
                    if (isset($context['duration_ms'])) {
                        $durationMs = $context['duration_ms'];
                    }

                    return true;
                }),
            );

        $middleware = new LoggingMiddleware($logger);

        $request = new Request('GET', 'https://api.example.com/models');

        $middleware->process($request, fn ($req) => new Response(200));

        $this->assertNotNull($durationMs);
        $this->assertIsFloat($durationMs);
        $this->assertGreaterThanOrEqual(0, $durationMs);
    }

    public function test_custom_log_level(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                LogLevel::INFO,
                $this->anything(),
                $this->anything(),
            );

        $middleware = new LoggingMiddleware($logger, LogLevel::INFO);

        $request = new Request('GET', 'https://api.example.com/models');

        $middleware->process($request, fn ($req) => new Response(200));
    }

    public function test_custom_redact_headers(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($context) {
                    if (isset($context['headers']['X-Custom-Secret'])) {
                        return $context['headers']['X-Custom-Secret'] === ['[REDACTED]'];
                    }

                    return true;
                }),
            );

        $middleware = new LoggingMiddleware(
            $logger,
            redactHeaders: ['X-Custom-Secret'],
        );

        $request = new Request(
            'POST',
            'https://api.example.com/chat',
            ['X-Custom-Secret' => 'my-secret-value'],
        );

        $middleware->process($request, fn ($req) => new Response(200));
    }

    public function test_preserves_request_body_stream_position(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $middleware = new LoggingMiddleware($logger);

        $body = '{"test":"data"}';
        $request = new Request('POST', 'https://api.example.com/chat', [], $body);

        $middleware->process($request, function ($req) use ($body) {
            $this->assertSame($body, (string) $req->getBody());

            return new Response(200);
        });
    }
}
