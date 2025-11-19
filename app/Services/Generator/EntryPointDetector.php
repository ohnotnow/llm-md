<?php

namespace App\Services\Generator;

use Illuminate\Support\Facades\File;

class EntryPointDetector
{
    /**
     * Detect key entry points (route files).
     */
    public function detect(string $repoPath): array
    {
        $routes = [];

        if (File::exists($repoPath.'/routes/web.php')) {
            $routes[] = 'routes/web.php';
        }

        if (File::exists($repoPath.'/routes/api.php')) {
            $routes[] = 'routes/api.php';
        }

        return $routes;
    }
}
