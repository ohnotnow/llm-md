<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Provider and Model
    |--------------------------------------------------------------------------
    |
    | The LLM provider and model to use for generating documentation.
    | Format: "provider/model" (e.g., "openai/gpt-4", "anthropic/claude-3-5-sonnet-20241022")
    |
    */
    'model' => env('GENERATOR_MODEL', 'openai/gpt-5.1'),

    /*
    |--------------------------------------------------------------------------
    | Model Parameters
    |--------------------------------------------------------------------------
    |
    | Configuration for the LLM model behavior.
    |
    */
    'max_tokens' => env('GENERATOR_MAX_TOKENS', 100000),
    'temperature' => env('GENERATOR_TEMPERATURE', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Directory Tree Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for generating the directory tree structure.
    |
    */
    'tree' => [
        'max_depth' => 4,
        'skip_at_root' => ['database', 'public', 'storage', 'bootstrap', 'config'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the generated documentation file.
    |
    */
    'output_filename' => '.llm.md',
];
