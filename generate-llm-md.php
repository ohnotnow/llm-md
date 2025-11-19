#!/usr/bin/env php
<?php

/**
 * Generate .llm.md for a repository
 * Usage: php generate-llm-md.php /path/to/repo
 */
if ($argc < 2) {
    echo "Usage: php generate-llm-md.php /path/to/repo\n";
    exit(1);
}

$repoPath = rtrim($argv[1], '/');

if (! is_dir($repoPath)) {
    echo "Error: Directory not found: $repoPath\n";
    exit(1);
}

// --- 1. Get tech stack from composer.json ---
function getTechStack($repoPath)
{
    $composerFile = "$repoPath/composer.json";
    if (! file_exists($composerFile)) {
        return 'No composer.json found';
    }

    $composer = json_decode(file_get_contents($composerFile), true);
    $stack = [];

    if (isset($composer['require']['laravel/framework'])) {
        $stack[] = 'Laravel '.str_replace('^', '', $composer['require']['laravel/framework']);
    }
    // check the php version in the .lando.yml if it exists
    if (file_exists("$repoPath/.lando.yml")) {
        $landoYml = file_get_contents("$repoPath/.lando.yml");
        if (preg_match('/php:\s*[\'"]?(\d+\.\d+)[\'"]?/', $landoYml, $matches)) {
            $stack[] = 'PHP '.$matches[1];
        }
    } else {
        if (isset($composer['require']['php'])) {
            $stack[] = 'PHP '.str_replace('^', '', $composer['require']['php']);
        }
    }

    // Add a few key dependencies
    $keyDeps = ['livewire/livewire', 'livewire/flux', 'livewire/flux-pro'];
    foreach ($keyDeps as $dep) {
        if (isset($composer['require'][$dep])) {
            $stack[] = basename($dep);
        }
    }

    return implode(', ', $stack);
}

// --- 2. Generate filtered directory tree ---
function generateTree($dir, $repoRoot, $prefix = '', $isLast = true, $maxDepth = 4, $currentDepth = 0, $ignorePatterns = null)
{
    if ($currentDepth >= $maxDepth) {
        return '';
    }

    // Parse .gitignore from repo root only once
    if ($ignorePatterns === null) {
        $gitignoreFile = "$repoRoot/.gitignore";
        $gitignore = file_exists($gitignoreFile) ? file_get_contents($gitignoreFile) : '';

        if (empty($gitignore)) {
            $gitignore = "vendor\nnode_modules\n.git\n.env\n.env.local\n.env.development.local\n.env.test.local\n.env.production.local";
        }

        $ignorePatterns = explode("\n", $gitignore);
        $ignorePatterns = array_filter($ignorePatterns, function ($item) {
            return ! empty(trim($item)) && ! str_starts_with(trim($item), '#');
        });
        $ignorePatterns = array_map('trim', $ignorePatterns);
        $ignorePatterns = array_filter($ignorePatterns, function ($item) {
            return ! empty($item);
        });
        $ignorePatterns = array_values($ignorePatterns);
    }

    $output = '';

    $items = array_diff(scandir($dir), ['.', '..']);
    $items = array_filter($items, function ($item) use ($dir, $repoRoot, $ignorePatterns) {
        // Skip hidden files/directories
        if (str_starts_with($item, '.')) {
            return false;
        }

        // Skip commonly verbose/boilerplate directories at root level
        if ($dir === $repoRoot) {
            $skipDirs = ['database', 'public', 'storage', 'bootstrap', 'config'];
            if (in_array($item, $skipDirs)) {
                return false;
            }
        }

        $itemPath = "$dir/$item";
        // Calculate relative path from repo root
        $relativePath = str_replace(rtrim($repoRoot, '/').'/', '', $itemPath);
        // Handle case where itemPath equals repoRoot exactly
        if ($relativePath === $itemPath) {
            $relativePath = $item;
        }

        // Check if this path matches any gitignore pattern
        foreach ($ignorePatterns as $pattern) {
            if (matchesGitignorePattern($relativePath, $pattern, $repoRoot)) {
                return false;
            }
        }

        return true;
    });
    $items = array_values($items);

    foreach ($items as $index => $item) {
        $path = "$dir/$item";
        $isLastItem = ($index === count($items) - 1);
        $connector = $isLastItem ? '└── ' : '├── ';
        $output .= $prefix.$connector.$item."\n";

        if (is_dir($path)) {
            $newPrefix = $prefix.($isLastItem ? '    ' : '│   ');
            $output .= generateTree($path, $repoRoot, $newPrefix, $isLastItem, $maxDepth, $currentDepth + 1, $ignorePatterns);
        }
    }

    return $output;
}

