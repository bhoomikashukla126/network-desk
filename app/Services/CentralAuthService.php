<?php

namespace App\Services;

use App\Support\WorkspaceAppearance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CentralAuthService
{
    public function authorizationUrl(?string $state = null): string
    {
        return config('central.url').'/oauth/authorize?'.http_build_query([
            'client_id' => config('central.client_id'),
            'redirect_uri' => config('central.redirect_uri'),
            'response_type' => 'code',
            'scope' => config('central.scopes'),
            'state' => $state ?? bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asForm()
                ->post(config('central.url').'/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'client_id' => config('central.client_id'),
                    'client_secret' => config('central.client_secret'),
                    'redirect_uri' => config('central.redirect_uri'),
                    'code' => $code,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Cannot reach central auth server at '.config('central.url').': '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Token exchange failed ('.$response->status().'): '
                .($response->json('error_description') ?? $response->json('error') ?? $response->body())
            );
        }

        $token = $response->json();

        if (! is_array($token) || empty($token['access_token'])) {
            throw new RuntimeException('Token exchange returned an unexpected response.');
        }

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function userInfo(string $accessToken): array
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($accessToken)
                ->get(config('central.url').'/oauth/userinfo');
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Cannot reach central auth server at '.config('central.url').': '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Userinfo request failed ('.$response->status().'): '
                .($response->json('error_description') ?? $response->json('error') ?? $response->body())
            );
        }

        $user = $response->json();

        if (! is_array($user)) {
            throw new RuntimeException('Userinfo returned an unexpected response.');
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array<string, mixed>|null
     */
    public function resolveWorkspace(array $user, ?string $state): ?array
    {
        $fromOidc = is_array($user['workspace'] ?? null) ? $user['workspace'] : null;
        $fromState = $this->verifyLaunchState($state);

        $workspace = $fromOidc ?? $fromState;

        return is_array($workspace) ? $this->normalizeWorkspace($workspace) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verifyLaunchState(?string $state): ?array
    {
        if (! $state || ! str_contains($state, '.')) {
            return null;
        }

        [$encoded, $signature] = explode('.', $state, 2);

        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $encoded, (string) config('central.client_secret'), true)
        );

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encoded), true);

        if (! is_array($payload) || ! isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $this->normalizeWorkspace([
            'id' => $payload['workspace_id'],
            'name' => $payload['workspace_name'],
            'slug' => $payload['workspace_slug'],
            'domain' => $payload['workspace_domain'] ?? null,
            'language' => $payload['workspace_language'] ?? 'en',
            'theme_key' => $payload['workspace_theme_key'] ?? null,
            'color_mode' => $payload['workspace_color_mode'] ?? null,
            'is_owner' => $payload['workspace_is_owner'] ?? false,
            'role' => $payload['workspace_role'] ?? null,
            'permission' => $payload['workspace_permission'] ?? null,
            'max_activities' => $payload['max_activities'] ?? null,
            'max_storage_mb' => $payload['max_storage_mb'] ?? null,
            'max_storage_bytes' => $payload['max_storage_bytes'] ?? null,
            'usage_activities' => $payload['usage_activities'] ?? null,
            'usage_storage_mb' => $payload['usage_storage_mb'] ?? null,
            'usage_storage_bytes' => $payload['usage_storage_bytes'] ?? null,
            'remaining_activities' => $payload['remaining_activities'] ?? null,
            'remaining_storage_mb' => $payload['remaining_storage_mb'] ?? null,
            'remaining_storage_bytes' => $payload['remaining_storage_bytes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    private function normalizeWorkspace(array $workspace): array
    {
        $isOwner = (bool) ($workspace['is_owner'] ?? false);
        $role = (string) ($workspace['role'] ?? '');
        $permission = (string) ($workspace['permission'] ?? '');

        if ($role === 'owner' || $isOwner) {
            $isOwner = true;
            $role = 'owner';
            $permission = 'edit';
        } elseif ($role === '') {
            $role = $permission === 'edit' ? 'edit' : 'view';
        }

        if ($permission === '') {
            $permission = $role === 'edit' ? 'edit' : 'view';
        }

        $workspace['is_owner'] = $isOwner;
        $workspace['role'] = $role;
        $workspace['permission'] = $permission;

        return WorkspaceAppearance::mergeIntoContext($workspace);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid launch state payload.');
        }

        return $decoded;
    }
}
