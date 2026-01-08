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

namespace Displace\XaiSdk\Resources;

use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Responses\Token;

/**
 * Resource for tokenization API.
 *
 * This class provides access to the tokenization endpoint, allowing you to
 * tokenize text using xAI's models.
 *
 * @example
 * ```php
 * // Tokenize text
 * $tokens = $client->tokenize->tokenizeText(
 *     text: 'Hello, world!',
 *     model: 'grok-3'
 * );
 *
 * foreach ($tokens as $token) {
 *     echo "Token ID: {$token->tokenId}, Text: {$token->stringToken}\n";
 * }
 *
 * // Count tokens
 * $count = $client->tokenize->countTokens('Hello, world!', 'grok-3');
 * echo "Token count: $count\n";
 * ```
 */
class TokenizerResource
{
    private const ENDPOINT = '/tokenize';

    /**
     * Creates a new TokenizerResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * Tokenizes text using the specified model.
     *
     * @param string $text The text to tokenize.
     * @param string $model The model to use for tokenization.
     *
     * @return array<Token> The list of tokens.
     */
    public function tokenizeText(string $text, string $model = 'grok-3'): array
    {
        $payload = [
            'text' => $text,
            'model' => $model,
        ];

        $response = $this->httpClient->post(self::ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            fn (array $tokenData) => Token::fromArray($tokenData),
            $data['tokens'] ?? $data['data'] ?? [],
        );
    }

    /**
     * Counts the number of tokens in the text.
     *
     * This is a convenience method that tokenizes the text and returns the count.
     *
     * @param string $text The text to count tokens for.
     * @param string $model The model to use for tokenization.
     *
     * @return int The number of tokens.
     */
    public function countTokens(string $text, string $model = 'grok-3'): int
    {
        $tokens = $this->tokenizeText($text, $model);

        return count($tokens);
    }

    /**
     * Tokenizes multiple texts in a batch.
     *
     * @param array<string> $texts The texts to tokenize.
     * @param string $model The model to use for tokenization.
     *
     * @return array<array<Token>> An array of token arrays, one per input text.
     */
    public function tokenizeBatch(array $texts, string $model = 'grok-3'): array
    {
        return array_map(
            fn (string $text) => $this->tokenizeText($text, $model),
            $texts,
        );
    }

    /**
     * Decodes token IDs back to text.
     *
     * @param array<int> $tokenIds The token IDs to decode.
     * @param string $model The model to use for decoding.
     *
     * @return string The decoded text.
     */
    public function decode(array $tokenIds, string $model = 'grok-3'): string
    {
        $payload = [
            'token_ids' => $tokenIds,
            'model' => $model,
        ];

        $response = $this->httpClient->post(self::ENDPOINT . '/decode', $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $data['text'] ?? '';
    }
}
