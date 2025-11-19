<?php

namespace App\Services\Generator;

use Illuminate\Support\Facades\File;

class GitignoreParser
{
    protected array $patterns = [];

    /**
     * Create a new gitignore parser instance.
     */
    public function __construct(string $repoPath)
    {
        $this->loadGitignore($repoPath);
    }

    /**
     * Load and parse the .gitignore file.
     */
    protected function loadGitignore(string $repoPath): void
    {
        $gitignorePath = $repoPath.'/.gitignore';

        if (File::exists($gitignorePath)) {
            $content = File::get($gitignorePath);
        } else {
            // Default patterns if no .gitignore exists
            $content = "vendor\nnode_modules\n.git\n.env\n.env.local\n.env.development.local\n.env.test.local\n.env.production.local";
        }

        $this->patterns = collect(explode("\n", $content))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => ! empty($line) && ! str_starts_with($line, '#'))
            ->values()
            ->toArray();
    }

    /**
     * Check if a relative path matches any gitignore pattern.
     */
    public function matches(string $relativePath): bool
    {
        foreach ($this->patterns as $pattern) {
            if ($this->matchesPattern($relativePath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a path matches a specific gitignore pattern.
     */
    protected function matchesPattern(string $relativePath, string $pattern): bool
    {
        // Check if pattern starts with / (it means "root only")
        $isRootOnly = str_starts_with($pattern, '/');
        if ($isRootOnly) {
            $pattern = substr($pattern, 1);
            // Check if it matches at root level
            if (str_starts_with($relativePath, $pattern.'/') || $relativePath === $pattern) {
                return true;
            }
            // Also check if it's a directory name at root
            $pathParts = explode('/', $relativePath);
            if (isset($pathParts[0]) && $pathParts[0] === $pattern) {
                return true;
            }

            return false;
        }

        // Simple exact match (for directories like "vendor", "node_modules")
        if ($pattern === basename($relativePath) || $pattern === $relativePath) {
            return true;
        }

        // Check if pattern matches any part of the path
        if (str_contains($relativePath, $pattern)) {
            // Check if it's a directory name (not just part of a filename)
            $pathParts = explode('/', $relativePath);
            foreach ($pathParts as $part) {
                if ($part === $pattern) {
                    return true;
                }
            }
        }

        return false;
    }
}
