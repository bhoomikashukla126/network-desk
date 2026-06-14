<?php

namespace Database\Seeders;

use App\Models\Complaint;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $workspaceId = 'local-dev-workspace';

        $samples = [
            ['title' => 'Damaged packaging', 'description' => 'Customer received a torn box with missing items.', 'priority' => 'high', 'status' => 'open', 'customer_name' => 'Alex Morgan', 'assignee' => 'Support Team'],
            ['title' => 'Late refund', 'description' => 'Refund has not been processed after 10 business days.', 'priority' => 'urgent', 'status' => 'in_progress', 'customer_name' => 'Jamie Lee', 'assignee' => 'Finance'],
            ['title' => 'Wrong item shipped', 'description' => 'Customer ordered blue variant but received red.', 'priority' => 'medium', 'status' => 'resolved', 'customer_name' => 'Taylor Brooks'],
            ['title' => 'App login issue', 'description' => 'User cannot sign in after password reset.', 'priority' => 'low', 'status' => 'closed', 'customer_name' => 'Riley Chen'],
            ['title' => 'Billing discrepancy', 'description' => 'Invoice total does not match order summary.', 'priority' => 'high', 'status' => 'open', 'customer_name' => 'Jordan Price', 'assignee' => 'Billing'],
            ['title' => 'Missing warranty card', 'description' => 'Warranty documentation was not included in shipment.', 'priority' => 'low', 'status' => 'open', 'customer_name' => 'Casey Wright'],
            ['title' => 'Support callback missed', 'description' => 'Scheduled callback was missed twice.', 'priority' => 'medium', 'status' => 'in_progress', 'customer_name' => 'Morgan Diaz', 'assignee' => 'Support Team'],
            ['title' => 'Duplicate charge', 'description' => 'Customer was charged twice for the same order.', 'priority' => 'urgent', 'status' => 'open', 'customer_name' => 'Sam Patel', 'assignee' => 'Finance'],
            ['title' => 'Product quality concern', 'description' => 'Multiple units reported defective buttons.', 'priority' => 'high', 'status' => 'resolved', 'customer_name' => 'Drew Allen'],
            ['title' => 'Delivery to wrong address', 'description' => 'Courier delivered package to previous tenant.', 'priority' => 'medium', 'status' => 'closed', 'customer_name' => 'Quinn Foster'],
            ['title' => 'Account merge request', 'description' => 'Customer has duplicate accounts after re-registration.', 'priority' => 'low', 'status' => 'in_progress', 'customer_name' => 'Harper Bell'],
            ['title' => 'Subscription cancellation failed', 'description' => 'Cancel button returns an error in customer portal.', 'priority' => 'high', 'status' => 'open', 'customer_name' => 'Avery Stone', 'assignee' => 'Product'],
        ];

        foreach ($samples as $sample) {
            Complaint::query()->create([
                ...$sample,
                'workspace_id' => $workspaceId,
                'created_by' => 'Local Dev User',
            ]);
        }
    }
}
