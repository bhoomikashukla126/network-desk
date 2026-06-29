<?php

namespace App\Services;

use App\Support\WorkspaceAppearance;
use App\Support\WorkspaceSession;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CentralUserPreferenceService
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(Request $request): array
    {
        $response = $this->request($request, 'GET');

        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(Request $request, array $input): array
    {
        $payload = [
            'use_workspace_language' => ! filled($input['language'] ?? null),
            'use_workspace_theme' => ! filled($input['theme_key'] ?? null),
            'use_workspace_color_mode' => ! filled($input['color_mode'] ?? null),
        ];

        if (filled($input['language'] ?? null)) {
            $payload['language'] = $input['language'];
        }

        if (filled($input['theme_key'] ?? null)) {
            $payload['theme_key'] = $input['theme_key'];
        }

        if (filled($input['color_mode'] ?? null)) {
            $payload['color_mode'] = $input['color_mode'];
        }

        $response = $this->request($request, 'PUT', $payload);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        if (is_array($data['context'] ?? null)) {
            $this->applyContextToSession($request, $data['context']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function request(Request $request, string $method, array $input = []): array
    {
        $workspaceId = WorkspaceSession::id($request);
        $accessToken = (string) ($request->session()->get('central_tokens.access_token') ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Missing central access token.');
        }

        $centralUrl = rtrim((string) config('central.url'), '/');
        $url = "{$centralUrl}/api/workspaces/{$workspaceId}/user-preferences";

        try {
            $pending = Http::timeout(15)
                ->acceptJson()
                ->withToken($accessToken);

            $response = strtoupper($method) === 'PUT'
                ? $pending->put($url, $input)
                : $pending->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Cannot reach central server: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Preferences request failed ('.$response->status().'): '
                .($response->json('message') ?? $response->body())
            );
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function applyContextToSession(Request $request, array $context): void
    {
        $workspace = WorkspaceSession::get($request) ?? [];

        $updated = array_merge($workspace, array_filter([
            'language' => $context['language'] ?? null,
            'theme_key' => $context['theme_key'] ?? null,
            'color_mode' => $context['color_mode'] ?? null,
        ], fn ($value) => $value !== null));

        if (is_array($context['appearance'] ?? null)) {
            $updated['appearance'] = $context['appearance'];
        }

        $request->session()->put('central_workspace', WorkspaceAppearance::mergeIntoContext($updated));
    }
}
