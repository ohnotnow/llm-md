<?php

namespace App\Services\Generator;

use Illuminate\Support\Facades\File;

class DirectoryTreeGenerator
{
    /**
     * Directories under app/ to expand and show class names
     */
    private array $expandDirectories = [
        'Enums',
        'Models',
        'Services',
        'Traits',
        'Events',
        'Jobs',
        'Actions',
        'Data',
    ];

    public function generate(string $repoPath, ?int $maxDepth = null): string
    {
        $output = "```\n";
        $output .= $this->generateAppStructure($repoPath);
        $output .= $this->generateOtherDirectories($repoPath);

        return $output;
    }

    private function generateAppStructure(string $repoPath): string
    {
        $appPath = $repoPath . '/app';

        if (!File::isDirectory($appPath)) {
            return '';
        }

        $output = "app/\n";

        $directories = collect(File::directories($appPath))
            ->map(fn ($dir) => basename($dir))
            ->sort()
            ->values();

        foreach ($directories as $index => $dirName) {
            $isLast = $index === $directories->count() - 1;
            $prefix = $isLast ? '└──' : '├──';

            $output .= "  {$prefix} {$dirName}/\n";

            // Expand certain directories to show class names
            if ($this->shouldExpand($dirName)) {
                $classes = $this->getClassNames($appPath.'/'.$dirName);

                if (! empty($classes)) {
                    $indent = $isLast ? '      ' : '│     ';
                    $classLine = $this->formatClassList($classes, 70);

                    // Handle multi-line class lists
                    $lines = explode("\n", $classLine);
                    foreach ($lines as $line) {
                        $output .= "  {$indent}{$line}\n";
                    }
                }
            }
        }

        return $output;
    }

    private function generateOtherDirectories(string $repoPath): string
    {
        $output = '';

        $topLevelDirs = ['config', 'resources', 'tests', 'docker', 'routes'];

        foreach ($topLevelDirs as $dir) {
            $fullPath = $repoPath.'/'.$dir;

            if (File::isDirectory($fullPath)) {
                $output .= "{$dir}/\n";

                // Show subdirectories for specific cases
                if (in_array($dir, ['resources', 'tests'])) {
                    $subdirs = collect(File::directories($fullPath))
                        ->map(fn ($d) => basename($d))
                        ->sort()
                        ->values();

                    foreach ($subdirs as $index => $subdir) {
                        $isLast = $index === $subdirs->count() - 1;
                        $prefix = $isLast ? '└──' : '├──';
                        $output .= "  {$prefix} {$subdir}/\n";
                    }
                }
            }
        }

        return $output;
    }

    private function shouldExpand(string $dirName): bool
    {
        return in_array($dirName, $this->expandDirectories);
    }

    private function getClassNames(string $directory): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        // Get only files directly in this directory (not recursive)
        $files = File::files($directory);
        $classNames = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $classNames[] = $file->getFilenameWithoutExtension();
            }
        }

        sort($classNames);

        return $classNames;
    }

    private function formatClassList(array $classes, int $maxWidth = 70): string
    {
        if (empty($classes)) {
            return '';
        }

        $lines = [];
        $currentLine = '';

        foreach ($classes as $class) {
            if ($currentLine === '') {
                $currentLine = $class;
            } elseif (strlen($currentLine.', '.$class) <= $maxWidth) {
                $currentLine .= ', '.$class;
            } else {
                $lines[] = $currentLine.',';
                $currentLine = $class;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return implode("\n", $lines);
    }
}
