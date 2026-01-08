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
 * Represents the result of a tool call execution.
 *
 * When you execute a function that the model requested, you wrap the result
 * in a ToolResult and append it to the conversation so the model can
 * use the information to formulate its response.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\ToolResult;
 *
 * // Execute the function requested by the model
 * $weather = getWeather($city, $units);
 *
 * // Create a tool result with the response
 * $result = new ToolResult("The weather in {$city} is sunny at 72{$units}.");
 *
 * // Append to conversation
 * $chat->append($result);
 * ```
 */
readonly class ToolResult
{
    /**
     * The role identifier for tool results in API messages.
     */
    public const ROLE = 'tool';

    /**
     * Creates a new ToolResult instance.
     *
     * @param string $content The result content from executing the tool.
     * @param string|null $toolCallId Optional ID linking this result to a specific tool call.
     */
    public function __construct(
        public string $content,
        public ?string $toolCallId = null,
    ) {}

    /**
     * Gets the role for this message type.
     *
     * @return string The role identifier.
     */
    public function getRole(): string
    {
        return self::ROLE;
    }

    /**
     * Converts the tool result to a message array format.
     *
     * @return array{role: string, content: string, tool_call_id?: string}
     */
    public function toArray(): array
    {
        $result = [
            'role' => self::ROLE,
            'content' => $this->content,
        ];

        if ($this->toolCallId !== null) {
            $result['tool_call_id'] = $this->toolCallId;
        }

        return $result;
    }

    /**
     * Creates a ToolResult from an array representation.
     *
     * @param array{content: string, tool_call_id?: string} $data The array data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            toolCallId: $data['tool_call_id'] ?? null,
        );
    }
}
