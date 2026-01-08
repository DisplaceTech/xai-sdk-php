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
 * Represents the function details within a tool call.
 *
 * This class holds the name and arguments of a function that the model
 * wants to invoke.
 */
readonly class FunctionCall
{
    /**
     * Creates a new FunctionCall instance.
     *
     * @param string $name The name of the function to call.
     * @param string $arguments The JSON-encoded arguments for the function.
     */
    public function __construct(
        public string $name,
        public string $arguments,
    ) {}

    /**
     * Decodes the arguments as an associative array.
     *
     * @return array<string, mixed> The decoded arguments.
     * @throws \JsonException If the arguments are not valid JSON.
     */
    public function getDecodedArguments(): array
    {
        return json_decode($this->arguments, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Converts the function call to an array representation.
     *
     * @return array{name: string, arguments: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }

    /**
     * Creates a FunctionCall from an array representation.
     *
     * @param array{name: string, arguments: string} $data The array data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            arguments: $data['arguments'],
        );
    }
}
