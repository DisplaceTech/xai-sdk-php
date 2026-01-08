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

namespace Displace\XaiSdk\Responses;

/**
 * Represents a single token from tokenization.
 */
readonly class Token
{
    /**
     * Creates a new Token instance.
     *
     * @param int $tokenId The numeric token ID.
     * @param string $stringToken The string representation of the token.
     * @param string $tokenBytes The raw bytes of the token (base64 encoded).
     */
    public function __construct(
        public int $tokenId,
        public string $stringToken = '',
        public string $tokenBytes = '',
    ) {}

    /**
     * Gets the decoded token bytes.
     *
     * @return string The raw bytes of the token.
     */
    public function getDecodedBytes(): string
    {
        if ($this->tokenBytes === '') {
            return '';
        }

        $decoded = base64_decode($this->tokenBytes, strict: true);
        return $decoded !== false ? $decoded : '';
    }

    /**
     * Creates a Token from an API response array.
     *
     * @param array<string, mixed> $data The token data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tokenId: $data['token_id'] ?? $data['id'] ?? 0,
            stringToken: $data['string_token'] ?? $data['text'] ?? '',
            tokenBytes: $data['token_bytes'] ?? $data['bytes'] ?? '',
        );
    }
}
