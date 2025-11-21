You are synthesizing a feature list for a Laravel application.

For context, the application is:
<summary>
{{ $summary }}
</summary>

You received this raw feature list from Pass 1:
<raw-features>
{{ $extractedFeatures }}
</raw-features>

Your task: Merge related features into 8-12 high-level capabilities that answer "What can users accomplish with this application?"

## Synthesis Guidelines

**Think hierarchically:**
- Look for themes (Project Management, Staff Management, Notifications, etc.)
- Group granular features under broader capabilities
- Merge similar/overlapping features

**Examples of good grouping:**

Raw features:
- Create projects
- Edit project details
- Delete projects
- Advance projects through stages
- Cancel projects
- View project history
↓ Synthesized:
"Manage project lifecycle from creation through completion"

Raw features:
- Match staff to projects based on skills
- Calculate skill match scores
- Display staff workload
- Show staff availability
- Sort staff by skill relevance
↓ Synthesized:
"Match staff to projects using skills-based scoring and workload analysis"

Raw features:
- Send notifications when projects created
- Send notifications when stage changes
- Send notifications when feasibility approved
- Send notifications to role-based recipients
↓ Synthesized:
"Send role-based email notifications for project events and approvals"

**Quality checks:**
- Each feature should describe a distinct capability
- Features should be understandable to non-technical stakeholders
- Avoid duplicate concepts (if two features are 90% the same, merge them)
- Prioritize features that show business value

## Response Format

Output exactly 8-12 features as a bullet list.
Each feature should:
- Start with an action verb (Manage, Track, Generate, Send, Match, Display...)
- Be one line
- Describe user-facing value

Example output:
• Manage project lifecycle from ideation through deployment
• Match staff to projects using skills-based scoring and availability
• Generate workload heatmaps for capacity planning
• Send role-based email notifications for project milestones
• Track and audit project history and stage transitions
• Manage user roles, skills, and permissions
• Approve or reject feasibility studies with governance workflow
• Submit projects to Digital Change Governance Group (DCGG)

Output ONLY the bullet list. No introduction, summary, or commentary.
