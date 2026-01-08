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

namespace Displace\XaiSdk\Chat\Messages;

/**
 * A system message that sets the behavior of the assistant.
 */
readonly class SystemMessage implements Message
{
    /**
     * @var array<Content>
     */
    public array $content;

    /**
     * Creates a new system message.
     *
     * @param string|Content|array<Content|string> $content The message content.
     */
    public function __construct(string|Content|array $content)
    {
        $this->content = $this->normalizeContent($content);
    }

    public function getRole(): string
    {
        return 'system';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // For simple text content, use string format
        if (count($this->content) === 1 && $this->content[0]->type === Content::TYPE_TEXT) {
            return [
                'role' => 'system',
                'content' => $this->content[0]->data['text'],
            ];
        }

        // For complex content, use array format
        return [
            'role' => 'system',
            'content' => array_map(fn (Content $c) => $c->toArray(), $this->content),
        ];
    }

    /**
     * Normalizes content to an array of Content objects.
     *
     * @param string|Content|array<Content|string> $content
     *
     * @return array<Content>
     */
    private function normalizeContent(string|Content|array $content): array
    {
        if (is_string($content)) {
            return [Content::text($content)];
        }

        if ($content instanceof Content) {
            return [$content];
        }

        return array_map(
            fn (string|Content $c) => is_string($c) ? Content::text($c) : $c,
            $content,
        );
    }
}
