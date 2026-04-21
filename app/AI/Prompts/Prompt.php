<?php

namespace App\AI\Prompts;

/**
 * Base prompt. Subclasses supply the template-specific system text and JSON
 * schema; this class handles the repeated bits: assembling the system message
 * with global rules, wrapping CONTEXT into the user message, and validating
 * model output before callers trust it.
 */
abstract class Prompt
{
    /**
     * Template-specific system text. The base class appends global rules.
     */
    abstract public function system(): string;

    /**
     * JSON schema describing the expected structured response.
     * Shape: ['name' => string, 'schema' => array] — matches OpenAI /
     * Anthropic structured-output conventions and is easily adapted by
     * the AI SDK wrapper.
     *
     * @return array{name: string, schema: array<string, mixed>}
     */
    abstract public function schema(): array;

    /**
     * Build the chat messages array for the AI SDK call.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, array{role: string, content: string}>
     */
    public function buildMessages(array $context): array
    {
        return [
            ['role' => 'system', 'content' => $this->fullSystem()],
            ['role' => 'user', 'content' => $this->formatContext($context)],
        ];
    }

    /**
     * Model hint — small/cheap by default. Chat prompts override.
     */
    public function model(): string
    {
        return 'claude-haiku-4-5';
    }

    public function maxTokens(): int
    {
        return 400;
    }

    public function temperature(): float
    {
        return 0.4;
    }

    /**
     * Validate a decoded JSON response. Returns the response on success.
     * Throws InvalidPromptResponseException if required top-level keys are
     * missing — callers should catch and either retry once or fall back
     * to a deterministic insight.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public function validate(array $response): array
    {
        $missing = array_values(array_diff($this->requiredKeys(), array_keys($response)));

        if ($missing !== []) {
            throw new InvalidPromptResponseException(
                'Prompt response missing required keys: '.implode(', ', $missing)
            );
        }

        return $response;
    }

    /**
     * Rules included in the system message. Subclasses override to add
     * chat-specific rules or any future prompt-kind-specific rules.
     *
     * @return array<int, string>
     */
    protected function rules(): array
    {
        return [PromptRules::global()];
    }

    /**
     * @return array<int, string>
     */
    protected function requiredKeys(): array
    {
        return $this->schema()['schema']['required'] ?? [];
    }

    protected function fullSystem(): string
    {
        $blocks = array_map(trim(...), [$this->system(), ...$this->rules()]);

        return implode("\n\n", array_filter($blocks));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function formatContext(array $context): string
    {
        return json_encode(
            ['CONTEXT' => $context],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /**
     * Shared sub-schema used by most insight prompts to force the model to
     * cite which CONTEXT values it used.
     *
     * @return array<string, mixed>
     */
    protected static function referencedValuesSchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'required' => ['label', 'value'],
                'properties' => [
                    'label' => ['type' => 'string'],
                    'value' => [],
                ],
            ],
        ];
    }
}
