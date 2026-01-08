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
 * Represents a tool choice configuration for chat requests.
 *
 * Tool choice controls which (if any) tool the model calls:
 * - 'none': Model will not call any tool
 * - 'auto': Model can pick between generating a message or calling tools
 * - 'required': Model must call one or more tools
 * - specific function: Force the model to call a specific tool
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\ToolChoice;
 *
 * // Force a specific tool
 * $choice = new ToolChoice('get_weather');
 *
 * // Or use mode constants
 * $choice = ToolChoice::auto();
 * $choice = ToolChoice::none();
 * $choice = ToolChoice::required();
 * ```
 */
readonly class ToolChoice
{
    public const MODE_NONE = 'none';

    public const MODE_AUTO = 'auto';

    public const MODE_REQUIRED = 'required';

    /**
     * Creates a new ToolChoice instance.
     *
     * @param string|null $functionName Force calling this specific function.
     * @param string|null $mode The tool choice mode (none, auto, required).
     */
    public function __construct(
        public ?string $functionName = null,
        public ?string $mode = null,
    ) {
    }

    /**
     * Creates a ToolChoice with mode 'none'.
     *
     * The model will not call any tools.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self(mode: self::MODE_NONE);
    }

    /**
     * Creates a ToolChoice with mode 'auto'.
     *
     * The model can pick between generating a message or calling tools.
     *
     * @return self
     */
    public static function auto(): self
    {
        return new self(mode: self::MODE_AUTO);
    }

    /**
     * Creates a ToolChoice with mode 'required'.
     *
     * The model must call one or more tools.
     *
     * @return self
     */
    public static function required(): self
    {
        return new self(mode: self::MODE_REQUIRED);
    }

    /**
     * Creates a ToolChoice for a specific function.
     *
     * Forces the model to call the specified tool.
     *
     * @param string $name The function name to force.
     *
     * @return self
     */
    public static function function(string $name): self
    {
        return new self(functionName: $name);
    }

    /**
     * Converts the tool choice to an array representation for API requests.
     *
     * @return array{type?: string, mode?: string, function?: array{name: string}}|string
     */
    public function toArray(): array|string
    {
        if ($this->functionName !== null) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $this->functionName,
                ],
            ];
        }

        if ($this->mode !== null) {
            return $this->mode;
        }

        return self::MODE_AUTO;
    }
}
