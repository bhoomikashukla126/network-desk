<?php

namespace Tests\Feature;

use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dev.local_auth' => true,
            'central.client_id' => null,
        ]);
    }

    public function test_keyboard_shortcuts_can_be_saved_per_app(): void
    {
        $this->putJson('/api/user/preferences', [
            'preferences' => [
                'keyboard_shortcuts' => [
                    'network-desk' => [
                        'toggleChrome' => 'alt+shift+x',
                    ],
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('preferences.keyboard_shortcuts.network-desk.toggleChrome', 'alt+shift+x');

        $this->getJson('/api/user/preferences')
            ->assertOk()
            ->assertJsonPath('preferences.keyboard_shortcuts.network-desk.toggleChrome', 'alt+shift+x');
    }

    public function test_keyboard_shortcuts_merge_with_existing_preferences(): void
    {
        UserPreference::query()->create([
            'workspace_id' => 'local-dev-workspace',
            'central_user_id' => 'local-dev-user-1',
            'preferences' => [
                'keyboard_shortcuts' => [
                    'network-desk' => [
                        'showHelp' => 'shift+/',
                    ],
                ],
            ],
        ]);

        $this->putJson('/api/user/preferences', [
            'preferences' => [
                'keyboard_shortcuts' => [
                    'network-desk' => [
                        'toggleChrome' => 'alt+shift+x',
                    ],
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('preferences.keyboard_shortcuts.network-desk.toggleChrome', 'alt+shift+x')
            ->assertJsonPath('preferences.keyboard_shortcuts.network-desk.showHelp', 'shift+/');
    }
}
