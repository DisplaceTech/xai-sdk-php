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

namespace Displace\XaiSdk\Chat;

use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Chat\Messages\Content;
use Displace\XaiSdk\Chat\Messages\SystemMessage;
use Displace\XaiSdk\Chat\Messages\ToolMessage;
use Displace\XaiSdk\Chat\Messages\UserMessage;
use Displace\XaiSdk\Tools\Tool;
use Displace\XaiSdk\Tools\ToolChoice;

/**
 * Creates a new system message.
 *
 * System messages set the behavior and personality of the assistant.
 *
 * Example:
 * ```php
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     messages: [system('You are a helpful pirate assistant.')]
 * );
 * ```
 *
 * @param string|Content ...$content The message content (text or content objects).
 */
function system(string|Content ...$content): SystemMessage
{
    return new SystemMessage(array_values($content));
}

/**
 * Creates a new user message.
 *
 * User messages represent input from the user.
 *
 * Example:
 * ```php
 * $chat->append(user('Hello, how are you?'));
 *
 * // With images
 * $chat->append(user(
 *     'What do you see in this image?',
 *     image('https://example.com/photo.jpg')
 * ));
 * ```
 *
 * @param string|Content ...$content The message content (text, images, or files).
 */
function user(string|Content ...$content): UserMessage
{
    return new UserMessage(array_values($content));
}

/**
 * Creates a new assistant message.
 *
 * Assistant messages represent responses from the model. Typically used to
 * seed the conversation or for few-shot examples.
 *
 * Example:
 * ```php
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     messages: [
 *         system('You talk like a pirate.'),
 *         user('How are you?'),
 *         assistant('Ahoy! I be doing well, matey!'),
 *     ]
 * );
 * ```
 *
 * @param string $content The assistant's response content.
 */
function assistant(string $content): AssistantMessage
{
    return new AssistantMessage($content);
}

/**
 * Creates a new tool result message.
 *
 * Used to provide the result of a tool/function call back to the model.
 *
 * Example:
 * ```php
 * // After model returns a tool call
 * $result = getWeather('New York', 'C');
 * $chat->append(toolResult($result));
 * ```
 *
 * @param string $content The tool execution result.
 * @param string|null $toolCallId The ID of the tool call this result is for.
 */
function toolResult(string $content, ?string $toolCallId = null): ToolMessage
{
    return new ToolMessage($content, $toolCallId);
}

/**
 * Creates image content for use in messages.
 *
 * Images can be specified as URLs or base64-encoded strings.
 *
 * Example:
 * ```php
 * $chat->append(user(
 *     'Describe these images:',
 *     image('https://example.com/dog.jpg'),
 *     image('https://example.com/cat.jpg', detail: 'high')
 * ));
 * ```
 *
 * @param string $url The image URL or base64-encoded string.
 * @param string $detail Image resolution for processing (auto, low, high).
 */
function image(string $url, string $detail = 'auto'): Content
{
    return Content::imageUrl($url, $detail);
}

/**
 * Creates text content for use in messages.
 *
 * Most of the time you can pass strings directly. This function is useful
 * when you need to explicitly create a Content object.
 *
 * @param string $text The text content.
 */
function text(string $text): Content
{
    return Content::text($text);
}

/**
 * Creates file content for use in messages.
 *
 * Reference previously uploaded files by their ID.
 *
 * Example:
 * ```php
 * // Upload a file first
 * $file = $client->files->upload('document.pdf');
 *
 * // Then reference it in a message
 * $chat->append(user(
 *     'Summarize this document:',
 *     file($file->id)
 * ));
 * ```
 *
 * @param string $fileId The ID of a previously uploaded file.
 */
function file(string $fileId): Content
{
    return Content::file($fileId);
}

/**
 * Creates a new tool definition for function calling.
 *
 * Tools define functions that the model can call during a conversation.
 * The model will generate JSON inputs based on the parameter schema.
 *
 * Example with explicit schema:
 * ```php
 * $weatherTool = tool(
 *     name: 'get_weather',
 *     description: 'Get the weather for a given city.',
 *     parameters: [
 *         'type' => 'object',
 *         'properties' => [
 *             'city' => [
 *                 'type' => 'string',
 *                 'description' => 'The name of the city',
 *             ],
 *             'units' => [
 *                 'type' => 'string',
 *                 'enum' => ['C', 'F'],
 *                 'description' => 'Temperature units',
 *             ],
 *         ],
 *         'required' => ['city', 'units'],
 *     ],
 * );
 *
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     tools: [$weatherTool],
 * );
 * ```
 *
 * @param string $name The unique function name.
 * @param string $description What the function does.
 * @param array<string, mixed> $parameters JSON Schema for function parameters.
 */
function tool(string $name, string $description, array $parameters): Tool
{
    return new Tool($name, $description, $parameters);
}

/**
 * Creates a tool choice that forces the model to call a specific tool.
 *
 * Use this to ensure the model invokes a particular function.
 *
 * Example:
 * ```php
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     tools: [$weatherTool],
 *     toolChoice: requiredTool('get_weather'),
 * );
 * ```
 *
 * @param string $name The name of the tool to force.
 */
function requiredTool(string $name): ToolChoice
{
    return new ToolChoice($name);
}