// Helper function to check if a path matches a gitignore pattern
function matchesGitignorePattern($relativePath, $pattern, $repoRoot): bool
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
    if (strpos($relativePath, $pattern) !== false) {
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

// --- Helper function to call OpenAI API ---
function callOpenAi(string $prompt): ?string
{
    $apiKey = getenv('OPENAI_API_KEY');

    if (empty($apiKey)) {
        echo "Warning: OPENAI_API_KEY not set, skipping LLM call\n";

        return null;
    }

    $ch = curl_init('https://api.openai.com/v1/responses');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-5.1',
            'input' => $prompt,
            'reasoning' => ['effort' => 'low'],
        ]),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "Warning: Curl error - $curlError\n";

        return null;
    }

    if ($httpCode !== 200) {
        echo "Warning: OpenAI API returned HTTP $httpCode\n";
        echo "Response: $response\n";

        return null;
    }

    $data = json_decode($response, true);

    if (! $data || ! isset($data['output'])) {
        echo "Warning: Unexpected response format from OpenAI API\n";

        return null;
    }

    // Find the message output (skip reasoning output)
    $messageOutput = null;
    foreach ($data['output'] as $output) {
        if ($output['type'] === 'message' && $output['status'] === 'completed') {
            $messageOutput = $output;
            break;
        }
    }

    if (! $messageOutput || ! isset($messageOutput['content'][0]['text'])) {
        echo "Warning: Could not find message content in OpenAI response\n";

        return null;
    }

    return $messageOutput['content'][0]['text'];
}

// --- 3. Extract test names ---
function getTestFeatures($repoPath, $useLlm = false, $summary = "")
{
    $testsDir = "$repoPath/tests/Feature";
    if (! is_dir($testsDir)) {
        return ['No feature tests found'];
    }

    $features = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());

            // Match any public function (treating all as tests)
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

            // do the same for Pest style tests (both it() and test() styles)
            preg_match_all('/(it|test)\([\'"](.*?)[\'"],\s*function\s*\(\)\s*\{/s', $content, $pestMatches);
            if (! empty($pestMatches[2])) {
                foreach ($pestMatches[2] as $testName) {
                    $feature = ucfirst($testName);
                    $features[] = $feature;
                }
            }
        }
    }

    $featureList = implode("\n", array_unique($features));

    if ($useLlm) {
        $prompt = <<<PROMPT
You are analyzing test names from a Laravel application to identify core features.

For background, the overall project description is:
<summary>
{$summary}
</summary>

Filter this list to include ONLY tests that describe:
- Primary entities and resources (e.g., "can create a project")
- User-facing workflows and business processes
- Key integrations or automation
- Important domain concepts

EXCLUDE tests that are:
- Validation rules (required fields, format checks, length limits)
- Edge cases and error handling
- Authorization/permission checks
- Empty state displays
- Form state management (resets, initialization)
- Relationship eager loading or query optimization

Keep ONE representative CRUD test per entity to show what exists in the system.
For other tests, only include those that reveal business logic or workflows.

Rewrite kept tests as concise feature statements.

Example One:
<original-list>
Displays the heatmap page with component
Provides staff sorted alphabetically by surname
Provides active projects but excludes cancelled projects
Provides 10 upcoming working days
Includes busyness data for each staff member
Renders the component successfully
Displays heatmap when Model button is clicked
Hides heatmap when Model button is clicked again
Shows assigned staff at top of heatmap when staff are assigned
Shows all staff alphabetically when no staff are assigned
Includes technical lead and change champion in assigned staff
Includes CoSE IT staff in assigned staff list
Shows both assigned_to and coseItStaff together at top of heatmap
Loads assigned staff from database correctly when reopening project
Returns correct structure in heatmapData computed property
Displays UI elements correctly when heatmap is shown
Updates button label when toggling heatmap
</original-list>

<filtered-list>
Generates a heatmap of staff activity
</filtered-list>

Example Two:
<original-list>
Hides IT assignment information when the user has no skills
Can toggle to include completed and cancelled assignments
Can sort a list of people with most applicable skill level for a given competency
Can get users matched by skills and sorted by score
Returns all staff sorted alphabetically when no required skills provided
Returns all staff with score 0 when no users have required skills
Returns all staff with matched users sorted first by skill score
Displays user skills
Displays all skills
Renders skill card with proper structure
Renders skill level radio group in correct position
Filters skills by name
Filters skills by description
Requires minimum 2 characters for skill search
Shows all skills when skill search is empty
Is case insensitive for skill search
Resets page when skill search changes
Orders skills by name
Shows only my skills when toggled to true
Shows all skills when toggled to false
Updates user skill when radio group is changed
Removes user skill when radio group is changed to none
Handles user with no skills
Handles search with special characters
Handles empty search query
Displays skills in the list
Has show create skill form flag set to false by default
Displays Skill name, description, category and user count for each skill
Filters skills by category
Filters users by forenames
Filters users by surname
Filters users by full name
</original-list>

<filtered-list>
Shows user details, roles, skills, requests, and IT assignments for admins
Staff can view and edit their skills
</filtered-list>

Example Three:
<original-list>
Can create a project with valid data
Validates required fields for project creation
Can create an ideation form with valid data
Validates required fields for ideation form
Validates deadline must be after today
Can create a feasibility form with valid data
Validates required fields for feasibility form
Can create a scoping form with valid data
Validates required fields for scoping form
Can create a scheduling form with valid data
Validates required fields for scheduling form
Validates completion date must be after start date
Can create a detailed design form with valid data
Validates required fields for detailed design form
Validates URL format for design link
Can create a development form with valid data
Validates required fields for development form
Validates URL format for repository URL
Can create a testing form with valid data
Validates required fields for testing form
Validates URL format for test repository
Can create a deployed form with valid data
Validates required fields for deployed form
Validates URL format for deployment URL
Validates maximum length for string fields
Validates maximum length for textarea fields
</original-list>

<filtered-list>
Can create a project and related sub-forms
</filtered-list>

Here's the test list:

[test-list]
{$featureList}
[/test-list]

## Response format

Output ONLY the filtered feature list. Do not include any introduction, explanation, or follow-up questions.
PROMPT;

        $filteredFeatures = callOpenAi($prompt);

        if ($filteredFeatures === null) {
            return $featureList;
        }

        return $filteredFeatures;
    }

    return $featureList;
}

