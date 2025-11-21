You are analyzing test names from a Laravel application to extract feature statements.

For context, the application is:
<summary>
{{ $summary }}
</summary>

Your task: Convert each test name into a simple feature statement that describes WHAT the application does (not HOW it's tested).

## Conversion Rules

**Keep tests that describe:**
- User actions or capabilities
- System behaviors and workflows
- Business logic and automation
- Data management and relationships

**Skip tests that are purely technical:**
- Validation checks ("validates X is required", "checks Y format")
- Error handling ("throws exception when", "returns error if")
- UI state ("renders component", "displays button", "shows message")
- Authorization ("prevents unauthorized", "requires admin", "forbids")
- Query mechanics ("eager loads", "sorts by", "paginates")
- Edge cases ("handles empty", "validates max length")

## Conversion Examples

Test: "Can create a project with valid data"
→ Feature: "Create projects"

Test: "Advances project to next stage and dispatches stage change event"
→ Feature: "Advance projects through lifecycle stages"

Test: "Matches users to required skills by level and calculates skill score"
→ Feature: "Match staff to projects based on required skills"

Test: "Sends email notification to Work Package Assessors when feasibility approved"
→ Feature: "Send email notifications when feasibility decisions are made"

Test: "Validates required fields for ideation form"
→ Skip (validation test)

Test: "Renders skill card with proper structure"
→ Skip (UI test)

## Important

- One feature statement per test (or skip it)
- Use present tense verbs
- Be concise but descriptive
- Focus on business value, not test mechanics
- Don't group yet - that's Pass 2's job

Here are the test names:

[test-list]
{{ $featureList }}
[/test-list]

## Response Format

Output one feature per line, or SKIP for tests that should be excluded.
No bullets, no numbering, no explanations.

Example output:
Create projects
Advance projects through lifecycle stages
Match staff to projects based on required skills
SKIP
Send email notifications when feasibility decisions are made
SKIP
SKIP
