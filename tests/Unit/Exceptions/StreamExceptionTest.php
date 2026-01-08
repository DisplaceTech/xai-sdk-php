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

namespace Displace\XaiSdk\Tests\Unit\Exceptions;

use Displace\XaiSdk\Exceptions\StreamException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use Exception;
use JsonException;

final class StreamExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new StreamException();

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_has_default_message(): void
    {
        $exception = new StreamException();

        $this->assertEquals('A streaming error occurred.', $exception->getMessage());
    }

    public function test_accepts_custom_message(): void
    {
        $exception = new StreamException('Custom stream error');

        $this->assertEquals('Custom stream error', $exception->getMessage());
    }

    public function test_malformed_data_creates_exception(): void
    {
        $exception = StreamException::malformedData('invalid data');

        $this->assertInstanceOf(StreamException::class, $exception);
        $this->assertStringContainsString('Malformed SSE data', $exception->getMessage());
        $this->assertStringContainsString('invalid data', $exception->getMessage());
    }

    public function test_malformed_data_truncates_long_data(): void
    {
        $longData = str_repeat('x', 200);
        $exception = StreamException::malformedData($longData);

        $this->assertStringContainsString('...', $exception->getMessage());
        $this->assertLessThan(200, strlen($exception->getMessage()));
    }

    public function test_malformed_data_includes_previous_exception(): void
    {
        $previous = new Exception('Original error');
        $exception = StreamException::malformedData('data', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_json_parse_error_creates_exception(): void
    {
        $exception = StreamException::jsonParseError('{"invalid');

        $this->assertInstanceOf(StreamException::class, $exception);
        $this->assertStringContainsString('Failed to parse JSON', $exception->getMessage());
        $this->assertStringContainsString('{"invalid', $exception->getMessage());
    }

    public function test_json_parse_error_truncates_long_json(): void
    {
        $longJson = str_repeat('x', 200);
        $exception = StreamException::jsonParseError($longJson);

        $this->assertStringContainsString('...', $exception->getMessage());
    }

    public function test_json_parse_error_includes_previous_exception(): void
    {
        $previous = new JsonException('Syntax error');
        $exception = StreamException::jsonParseError('bad json', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_unexpected_termination_creates_exception(): void
    {
        $exception = StreamException::unexpectedTermination();

        $this->assertInstanceOf(StreamException::class, $exception);
        $this->assertStringContainsString('Stream terminated unexpectedly', $exception->getMessage());
    }

    public function test_unexpected_termination_includes_reason(): void
    {
        $exception = StreamException::unexpectedTermination('Connection reset');

        $this->assertStringContainsString('Connection reset', $exception->getMessage());
    }

    public function test_read_error_creates_exception(): void
    {
        $exception = StreamException::readError();

        $this->assertInstanceOf(StreamException::class, $exception);
        $this->assertStringContainsString('Error reading from stream', $exception->getMessage());
    }

    public function test_read_error_includes_previous_exception(): void
    {
        $previous = new Exception('Socket closed');
        $exception = StreamException::readError($previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
