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
 * Represents a tool (function) that the model can call.
 *
 * Tools define functions that the model can invoke during a conversation to perform
 * specific tasks, such as getting weather data or executing code. The model uses
 * the tool's JSON schema to generate valid inputs.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\Tool;
 *
 * $weatherTool = new Tool(
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
 * ```
 */
readonly class Tool
{
    /**
     * Creates a new Tool instance.
     *
     * @param string $name The unique name of the function the model can call.
     * @param string $description A description of what the function does.
     * @param array<string, mixed> $parameters JSON Schema defining the function's parameters.
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters,
    ) {}

    /**
     * Converts the tool to an array representation for API requests.
     *
     * @return array{type: string, function: array{name: string, description: string, parameters: string}}
     */
    public function toArray(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => json_encode($this->parameters, JSON_THROW_ON_ERROR),
            ],
        ];
    }

    /**
     * Creates a Tool from a callable with reflection.
     *
     * This method extracts the function name, docblock description, and parameter
     * types from a callable using PHP reflection.
     *
     * @param callable $callable The callable to create a tool from.
     * @param string|null $name Optional override for the tool name.
     * @param string|null $description Optional override for the tool description.
     * @return self
     */
    public static function fromCallable(callable $callable, ?string $name = null, ?string $description = null): self
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callable));

        $functionName = $name ?? $reflection->getName();

        // Extract description from docblock if not provided
        $docComment = $reflection->getDocComment();
        $toolDescription = $description;
        if ($toolDescription === null && $docComment !== false) {
            // Extract first line of docblock as description
            preg_match('/\/\*\*\s*\n\s*\*\s*(.+)/', $docComment, $matches);
            $toolDescription = $matches[1] ?? 'No description available';
        }
        $toolDescription ??= 'No description available';

        // Build parameters schema from reflection
        $properties = [];
        $required = [];

        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            $property = [
                'type' => self::phpTypeToJsonSchemaType($paramType),
            ];

            // Extract parameter description from docblock
            if ($docComment !== false) {
                preg_match('/@param\s+\S+\s+\$' . preg_quote($paramName, '/') . '\s+(.+)/', $docComment, $paramMatches);
                if (isset($paramMatches[1])) {
                    $property['description'] = trim($paramMatches[1]);
                }
            }

            $properties[$paramName] = $property;

            if (!$param->isOptional()) {
                $required[] = $paramName;
            }
        }

        $parameters = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $parameters['required'] = $required;
        }

        return new self(
            name: $functionName,
            description: $toolDescription,
            parameters: $parameters,
        );
    }

    /**
     * Converts a PHP type to JSON Schema type.
     *
     * @param \ReflectionType|null $type The PHP type.
     * @return string The JSON Schema type.
     */
    private static function phpTypeToJsonSchemaType(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'string';
        }

        if ($type instanceof \ReflectionUnionType) {
            // For union types, return 'string' as a safe default
            return 'string';
        }

        if ($type instanceof \ReflectionNamedType) {
            return match ($type->getName()) {
                'int', 'integer' => 'integer',
                'float', 'double' => 'number',
                'bool', 'boolean' => 'boolean',
                'array' => 'array',
                'object', 'stdClass' => 'object',
                default => 'string',
            };
        }

        return 'string';
    }
}