function summarizeReadme($repoPath, $useLlm = false)
{
    $readmeFile = "$repoPath/README.md";
    if (! file_exists($readmeFile)) {
        return 'No README found';
    }

    $readmeContent = file_get_contents($readmeFile);

    if (str_contains($readmeContent, 'Laravel is a web application framework with expressive')) {
        return "Only Laravel stub readme found";
    }

    if ($useLlm) {
        $prompt = <<<PROMPT
You are helping create concise documentation for a code repository.

Please read the following README and provide a 2-3 sentence summary that covers:
1. What this application does (its main purpose)
2. Who the primary users are (if mentioned)
3. The key problem it solves

Keep it factual and concise. Do not include installation instructions,
technical details, or contribution guidelines.

README:
---
{$readmeContent}
---

## Response format

Output ONLY the summary. Do not include any introduction, explanation, or follow-up questions.
PROMPT;

        $summary = callOpenAi($prompt);

        if ($summary !== null) {
            return $summary;
        }
    }

    // Fallback to simple truncation
    return substr($readmeContent, 0, 500).'...';
}

// --- 5. Get key entry points ---
function getEntryPoints($repoPath)
{
    $routes = [];
    $routesFile = "$repoPath/routes/web.php";

    if (file_exists($routesFile)) {
        $routes[] = 'routes/web.php';
    }
    if (file_exists("$repoPath/routes/api.php")) {
        $routes[] = 'routes/api.php';
    }

    return $routes;
}

// --- Generate the START_HERE.md ---
$output = "# Repository Overview\n\n";
$output .= "> Auto-generated by generate-llm-md.php\n\n";

$output .= "## Purpose\n\n";
$summary = summarizeReadme($repoPath, true);
$output .= $summary."\n\n";

$output .= "## Tech Stack\n\n";
$output .= getTechStack($repoPath)."\n\n";

$output .= "## Directory Structure\n\n";
$output .= "```\n";
$output .= generateTree($repoPath, $repoPath);
$output .= "```\n\n";

$output .= "## Key Entry Points\n\n";
$entryPoints = getEntryPoints($repoPath);
foreach ($entryPoints as $ep) {
    $output .= "- `$ep`\n";
}
$output .= "\n";

$output .= "## Features (from tests)\n\n";
$features = getTestFeatures($repoPath, true, $summary);
$output .= $features."\n\n";

// Write to file
file_put_contents("$repoPath/.llm.md", $output);
echo "✓ Created {$repoPath}/.llm.md\n";
