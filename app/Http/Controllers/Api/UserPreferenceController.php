<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CentralUserPreferenceService;
use App\Support\WorkspaceAppearance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPreferenceController extends Controller
{
    public function __construct(
        protected CentralUserPreferenceService $preferences,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->preferences->fetch($request),
            'themes' => $this->themeOptions(),
            'languages' => config('workspace.languages'),
            'color_modes' => WorkspaceAppearance::COLOR_MODES,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'use_workspace_language' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', Rule::in(array_keys(config('workspace.languages')))],
            'use_workspace_theme' => ['nullable', 'boolean'],
            'theme_key' => ['nullable', 'string', Rule::in(array_keys(config('themes')))],
            'use_workspace_color_mode' => ['nullable', 'boolean'],
            'color_mode' => ['nullable', 'string', Rule::in(WorkspaceAppearance::COLOR_MODES)],
        ]);

        $input = [
            'language' => $request->boolean('use_workspace_language') ? null : ($validated['language'] ?? null),
            'theme_key' => $request->boolean('use_workspace_theme') ? null : ($validated['theme_key'] ?? null),
            'color_mode' => $request->boolean('use_workspace_color_mode') ? null : ($validated['color_mode'] ?? null),
        ];

        $data = $this->preferences->save($request, $input);

        return response()->json([
            'data' => $data,
            'message' => __('preferences.updated'),
        ]);
    }

    /**
     * @return array<string, array{name: string, primary: string, secondary: string, accent: string}>
     */
    private function themeOptions(): array
    {
        $themes = config('themes', []);

        return collect($themes)
            ->map(fn (array $theme, string $key) => [
                'key' => $key,
                'name' => $theme['name'] ?? $key,
                'primary' => $theme['primary'] ?? '#3B82F6',
                'secondary' => $theme['secondary'] ?? '#10B981',
                'accent' => $theme['accent'] ?? '#8B5CF6',
            ])
            ->keyBy('key')
            ->all();
    }
}
