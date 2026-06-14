<?php

return [
    'title' => 'Complaint Desk Documentation',
    'subtitle' => 'Complete guide to tracking, assigning, importing, and resolving customer complaints.',
    'last_updated' => 'June 2026',

    'sections' => [
        [
            'id' => 'overview',
            'title' => 'Overview',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Complaint Desk is a workspace extension for customer support teams. Log issues with priority and status, assign ownership, bulk import from spreadsheets, and export reports — all scoped to your platform workspace.'],
                ['type' => 'list', 'title' => 'Main areas', 'items' => [
                    'Complaints — list, filter, create, and edit records',
                    'Import — bulk load complaints from Excel or CSV',
                    'Export — download filtered complaint data as CSV',
                    'Roles & members — permissions and team access (admins only)',
                ]],
            ],
        ],
        [
            'id' => 'getting-started',
            'title' => 'Getting started',
            'blocks' => [
                ['type' => 'steps', 'items' => [
                    ['title' => 'Open Complaint Desk', 'text' => 'Visit your Complaint Desk URL. The welcome page shows an overview of features.'],
                    ['title' => 'Sign in', 'text' => 'Click Sign in and authenticate with your platform account.'],
                    ['title' => 'Check your role', 'text' => 'New members receive the Guest role (view only). Workspace owners get full access. Admins can assign roles under Roles & members.'],
                    ['title' => 'Start logging complaints', 'text' => 'Open Complaints from the navigation bar. Use New complaint or Import for bulk entry.'],
                ]],
            ],
        ],
        [
            'id' => 'complaints',
            'title' => 'Complaints dashboard',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'The Complaints page is your home screen after sign-in. Requires complaints.view permission.'],
                ['type' => 'list', 'title' => 'Status summary', 'items' => [
                    'Summary cards for Open, In progress, Resolved, and Closed',
                    'Filter pills to show one status or all complaints',
                    'Search by title, customer name, email, or assignee',
                ]],
                ['type' => 'list', 'title' => 'List views', 'items' => [
                    'Table layout on desktop; card layout on mobile',
                    'Pagination for large complaint volumes',
                    'Toolbar: Import, Export, and New complaint (permission-gated)',
                ]],
            ],
        ],
        [
            'id' => 'create-edit',
            'title' => 'Create & edit complaints',
            'blocks' => [
                ['type' => 'list', 'title' => 'Complaint fields', 'items' => [
                    'Title and description (required)',
                    'Customer name and email',
                    'Priority: low, medium, high, urgent',
                    'Status: open, in progress, resolved, closed',
                    'Assignee — team member responsible for resolution',
                ]],
                ['type' => 'paragraph', 'text' => 'Create requires complaints.create. Edit and delete require complaints.edit and complaints.delete respectively. Click a complaint title to open the edit form when permitted.'],
            ],
        ],
        [
            'id' => 'import',
            'title' => 'Bulk import',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Import many complaints at once from spreadsheet data. Requires complaints.create.'],
                ['type' => 'steps', 'items' => [
                    ['title' => 'Paste', 'text' => 'Paste from Excel, Google Sheets, or upload CSV. Choose delimiter if needed.'],
                    ['title' => 'Map columns', 'text' => 'Match columns to title, description, customer fields, priority, status, and assignee.'],
                    ['title' => 'Preview', 'text' => 'Review rows and fix errors inline before importing.'],
                    ['title' => 'Import', 'text' => 'Confirm to create all valid records. Maximum 500 rows per import.'],
                ]],
                ['type' => 'callout', 'variant' => 'info', 'title' => 'Template', 'text' => 'Download the import template from the Import page to see required column formats.'],
            ],
        ],
        [
            'id' => 'export',
            'title' => 'Export complaints',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Export filtered complaints to CSV for reporting or backup. Requires complaints.view.'],
                ['type' => 'list', 'items' => [
                    'Filter by status and search text',
                    'Live preview of how many rows will export',
                    'CSV includes id, title, description, customer fields, priority, status, assignee, and timestamps',
                    'Maximum 5,000 rows per export',
                ]],
            ],
        ],
        [
            'id' => 'access',
            'title' => 'Roles & members',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Manage who can view, create, edit, or delete complaints. Visible with roles.manage or members.manage.'],
                ['type' => 'list', 'items' => [
                    'System roles: Owner (all permissions), Guest (view only)',
                    'Create custom roles with a permission checklist',
                    'Assign roles to members when they sign in',
                    'New users default to Guest until an admin updates their role',
                ]],
            ],
        ],
        [
            'id' => 'profile',
            'title' => 'Profile & activity',
            'blocks' => [
                ['type' => 'list', 'title' => 'Profile menu', 'items' => [
                    'Workspace name and your role',
                    'Usage — API requests and storage; Refresh usage to sync quotas',
                    'Workspace activity — all actions (requires activity.view)',
                    'My activity — your own actions only',
                    'Workspaces — switch workspace; Workspace home — central platform',
                    'Logout',
                ]],
                ['type' => 'paragraph', 'text' => 'Activity log tracks complaint, role, and member changes with user, action, and timestamp filters.'],
            ],
        ],
        [
            'id' => 'languages-themes',
            'title' => 'Languages & themes',
            'blocks' => [
                ['type' => 'list', 'title' => 'Languages', 'items' => ['English and Hindi UI', 'Language follows workspace settings']],
                ['type' => 'list', 'title' => 'Themes', 'items' => ['Workspace brand colors from central settings', 'Light, dark, or system appearance']],
            ],
        ],
        [
            'id' => 'permissions',
            'title' => 'Permissions reference',
            'blocks' => [
                ['type' => 'table', 'headers' => ['Permission', 'Description'], 'rows' => [
                    ['complaints.view', 'View complaints list and export'],
                    ['complaints.create', 'Create complaints and import'],
                    ['complaints.edit', 'Edit existing complaints'],
                    ['complaints.delete', 'Delete complaints'],
                    ['activity.view', 'Workspace-wide activity log'],
                    ['roles.manage', 'Create and edit roles'],
                    ['members.manage', 'Assign roles to members'],
                ]],
                ['type' => 'callout', 'variant' => 'tip', 'title' => 'Suggested roles', 'text' => 'Owner — all permissions. Agent — view, create, edit. Viewer — view only. Support lead — add delete and activity.view.'],
            ],
        ],
        [
            'id' => 'troubleshooting',
            'title' => 'Troubleshooting',
            'blocks' => [
                ['type' => 'faq', 'items' => [
                    ['question' => 'I see access denied on Complaints', 'answer' => 'Your role needs complaints.view. Ask an admin to update your role under Roles & members.'],
                    ['question' => 'Import or Export buttons are missing', 'answer' => 'Import needs complaints.create. Export needs complaints.view. Check your assigned permissions.'],
                    ['question' => 'Import failed or skipped rows', 'answer' => 'Title is required. Check column mapping, delimiter, and the 500-row limit.'],
                    ['question' => 'Roles & members nav is missing', 'answer' => 'You need roles.manage or members.manage permission.'],
                ]],
            ],
        ],
    ],
];
