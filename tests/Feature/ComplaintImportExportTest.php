<?php

namespace Tests\Feature;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintImportExportTest extends TestCase
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

    public function test_import_meta_returns_target_fields(): void
    {
        $response = $this->withSession($this->ownerSession())->getJson('/api/imports/meta');

        $response
            ->assertOk()
            ->assertJsonPath('permissions.import', true)
            ->assertJsonStructure([
                'target_fields' => [
                    ['key', 'label', 'required', 'group', 'field_type'],
                ],
                'delimiter_options',
                'max_rows',
            ]);
    }

    public function test_import_parse_suggests_column_mapping(): void
    {
        $content = "title,description,customer_name\nLate delivery,Package was late,Jane Doe";

        $response = $this->withSession($this->ownerSession())->postJson('/api/imports/parse', [
            'content' => $content,
            'has_header' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('row_count', 1)
            ->assertJsonPath('suggested_mapping.0', 'title')
            ->assertJsonPath('suggested_mapping.1', 'description')
            ->assertJsonPath('suggested_mapping.2', 'customer_name');
    }

    public function test_blank_preview_returns_editable_columns(): void
    {
        $response = $this->withSession($this->ownerSession())->postJson('/api/imports/preview', [
            'blank' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('row_count', 1)
            ->assertJsonStructure([
                'columns' => [
                    ['key', 'label', 'required', 'field_type'],
                ],
                'rows' => [
                    ['title', 'description'],
                ],
            ]);
    }

    public function test_import_store_creates_complaints_from_rows(): void
    {
        $response = $this->withSession($this->ownerSession())->postJson('/api/imports', [
            'rows' => [
                [
                    'title' => 'Broken product',
                    'description' => 'Item arrived damaged.',
                    'customer_name' => 'Jane Doe',
                    'priority' => 'high',
                    'status' => 'open',
                ],
                [
                    'title' => 'Late delivery',
                    'description' => 'Order was two weeks late.',
                ],
            ],
            'data_start_row' => 1,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('created', 2)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseCount('complaints', 2);
        $this->assertDatabaseHas('complaints', [
            'workspace_id' => 'workspace-test-1',
            'title' => 'Broken product',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_export_preview_and_download(): void
    {
        Complaint::query()->create([
            'workspace_id' => 'workspace-test-1',
            'title' => 'Export me',
            'description' => 'Should appear in export.',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => 'Test User',
        ]);

        $preview = $this->withSession($this->ownerSession())->getJson('/api/exports/preview');

        $preview
            ->assertOk()
            ->assertJsonPath('preview.count', 1)
            ->assertJsonPath('preview.samples.0.primary', 'Export me');

        $download = $this->withSession($this->ownerSession())->get('/api/exports/download');

        $download
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('Export me', $download->getContent());
    }

    public function test_export_preview_respects_status_filter(): void
    {
        Complaint::query()->create([
            'workspace_id' => 'workspace-test-1',
            'title' => 'Open complaint',
            'description' => 'Open.',
            'priority' => 'low',
            'status' => 'open',
            'created_by' => 'Test User',
        ]);

        Complaint::query()->create([
            'workspace_id' => 'workspace-test-1',
            'title' => 'Closed complaint',
            'description' => 'Closed.',
            'priority' => 'low',
            'status' => 'closed',
            'created_by' => 'Test User',
        ]);

        $response = $this->withSession($this->ownerSession())->getJson('/api/exports/preview?status=closed');

        $response
            ->assertOk()
            ->assertJsonPath('preview.count', 1)
            ->assertJsonPath('preview.samples.0.primary', 'Closed complaint');
    }
}
