# LLM Markdown Generator

Generates `.llm.md` files for Laravel projects to give LLMs context about your codebase.

## Installation

```bash
git clone git@github.com:ohnotnow/llm-md.git
cd generate-llm-md-laravel
composer install
cp .env.example .env
```

Add your LLM credentials to `.env`:

```bash
GENERATOR_MODEL=openai/gpt-5.1
OPENAI_API_KEY=your_key_here
// or other *_API_KEY's
```

Format: `provider/model`. Examples: `anthropic/claude-sonnet-4-5`, `openrouter/google/gemini-3-pro-preview`

## Usage

```bash
php artisan generate /path/to/your/project
```

Outputs `.llm.md` in the target project containing tech stack, README summary, directory structure, routes, and features from tests.

## Configuration

Edit `config/generator.php`:
- `model` - Default LLM to use
- `max_tokens` - Default 100k
- `tree.max_depth` - Directory scan depth (default 4)
- `tree.skip_at_root` - Directories to exclude

## License

MIT

## Example (for this repo)
[_Note: not the best example as it's such a small project_](.llm.md)

