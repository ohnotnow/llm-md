You are analyzing test names from a Laravel application to identify core features.

For background, the overall project description is:
<summary>
{{ $summary }}
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
{{ $featureList }}
[/test-list]

## Response format

Output ONLY the filtered feature list. Do not include any introduction, explanation, or follow-up questions.
