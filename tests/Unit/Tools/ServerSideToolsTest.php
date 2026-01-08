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

use Displace\XaiSdk\Tools\CodeExecution;
use Displace\XaiSdk\Tools\CollectionsSearch;
use Displace\XaiSdk\Tools\McpTool;
use Displace\XaiSdk\Tools\ServerSideTools;
use Displace\XaiSdk\Tools\WebSearch;
use Displace\XaiSdk\Tools\XSearch;
use PHPUnit\Framework\TestCase;

final class ServerSideToolsTest extends TestCase
{
    public function test_web_search_creates_instance(): void
    {
        $tool = ServerSideTools::webSearch();

        $this->assertInstanceOf(WebSearch::class, $tool);
        $this->assertSame('web_search', $tool->getType());
    }

    public function test_web_search_with_excluded_domains(): void
    {
        $tool = ServerSideTools::webSearch(
            excludedDomains: ['spam.com', 'ads.com'],
        );

        $array = $tool->toArray();

        $this->assertSame(['spam.com', 'ads.com'], $array['web_search']['excluded_domains']);
    }

    public function test_web_search_with_allowed_domains(): void
    {
        $tool = ServerSideTools::webSearch(
            allowedDomains: ['trusted.com'],
        );

        $array = $tool->toArray();

        $this->assertSame(['trusted.com'], $array['web_search']['allowed_domains']);
    }

    public function test_web_search_throws_on_both_domain_filters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ServerSideTools::webSearch(
            excludedDomains: ['bad.com'],
            allowedDomains: ['good.com'],
        );
    }

    public function test_web_search_with_image_understanding(): void
    {
        $tool = ServerSideTools::webSearch(
            enableImageUnderstanding: true,
        );

        $array = $tool->toArray();

        $this->assertTrue($array['web_search']['enable_image_understanding']);
    }

    public function test_x_search_creates_instance(): void
    {
        $tool = ServerSideTools::xSearch();

        $this->assertInstanceOf(XSearch::class, $tool);
        $this->assertSame('x_search', $tool->getType());
    }

    public function test_x_search_with_date_range(): void
    {
        $from = new \DateTime('2025-01-01');
        $to = new \DateTime('2025-12-31');

        $tool = ServerSideTools::xSearch(
            fromDate: $from,
            toDate: $to,
        );

        $array = $tool->toArray();

        $this->assertArrayHasKey('from_date', $array['x_search']);
        $this->assertArrayHasKey('to_date', $array['x_search']);
    }

    public function test_x_search_with_allowed_handles(): void
    {
        $tool = ServerSideTools::xSearch(
            allowedXHandles: ['xai', 'elonmusk'],
        );

        $array = $tool->toArray();

        $this->assertSame(['xai', 'elonmusk'], $array['x_search']['allowed_x_handles']);
    }

    public function test_x_search_with_excluded_handles(): void
    {
        $tool = ServerSideTools::xSearch(
            excludedXHandles: ['spam_bot'],
        );

        $array = $tool->toArray();

        $this->assertSame(['spam_bot'], $array['x_search']['excluded_x_handles']);
    }

    public function test_x_search_throws_on_both_handle_filters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ServerSideTools::xSearch(
            allowedXHandles: ['good'],
            excludedXHandles: ['bad'],
        );
    }

    public function test_x_search_with_media_understanding(): void
    {
        $tool = ServerSideTools::xSearch(
            enableImageUnderstanding: true,
            enableVideoUnderstanding: true,
        );

        $array = $tool->toArray();

        $this->assertTrue($array['x_search']['enable_image_understanding']);
        $this->assertTrue($array['x_search']['enable_video_understanding']);
    }

    public function test_code_execution_creates_instance(): void
    {
        $tool = ServerSideTools::codeExecution();

        $this->assertInstanceOf(CodeExecution::class, $tool);
        $this->assertSame('code_execution', $tool->getType());
    }

    public function test_code_execution_to_array(): void
    {
        $tool = ServerSideTools::codeExecution();

        $array = $tool->toArray();

        $this->assertArrayHasKey('code_execution', $array);
    }

    public function test_collections_search_creates_instance(): void
    {
        $tool = ServerSideTools::collectionsSearch(
            collectionIds: ['col-1', 'col-2'],
        );

        $this->assertInstanceOf(CollectionsSearch::class, $tool);
        $this->assertSame('collections_search', $tool->getType());
    }

    public function test_collections_search_with_options(): void
    {
        $tool = ServerSideTools::collectionsSearch(
            collectionIds: ['col-1'],
            limit: 5,
            instructions: 'Focus on recent documents',
            retrievalMode: 'semantic',
        );

        $array = $tool->toArray();

        $this->assertSame(['col-1'], $array['collections_search']['collection_ids']);
        $this->assertSame(5, $array['collections_search']['limit']);
        $this->assertSame('Focus on recent documents', $array['collections_search']['instructions']);
        $this->assertArrayHasKey('semantic_retrieval', $array['collections_search']);
    }

    public function test_collections_search_invalid_retrieval_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ServerSideTools::collectionsSearch(
            collectionIds: ['col-1'],
            retrievalMode: 'invalid',
        );
    }

    public function test_mcp_creates_instance(): void
    {
        $tool = ServerSideTools::mcp(
            serverUrl: 'https://mcp.example.com',
        );

        $this->assertInstanceOf(McpTool::class, $tool);
        $this->assertSame('mcp', $tool->getType());
    }

    public function test_mcp_with_all_options(): void
    {
        $tool = ServerSideTools::mcp(
            serverUrl: 'https://mcp.example.com',
            serverLabel: 'my-mcp',
            serverDescription: 'My MCP server',
            allowedToolNames: ['search', 'fetch'],
            authorization: 'Bearer token123',
            extraHeaders: ['X-Custom' => 'value'],
        );

        $array = $tool->toArray();

        $this->assertSame('https://mcp.example.com', $array['mcp']['server_url']);
        $this->assertSame('my-mcp', $array['mcp']['server_label']);
        $this->assertSame('My MCP server', $array['mcp']['server_description']);
        $this->assertSame(['search', 'fetch'], $array['mcp']['allowed_tool_names']);
        $this->assertSame('Bearer token123', $array['mcp']['authorization']);
        $this->assertSame(['X-Custom' => 'value'], $array['mcp']['extra_headers']);
    }
}
