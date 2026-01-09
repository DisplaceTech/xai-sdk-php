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

namespace Displace\XaiSdk\Tests\Unit\Tools;

use DateTimeImmutable;
use Displace\XaiSdk\Tools\CodeExecution;
use Displace\XaiSdk\Tools\CollectionsSearch;
use Displace\XaiSdk\Tools\McpTool;
use Displace\XaiSdk\Tools\WebSearch;
use Displace\XaiSdk\Tools\XSearch;
use PHPUnit\Framework\TestCase;

final class ServerSideToolResponsesFormatTest extends TestCase
{
    public function test_x_search_to_responses_array_basic(): void
    {
        $tool = new XSearch();
        $result = $tool->toResponsesArray();

        $this->assertSame(['type' => 'x_search'], $result);
    }

    public function test_x_search_to_responses_array_with_dates(): void
    {
        $fromDate = new DateTimeImmutable('2026-01-01');
        $toDate = new DateTimeImmutable('2026-01-08');

        $tool = new XSearch(
            fromDate: $fromDate,
            toDate: $toDate,
        );
        $result = $tool->toResponsesArray();

        $this->assertSame('x_search', $result['type']);
        $this->assertSame('2026-01-01', $result['from_date']);
        $this->assertSame('2026-01-08', $result['to_date']);
    }

    public function test_x_search_to_responses_array_with_handles(): void
    {
        $tool = new XSearch(
            allowedXHandles: ['xai', 'elonmusk'],
        );
        $result = $tool->toResponsesArray();

        $this->assertSame('x_search', $result['type']);
        $this->assertSame(['xai', 'elonmusk'], $result['x_handles']);
    }

    public function test_web_search_to_responses_array_basic(): void
    {
        $tool = new WebSearch();
        $result = $tool->toResponsesArray();

        $this->assertSame(['type' => 'web_search'], $result);
    }

    public function test_web_search_to_responses_array_with_excluded_domains(): void
    {
        $tool = new WebSearch(
            excludedDomains: ['spam.com', 'ads.net'],
        );
        $result = $tool->toResponsesArray();

        $this->assertSame('web_search', $result['type']);
        $this->assertSame(['spam.com', 'ads.net'], $result['excluded_websites']);
    }

    public function test_web_search_to_responses_array_with_allowed_domains(): void
    {
        $tool = new WebSearch(
            allowedDomains: ['docs.php.net', 'laravel.com'],
        );
        $result = $tool->toResponsesArray();

        $this->assertSame('web_search', $result['type']);
        $this->assertSame(['docs.php.net', 'laravel.com'], $result['allowed_websites']);
    }

    public function test_code_execution_to_responses_array(): void
    {
        $tool = new CodeExecution();
        $result = $tool->toResponsesArray();

        $this->assertSame(['type' => 'code_execution'], $result);
    }

    public function test_collections_search_to_responses_array(): void
    {
        $tool = new CollectionsSearch(
            collectionIds: ['col_123', 'col_456'],
            limit: 5,
            instructions: 'Focus on recent documents',
            retrievalMode: 'semantic',
        );
        $result = $tool->toResponsesArray();

        $this->assertSame('collections_search', $result['type']);
        $this->assertSame(['col_123', 'col_456'], $result['collection_ids']);
        $this->assertSame(5, $result['limit']);
        $this->assertSame('Focus on recent documents', $result['instructions']);
        $this->assertSame('semantic', $result['retrieval_mode']);
    }

    public function test_mcp_tool_to_responses_array(): void
    {
        $tool = new McpTool(
            serverUrl: 'https://mcp.example.com',
            serverLabel: 'my-server',
            allowedToolNames: ['search', 'fetch'],
        );
        $result = $tool->toResponsesArray();

        $this->assertSame('mcp', $result['type']);
        $this->assertSame('https://mcp.example.com', $result['server_url']);
        $this->assertSame('my-server', $result['server_label']);
        $this->assertSame(['search', 'fetch'], $result['allowed_tool_names']);
    }
}
