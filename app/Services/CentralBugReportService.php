<?php

namespace App\Services;

use App\Support\WorkspaceSession;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CentralBugReportService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(Request $request): array
    {
        $response = $this->request($request, 'GET');
        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function submit(Request $request, array $input): array
    {
        $response = $this->request($request, 'POST', array_merge($input, [
            'extension_slug' => (string) config('central.slug'),
            'page_url' => $input['page_url'] ?? $request->headers->get('Referer'),
        ]));

        return is_array($response['data'] ?? null) ? $response['data'] : [];
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
        $url = "{$centralUrl}/api/workspaces/{$workspaceId}/bug-reports";

        try {
            $pending = Http::timeout(15)
                ->acceptJson()
                ->withToken($accessToken);

            $response = strtoupper($method) === 'POST'
                ? $pending->post($url, $input)
                : $pending->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Cannot reach central server: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Bug report request failed ('.$response->status().'): '
                .($response->json('message') ?? $response->body())
            );
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }
}
