<?php

namespace App\Services\Generator;

use Illuminate\Support\Facades\File;

class TechStackDetector
{
    /**
     * Detect the tech stack from composer.json and .lando.yml.
     */
    public function detect(string $repoPath): string
    {
        $composerFile = $repoPath.'/composer.json';
        if (! File::exists($composerFile)) {
            return 'No composer.json found';
        }

        $composer = json_decode(File::get($composerFile), true);
        $stack = [];

        // Detect Laravel
        if (isset($composer['require']['laravel/framework'])) {
            $version = str_replace('^', '', $composer['require']['laravel/framework']);
            // Extract major version only (e.g., "12.0" becomes "12")
            $majorVersion = explode('.', $version)[0];
            $stack[] = 'Laravel '.$majorVersion;
        }

        // Check PHP version from .lando.yml if it exists
        $landoFile = $repoPath.'/.lando.yml';
        if (File::exists($landoFile)) {
            $landoYml = File::get($landoFile);
            if (preg_match('/php:\s*[\'"]?(\d+\.\d+)[\'"]?/', $landoYml, $matches)) {
                $stack[] = 'PHP '.$matches[1];
            }
        } else {
            // Fall back to composer.json PHP requirement
            if (isset($composer['require']['php'])) {
                $stack[] = 'PHP '.str_replace('^', '', $composer['require']['php']);
            }
        }

        // Add key dependencies
        $keyDeps = ['livewire/livewire', 'livewire/flux', 'livewire/flux-pro'];
        foreach ($keyDeps as $dep) {
            if (isset($composer['require'][$dep])) {
                $stack[] = basename($dep);
            }
        }

        return implode(', ', $stack);
    }
}
