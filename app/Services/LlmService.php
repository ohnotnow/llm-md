<?php

namespace App\Services;

use InvalidArgumentException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Usage;

class LlmService
{
    protected string $provider;

    protected string $model;

    protected int $maxTokens;

    protected float $temperature;

    /**
     * Create a new LLM service instance.
     */
    public function __construct(?string $providerModel = null)
    {
        $providerModel = $providerModel ?? config('generator.model');

        if (! str_contains($providerModel, '/')) {
            throw new InvalidArgumentException(
                'Model must be in format "provider/model" (e.g., "openai/gpt-5.1")'
            );
        }

        [$this->provider, $this->model] = explode('/', $providerModel, 2);

        $this->maxTokens = config('generator.max_tokens');
        $this->temperature = config('generator.temperature');
    }

    /**
     * Generate text from the LLM.
     */
    public function generate(string $prompt): string
    {
        $response = Prism::text()
            ->using($this->provider, $this->model)
            ->withPrompt($prompt)
            ->withMaxTokens($this->maxTokens)
            ->asText();

        return $response->text;
    }

    /**
     * Get the usage statistics from the last response.
     */
    public function getUsage(): ?Usage
    {
        return Prism::text()
            ->using($this->provider, $this->model)
            ->withPrompt('')
            ->asText()
            ->usage;
    }
}
