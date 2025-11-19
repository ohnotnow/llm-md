<?php

namespace App\Console\Commands;

use App\Services\Generator\DirectoryTreeGenerator;
use App\Services\Generator\EntryPointDetector;
use App\Services\Generator\MarkdownGenerator;
use App\Services\Generator\ReadmeSummarizer;
use App\Services\Generator\TechStackDetector;
use App\Services\Generator\TestFeatureExtractor;
use App\Services\LlmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class Generate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate {path : The path to the project repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an .llm.md file for a project';

    /**
     * Execute the console command.
     */
    public function handle(
        LlmService $llmService,
        TechStackDetector $techStackDetector,
        DirectoryTreeGenerator $directoryTreeGenerator,
        EntryPointDetector $entryPointDetector,
        ReadmeSummarizer $readmeSummarizer,
        TestFeatureExtractor $testFeatureExtractor,
        MarkdownGenerator $markdownGenerator
    ): int {
        $path = $this->argument('path');

        // Validate path
        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $repoPath = rtrim($path, '/');

        // Validate composer.json exists
        if (! File::exists($repoPath.'/composer.json')) {
            $this->error('No composer.json found in the specified directory.');

            return self::FAILURE;
        }

        info('Generating .llm.md for: '.$repoPath);

        // Gather all the data
        $summary = spin(
            fn () => $readmeSummarizer->summarize($repoPath),
            'Summarizing README...'
        );

        $techStack = spin(
            fn () => $techStackDetector->detect($repoPath),
            'Detecting tech stack...'
        );

        $directoryTree = spin(
            fn () => $directoryTreeGenerator->generate($repoPath),
            'Generating directory tree...'
        );

        $entryPoints = spin(
            fn () => $entryPointDetector->detect($repoPath),
            'Detecting entry points...'
        );

        $features = spin(
            fn () => $testFeatureExtractor->extract($repoPath, $summary),
            'Extracting and filtering test features...'
        );

        // Generate the markdown
        $markdown = $markdownGenerator->generate(
            summary: $summary,
            techStack: $techStack,
            directoryTree: $directoryTree,
            entryPoints: $entryPoints,
            features: $features
        );

        // Write to file
        $outputPath = $repoPath.'/'.config('generator.output_filename');
        File::put($outputPath, $markdown);

        $this->components->success("Created {$outputPath}");

        return self::SUCCESS;
    }
}
