<?php

namespace Tests\Feature;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    private function ownerSession(array $workspace = []): array
    {
        return [
            'central_user' => [
                'sub' => 'test-user-1',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            'central_workspace' => array_merge([
                'id' => 'workspace-test-1',
                'name' => 'Test Workspace',
                'slug' => 'test-workspace',
                'is_owner' => true,
                'role' => 'owner',
                'permission' => 'edit',
            ], $workspace),
        ];
    }

    public function test_session_endpoint_returns_authenticated_context(): void
    {
        $response = $this->withSession($this->ownerSession())->getJson('/api/session');

        $response
            ->assertOk()
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonPath('workspace.slug', 'test-workspace')
            ->assertJsonPath('role.slug', 'owner')
            ->assertJsonPath('can_manage_roles', true);
    }

    public function test_complaints_index_is_paginated(): void
    {
        Complaint::query()->create([
            'workspace_id' => 'workspace-test-1',
            'title' => 'Broken product',
            'description' => 'Customer received a damaged item.',
            'priority' => 'high',
            'status' => 'open',
            'created_by' => 'Test User',
        ]);

        $response = $this->withSession($this->ownerSession())->getJson('/api/complaints?per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Broken product')
            ->assertJsonPath('permissions.create', true);
    }

    public function test_complaint_can_be_created_via_api(): void
    {
        $response = $this->withSession($this->ownerSession())->postJson('/api/complaints', [
            'title' => 'Late delivery',
            'description' => 'Order arrived two weeks late.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Late delivery');

        $this->assertDatabaseHas('complaints', [
            'workspace_id' => 'workspace-test-1',
            'title' => 'Late delivery',
        ]);
    }

    public function test_guest_role_is_assigned_to_new_non_owner_users(): void
    {
        $response = $this->withSession([
            'central_user' => [
                'sub' => 'guest-user-1',
                'name' => 'Guest User',
                'email' => 'guest@example.com',
            ],
            'central_workspace' => [
                'id' => 'workspace-test-1',
                'name' => 'Test Workspace',
                'slug' => 'test-workspace',
                'is_owner' => false,
                'role' => 'view',
                'permission' => 'view',
            ],
        ])->getJson('/api/session');

        $response
            ->assertOk()
            ->assertJsonPath('role.slug', 'guest')
            ->assertJsonPath('can_manage_roles', false);
    }

    public function test_spa_shell_loads(): void
    {
        $response = $this->withSession($this->ownerSession())->get('/');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }
}
