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
 * Represents a tool call from the model's response.
 *
 * When the model decides to call a function/tool, it returns a ToolCall object
 * containing the call ID, type, and function details including name and arguments.
 *
 * @example
 * ```php
 * // Accessing tool calls from a response
 * foreach ($response->toolCalls as $toolCall) {
 *     $args = $toolCall->function->getDecodedArguments();
 *     $result = callFunction($toolCall->function->name, $args);
 *     $chat->append(toolResult($result));
 * }
 * ```
 */
readonly class ToolCall
{
    /**
     * Tool call type constants.
     */
    public const TYPE_CLIENT_SIDE = 'client_side_tool';
    public const TYPE_WEB_SEARCH = 'web_search_tool';
    public const TYPE_X_SEARCH = 'x_search_tool';
    public const TYPE_CODE_EXECUTION = 'code_execution_tool';
    public const TYPE_COLLECTIONS_SEARCH = 'collections_search_tool';
    public const TYPE_MCP = 'mcp_tool';
    public const TYPE_ATTACHMENT_SEARCH = 'attachment_search_tool';

    /**
     * Creates a new ToolCall instance.
     *
     * @param string $id The unique identifier for this tool call.
     * @param string $type The type of tool call (e.g., 'function').
     * @param FunctionCall $function The function call details.
     * @param string|null $callType The internal tool call type (client_side_tool, web_search_tool, etc.).
     */
    public function __construct(
        public string $id,
        public string $type,
        public FunctionCall $function,
        public ?string $callType = null,
    ) {}

    /**
     * Checks if this is a client-side tool call.
     *
     * @return bool True if this is a client-side tool call.
     */
    public function isClientSideTool(): bool
    {
        return $this->callType === self::TYPE_CLIENT_SIDE || $this->callType === null;
    }

    /**
     * Checks if this is a server-side tool call.
     *
     * @return bool True if this is a server-side tool call.
     */
    public function isServerSideTool(): bool
    {
        return $this->callType !== null && $this->callType !== self::TYPE_CLIENT_SIDE;
    }

    /**
     * Gets the tool call type as a string.
     *
     * @return string The tool call type.
     */
    public function getToolCallType(): string
    {
        return $this->callType ?? self::TYPE_CLIENT_SIDE;
    }

    /**
     * Converts the tool call to an array representation.
     *
     * @return array{id: string, type: string, function: array{name: string, arguments: string}}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'function' => $this->function->toArray(),
        ];
    }

    /**
     * Creates a ToolCall from an array representation.
     *
     * @param array{id: string, type: string, function: array{name: string, arguments: string}, call_type?: string} $data The array data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            type: $data['type'],
            function: FunctionCall::fromArray($data['function']),
            callType: $data['call_type'] ?? null,
        );
    }
}
