<?php

namespace App\Services\Generator;

use App\Services\LlmService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ReadmeSummarizer
{
    /**
     * Create a new readme summarizer instance.
     */
    public function __construct(
        protected LlmService $llmService
    ) {}

    /**
     * Summarize the README file.
     */
    public function summarize(string $repoPath): string
    {
        $readmeFile = $repoPath.'/README.md';

        if (! File::exists($readmeFile)) {
            return 'No README found';
        }

        $readmeContent = File::get($readmeFile);

        // Check if it's just the Laravel stub readme
        if (str_contains($readmeContent, 'Laravel is a web application framework with expressive')) {
            return 'Only Laravel stub readme found';
        }

        // Generate prompt from Blade template
        $prompt = View::make('prompts.summarize-readme', [
            'readmeContent' => $readmeContent,
        ])->render();

        // Call LLM service
        return $this->llmService->generate($prompt);
    }
}
