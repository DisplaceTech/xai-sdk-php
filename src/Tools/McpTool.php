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

/**
 * Server-side tool for connecting to a remote MCP (Model Context Protocol) server.
 *
 * This tool enables the model to call tools on a remote MCP server.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\McpTool;
 *
 * $mcpTool = new McpTool(
 *     serverUrl: 'https://mcp-server.example.com',
 *     serverLabel: 'my-mcp',
 *     allowedToolNames: ['search', 'fetch'],
 * );
 * ```
 */
readonly class McpTool implements ServerSideTool
{
    public const TYPE = 'mcp';

    /**
     * Creates a new McpTool instance.
     *
     * @param string $serverUrl The URL of the MCP server.
     * @param string|null $serverLabel Label for the MCP server (used to prefix tool names).
     * @param string|null $serverDescription Description of the MCP server.
     * @param array<string>|null $allowedToolNames Tools the model is allowed to call.
     *        If empty, all tools are allowed.
     * @param string|null $authorization Authorization token for the server.
     * @param array<string, string>|null $extraHeaders Extra headers for the server.
     */
    public function __construct(
        public string $serverUrl,
        public ?string $serverLabel = null,
        public ?string $serverDescription = null,
        public ?array $allowedToolNames = null,
        public ?string $authorization = null,
        public ?array $extraHeaders = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $config = [
            'server_url' => $this->serverUrl,
        ];

        if ($this->serverLabel !== null) {
            $config['server_label'] = $this->serverLabel;
        }

        if ($this->serverDescription !== null) {
            $config['server_description'] = $this->serverDescription;
        }

        if ($this->allowedToolNames !== null) {
            $config['allowed_tool_names'] = $this->allowedToolNames;
        }

        if ($this->authorization !== null) {
            $config['authorization'] = $this->authorization;
        }

        if ($this->extraHeaders !== null) {
            $config['extra_headers'] = $this->extraHeaders;
        }

        return [
            self::TYPE => $config,
        ];
    }
}
