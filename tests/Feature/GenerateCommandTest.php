<?php

use Illuminate\Support\Facades\File;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;

use function Pest\Laravel\artisan;

beforeEach(function () {
    // Create a temporary test directory structure
    $this->testPath = storage_path('testing/test-project');
    File::ensureDirectoryExists($this->testPath);
    File::ensureDirectoryExists($this->testPath.'/routes');
    File::ensureDirectoryExists($this->testPath.'/tests/Feature');

    // Create composer.json
    File::put($this->testPath.'/composer.json', json_encode([
        'require' => [
            'laravel/framework' => '^12.0',
            'php' => '^8.4',
        ],
    ]));

    // Create README.md
    File::put($this->testPath.'/README.md', '# Test Project

This is a test project for testing the generator.');

    // Create routes
    File::put($this->testPath.'/routes/web.php', '<?php');
    File::put($this->testPath.'/routes/api.php', '<?php');

    // Create a test file
    File::put($this->testPath.'/tests/Feature/ExampleTest.php', '<?php

it("can create a user", function() {
    expect(true)->toBeTrue();
});

test("can update a user", function() {
    expect(true)->toBeTrue();
});
');
});

afterEach(function () {
    // Clean up
    if (File::exists($this->testPath)) {
        File::deleteDirectory($this->testPath);
    }
});

test('generates llm.md file successfully', function () {
    Prism::fake([
        TextResponseFake::make()->withText('This is a test project for testing the generator command.')->withUsage(new Usage(10, 20)),
        TextResponseFake::make()->withText('Can create a user')->withUsage(new Usage(10, 20)),
    ]);

    artisan('generate', ['path' => $this->testPath])
        ->assertSuccessful();

    expect(File::exists($this->testPath.'/.llm.md'))->toBeTrue();

    $content = File::get($this->testPath.'/.llm.md');
    expect($content)
        ->toContain('# Repository Overview')
        ->toContain('## Purpose')
        ->toContain('## Tech Stack')
        ->toContain('Laravel 12')
        ->toContain('## Directory Structure')
        ->toContain('## Key Entry Points')
        ->toContain('routes/web.php')
        ->toContain('routes/api.php')
        ->toContain('## Features (from tests)');
});

test('fails with invalid directory path', function () {
    artisan('generate', ['path' => '/nonexistent/path'])
        ->assertFailed();
});

test('fails when composer.json is missing', function () {
    $emptyPath = storage_path('testing/empty-project');
    File::ensureDirectoryExists($emptyPath);

    artisan('generate', ['path' => $emptyPath])
        ->assertFailed();

    File::deleteDirectory($emptyPath);
});

test('handles project without README', function () {
    Prism::fake([
        TextResponseFake::make()->withText('No features found')->withUsage(new Usage(10, 20)),
    ]);

    File::delete($this->testPath.'/README.md');

    artisan('generate', ['path' => $this->testPath])
        ->assertSuccessful();

    $content = File::get($this->testPath.'/.llm.md');
    expect($content)->toContain('No README found');
});

test('handles project without tests', function () {
    Prism::fake([
        TextResponseFake::make()->withText('This is a test project')->withUsage(new Usage(10, 20)),
    ]);

    File::deleteDirectory($this->testPath.'/tests');

    artisan('generate', ['path' => $this->testPath])
        ->assertSuccessful();

    $content = File::get($this->testPath.'/.llm.md');
    expect($content)->toContain('No feature tests found');
});
