<?php

namespace App\Services\Generator;

use App\Services\LlmService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TestFeatureExtractor
{
    /**
     * Create a new test feature extractor instance.
     */
    public function __construct(
        protected LlmService $llmService
    ) {}

    /**
     * Extract and filter test features.
     */
    public function extract(string $repoPath, string $summary): string
    {
        $testsDir = $repoPath.'/tests/Feature';

        if (! is_dir($testsDir)) {
            return 'No feature tests found';
        }

        $features = $this->extractTestNames($testsDir);

        if (empty($features)) {
            return 'No feature tests found';
        }

        $featureList = implode("\n", array_unique($features));

        // Generate prompt from Blade template
        $prompt = View::make('prompts.extract-features-pass1', [
            'summary' => $summary,
            'featureList' => $featureList,
        ])->render();

        // Filter via LLM
        $extractedFeatures = $this->llmService->generate($prompt);

        $prompt = View::make('prompts.synthesize-features-pass2', [
            'summary' => $summary,
            'extractedFeatures' => $extractedFeatures,
        ])->render();

        $synthesizedFeatures = $this->llmService->generate($prompt);

        return $synthesizedFeatures;
    }

    /**
     * Extract test names from test files.
     */
    protected function extractTestNames(string $testsDir): array
    {
        $features = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            // Extract PHPUnit-style test methods
            preg_match_all('/public function ([a-zA-Z0-9_]+)/', $content, $matches);
            foreach ($matches[1] as $testName) {
                // Skip setUp/tearDown and other common PHPUnit lifecycle methods
                if (in_array($testName, ['setUp', 'tearDown', 'setUpBeforeClass', 'tearDownAfterClass'])) {
                    continue;
                }
                // Convert snake_case to sentence case
                $feature = str_replace('_', ' ', $testName);
                $feature = ucfirst($feature);
                $features[] = $feature;
            }

            // Extract Pest-style tests (both it() and test() styles)
            preg_match_all('/(it|test)\([\'\"](.*?)[\'"],\s*function\s*\(\)\s*\{/s', $content, $pestMatches);
            if (! empty($pestMatches[2])) {
                foreach ($pestMatches[2] as $testName) {
                    $feature = ucfirst($testName);
                    $features[] = $feature;
                }
            }
        }

        return $features;
    }
}
