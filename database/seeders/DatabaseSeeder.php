<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $customers = [
            ['name' => 'Acme Corp', 'email' => 'contact@acmecorp.com', 'company' => 'Acme Corp', 'notes' => 'Long-standing client. Prefers weekly check-ins on Thursdays.'],
            ['name' => 'Nova Labs', 'email' => 'hello@novalabs.io', 'company' => 'Nova Labs', 'notes' => 'Fast-moving startup. High priority. CEO communicates directly.'],
            ['name' => 'Bright Media', 'email' => 'projects@brightmedia.co', 'company' => 'Bright Media', 'notes' => 'Content-heavy platform. Focused on performance and SEO.'],
        ];

        foreach ($customers as $data) {
            Customer::create($data);
        }

        $acme = Customer::where('company', 'Acme Corp')->first();
        $nova = Customer::where('company', 'Nova Labs')->first();
        $bright = Customer::where('company', 'Bright Media')->first();

        $projects = [
            [
                'customer_id' => $acme->id,
                'name' => 'Acme Customer Portal',
                'description' => 'Self-service portal for Acme clients to manage their orders and invoices.',
                'github_repo' => 'pishift/acme-portal',
                'stack' => 'Laravel + Vue',
                'status' => 'active',
            ],
            [
                'customer_id' => $acme->id,
                'name' => 'Acme Internal Tools',
                'description' => 'Internal dashboard for Acme ops team — inventory tracking and reporting.',
                'github_repo' => 'pishift/acme-internal',
                'stack' => 'Laravel + Filament',
                'status' => 'paused',
            ],
            [
                'customer_id' => $nova->id,
                'name' => 'Nova Platform v2',
                'description' => "Full rebuild of Nova's core product. API-first with a Next.js frontend.",
                'github_repo' => 'pishift/nova-platform',
                'stack' => 'Next.js + Supabase',
                'status' => 'active',
            ],
            [
                'customer_id' => $bright->id,
                'name' => 'Bright CMS Migration',
                'description' => 'Migrating Bright Media from WordPress to a headless CMS setup.',
                'github_repo' => 'pishift/bright-cms',
                'stack' => 'Astro + Sanity',
                'status' => 'active',
            ],
            [
                'customer_id' => $bright->id,
                'name' => 'Bright Analytics Dashboard',
                'description' => 'Custom analytics dashboard pulling from GA4 and internal event data.',
                'github_repo' => 'pishift/bright-analytics',
                'stack' => 'Laravel + Livewire',
                'status' => 'complete',
            ],
        ];

        foreach ($projects as $data) {
            Project::create($data);
        }

        $acmePortal = Project::where('name', 'Acme Customer Portal')->first();
        $acmeInternal = Project::where('name', 'Acme Internal Tools')->first();
        $novaPlatform = Project::where('name', 'Nova Platform v2')->first();
        $brightCms = Project::where('name', 'Bright CMS Migration')->first();
        $brightAnalytics = Project::where('name', 'Bright Analytics Dashboard')->first();

        $tasks = [
            // Acme Portal
            ['project_id' => $acmePortal->id, 'title' => 'Fix login redirect on mobile Safari', 'description' => 'After logging in on Safari iOS, users are redirected to / instead of /dashboard.', 'type' => 'bug', 'priority' => 'high', 'status' => 'in-progress'],
            ['project_id' => $acmePortal->id, 'title' => 'Add CSV export to invoices page', 'description' => 'Clients need to export their invoice history as CSV for accounting.', 'type' => 'feature', 'priority' => 'medium', 'status' => 'backlog'],
            ['project_id' => $acmePortal->id, 'title' => 'Update order status labels to match new terminology', 'description' => 'Rename "Processing" to "In Review" and "Shipped" to "Dispatched" across the portal.', 'type' => 'change', 'priority' => 'low', 'status' => 'backlog'],

            // Acme Internal
            ['project_id' => $acmeInternal->id, 'title' => 'Inventory count discrepancy on bulk import', 'description' => 'When importing more than 500 rows, some quantities are off by 1. Likely a rounding issue.', 'type' => 'bug', 'priority' => 'high', 'status' => 'backlog'],
            ['project_id' => $acmeInternal->id, 'title' => 'Add role-based access for warehouse staff', 'description' => 'Warehouse staff should only see inventory screens, not financial reports.', 'type' => 'feature', 'priority' => 'medium', 'status' => 'done'],

            // Nova Platform
            ['project_id' => $novaPlatform->id, 'title' => 'Implement OAuth2 with Google and GitHub', 'description' => 'Users should be able to sign up and log in via Google and GitHub SSO.', 'type' => 'feature', 'priority' => 'high', 'status' => 'in-progress'],
            ['project_id' => $novaPlatform->id, 'title' => 'API rate limiting returns 500 instead of 429', 'description' => 'When the rate limit is exceeded, the API throws a 500 error instead of a proper 429 response.', 'type' => 'bug', 'priority' => 'high', 'status' => 'done'],
            ['project_id' => $novaPlatform->id, 'title' => 'Add pagination to /api/v2/posts endpoint', 'description' => 'Endpoint currently returns all records. Need cursor-based pagination.', 'type' => 'feature', 'priority' => 'medium', 'status' => 'backlog'],
            ['project_id' => $novaPlatform->id, 'title' => 'Switch date format in API responses to ISO 8601', 'description' => 'All timestamps should be returned as ISO 8601 strings for consistency.', 'type' => 'change', 'priority' => 'low', 'status' => 'done'],

            // Bright CMS
            ['project_id' => $brightCms->id, 'title' => 'Migrate 3,000 blog posts from WordPress to Sanity', 'description' => 'Write a migration script to pull posts from WP REST API and push to Sanity via its SDK.', 'type' => 'feature', 'priority' => 'high', 'status' => 'in-progress'],
            ['project_id' => $brightCms->id, 'title' => 'Image paths broken after CMS import', 'description' => 'Images migrated from WordPress still reference the old CDN URLs. Need to rewrite to new Cloudflare paths.', 'type' => 'bug', 'priority' => 'high', 'status' => 'backlog'],
            ['project_id' => $brightCms->id, 'title' => 'Set up preview mode for draft content', 'description' => 'Editors need to preview unpublished content before going live.', 'type' => 'feature', 'priority' => 'medium', 'status' => 'backlog'],

            // Bright Analytics
            ['project_id' => $brightAnalytics->id, 'title' => 'Connect GA4 data source via API', 'description' => 'Pull page view and event data from GA4 Data API into the Laravel backend.', 'type' => 'feature', 'priority' => 'high', 'status' => 'done'],
            ['project_id' => $brightAnalytics->id, 'title' => 'Dashboard load time over 4s on large date ranges', 'description' => 'Querying 90+ day ranges causes slow page loads. Add DB-level aggregation and caching.', 'type' => 'bug', 'priority' => 'medium', 'status' => 'done'],
        ];

        foreach ($tasks as $data) {
            Task::create($data);
        }
    }
}
