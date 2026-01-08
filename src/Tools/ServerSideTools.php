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

namespace Displace\XaiSdk\Tools;

use DateTimeInterface;

/**
 * Factory class for creating server-side tools.
 *
 * Server-side tools are executed by xAI's servers rather than client-side.
 * They include web search, X (Twitter) search, code execution, and more.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\ServerSideTools;
 *
 * $chat = $client->chat->create(
 *     model: 'grok-4-fast',
 *     tools: [
 *         ServerSideTools::webSearch(),
 *         ServerSideTools::xSearch(),
 *         ServerSideTools::codeExecution(),
 *     ],
 * );
 * ```
 */
final class ServerSideTools
{
    /**
     * Creates a server-side tool for web search.
     *
     * This tool enables the model to perform web searches and access online content
     * during conversation.
     *
     * @param array<string>|null $excludedDomains Domains to exclude from search results (max 5).
     * @param array<string>|null $allowedDomains Domains to restrict search results to (max 5).
     * @param bool $enableImageUnderstanding Enable image understanding during search.
     * @return WebSearch The configured web search tool.
     */
    public static function webSearch(
        ?array $excludedDomains = null,
        ?array $allowedDomains = null,
        bool $enableImageUnderstanding = false,
    ): WebSearch {
        return new WebSearch(
            excludedDomains: $excludedDomains,
            allowedDomains: $allowedDomains,
            enableImageUnderstanding: $enableImageUnderstanding,
        );
    }

    /**
     * Creates a server-side tool for X (Twitter) search.
     *
     * This tool enables the model to search X posts and access social media
     * content during conversation.
     *
     * @param DateTimeInterface|null $fromDate Start date for search results.
     * @param DateTimeInterface|null $toDate End date for search results.
     * @param array<string>|null $allowedXHandles X handles to limit search to (without @).
     * @param array<string>|null $excludedXHandles X handles to exclude (without @).
     * @param bool $enableImageUnderstanding Enable image understanding for posts.
     * @param bool $enableVideoUnderstanding Enable video understanding for posts.
     * @return XSearch The configured X search tool.
     */
    public static function xSearch(
        ?DateTimeInterface $fromDate = null,
        ?DateTimeInterface $toDate = null,
        ?array $allowedXHandles = null,
        ?array $excludedXHandles = null,
        bool $enableImageUnderstanding = false,
        bool $enableVideoUnderstanding = false,
    ): XSearch {
        return new XSearch(
            fromDate: $fromDate,
            toDate: $toDate,
            allowedXHandles: $allowedXHandles,
            excludedXHandles: $excludedXHandles,
            enableImageUnderstanding: $enableImageUnderstanding,
            enableVideoUnderstanding: $enableVideoUnderstanding,
        );
    }

    /**
     * Creates a server-side tool for code execution.
     *
     * This tool enables the model to execute code during conversation, allowing
     * it to run computations, test algorithms, or perform data analysis.
     *
     * @return CodeExecution The configured code execution tool.
     */
    public static function codeExecution(): CodeExecution
    {
        return new CodeExecution();
    }

    /**
     * Creates a server-side tool for collections search (RAG).
     *
     * This tool enables the model to search collections and access document content
     * during conversation.
     *
     * @param array<string> $collectionIds The IDs of collections to search (max 10).
     * @param int|null $limit Maximum number of results to return.
     * @param string|null $instructions Custom search instructions.
     * @param string|null $retrievalMode Retrieval strategy: 'hybrid', 'semantic', or 'keyword'.
     * @return CollectionsSearch The configured collections search tool.
     */
    public static function collectionsSearch(
        array $collectionIds,
        ?int $limit = null,
        ?string $instructions = null,
        ?string $retrievalMode = null,
    ): CollectionsSearch {
        return new CollectionsSearch(
            collectionIds: $collectionIds,
            limit: $limit,
            instructions: $instructions,
            retrievalMode: $retrievalMode,
        );
    }

    /**
     * Creates a server-side tool for connecting to a remote MCP server.
     *
     * This tool enables the model to call tools on a remote MCP server.
     *
     * @param string $serverUrl The URL of the MCP server.
     * @param string|null $serverLabel Label for the MCP server (used to prefix tool names).
     * @param string|null $serverDescription Description of the MCP server.
     * @param array<string>|null $allowedToolNames Tools the model is allowed to call.
     * @param string|null $authorization Authorization token for the server.
     * @param array<string, string>|null $extraHeaders Extra headers for the server.
     * @return McpTool The configured MCP tool.
     */
    public static function mcp(
        string $serverUrl,
        ?string $serverLabel = null,
        ?string $serverDescription = null,
        ?array $allowedToolNames = null,
        ?string $authorization = null,
        ?array $extraHeaders = null,
    ): McpTool {
        return new McpTool(
            serverUrl: $serverUrl,
            serverLabel: $serverLabel,
            serverDescription: $serverDescription,
            allowedToolNames: $allowedToolNames,
            authorization: $authorization,
            extraHeaders: $extraHeaders,
        );
    }
}
