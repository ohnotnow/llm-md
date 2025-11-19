<?php

namespace App\Services\Generator;

class DirectoryTreeGenerator
{
    /**
     * Generate a filtered directory tree.
     */
    public function generate(string $repoPath, ?int $maxDepth = null): string
    {
        $maxDepth = $maxDepth ?? config('generator.tree.max_depth');
        $gitignoreParser = new GitignoreParser($repoPath);

        return $this->generateTree(
            dir: $repoPath,
            repoRoot: $repoPath,
            gitignoreParser: $gitignoreParser,
            maxDepth: $maxDepth
        );
    }

    /**
     * Recursively generate the tree structure.
     */
    protected function generateTree(
        string $dir,
        string $repoRoot,
        GitignoreParser $gitignoreParser,
        string $prefix = '',
        bool $isLast = true,
        int $maxDepth = 4,
        int $currentDepth = 0
    ): string {
        if ($currentDepth >= $maxDepth) {
            return '';
        }

        $output = '';
        $items = $this->getFilteredItems($dir, $repoRoot, $gitignoreParser);

        foreach ($items as $index => $item) {
            $path = $dir.'/'.$item;
            $isLastItem = ($index === count($items) - 1);
            $connector = $isLastItem ? '└── ' : '├── ';
            $output .= $prefix.$connector.$item."\n";

            if (is_dir($path)) {
                $newPrefix = $prefix.($isLastItem ? '    ' : '│   ');
                $output .= $this->generateTree(
                    dir: $path,
                    repoRoot: $repoRoot,
                    gitignoreParser: $gitignoreParser,
                    prefix: $newPrefix,
                    isLast: $isLastItem,
                    maxDepth: $maxDepth,
                    currentDepth: $currentDepth + 1
                );
            }
        }

        return $output;
    }

    /**
     * Get filtered directory items.
     */
    protected function getFilteredItems(string $dir, string $repoRoot, GitignoreParser $gitignoreParser): array
    {
        $items = array_diff(scandir($dir), ['.', '..']);

        $items = array_filter($items, function ($item) use ($dir, $repoRoot, $gitignoreParser) {
            // Skip hidden files/directories
            if (str_starts_with($item, '.')) {
                return false;
            }

            // Skip commonly verbose/boilerplate directories at root level
            if ($dir === $repoRoot) {
                $skipDirs = config('generator.tree.skip_at_root');
                if (in_array($item, $skipDirs)) {
                    return false;
                }
            }

            // Calculate relative path from repo root
            $itemPath = $dir.'/'.$item;
            $relativePath = str_replace(rtrim($repoRoot, '/').'/', '', $itemPath);

            // Handle case where itemPath equals repoRoot exactly
            if ($relativePath === $itemPath) {
                $relativePath = $item;
            }

            // Check gitignore patterns
            return ! $gitignoreParser->matches($relativePath);
        });

        return array_values($items);
    }
}
