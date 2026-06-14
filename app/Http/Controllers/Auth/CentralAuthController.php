<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CentralAuthService;
use App\Services\WorkspaceAccessService;
use App\Support\LocalDevAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class CentralAuthController extends Controller
{
    public function __construct(
        protected CentralAuthService $centralAuth,
        protected WorkspaceAccessService $workspaceAccess,
    ) {}

    public function callback(Request $request): RedirectResponse|Response
    {
        $code = $request->string('code');

        if ($code->isEmpty()) {
            return $this->ssoError('Missing authorization code.', 400);
        }

        $missing = collect([
            'CENTRAL_URL' => config('central.url'),
            'CLIENT_ID' => config('central.client_id'),
            'CLIENT_SECRET' => config('central.client_secret'),
            'REDIRECT_URI' => config('central.redirect_uri'),
        ])->filter(fn ($value) => blank($value))->keys()->all();

        if ($missing !== []) {
            return $this->ssoError(
                'SSO is not configured. Missing: '.implode(', ', $missing),
                500,
            );
        }

        try {
            $token = $this->centralAuth->exchangeCode($code->value());
            $user = $this->centralAuth->userInfo($token['access_token']);
            $workspace = $this->centralAuth->resolveWorkspace(
                $user,
                $request->string('state')->value() ?: null,
            );

            $request->session()->put('central_user', $user);
            $request->session()->put('central_workspace', $workspace);
            $request->session()->put('central_tokens', $token);
            $request->session()->regenerate();

            $this->workspaceAccess->syncMember($request);
        } catch (Throwable $exception) {
            Log::error('SSO callback failed', [
                'message' => $exception->getMessage(),
                'redirect_uri' => config('central.redirect_uri'),
                'central_url' => config('central.url'),
                'exception' => $exception,
            ]);

            report($exception);

            return $this->ssoError('SSO failed: '.$exception->getMessage(), 500);
        }

        return redirect()->route('welcome');
    }

    public function login(Request $request): RedirectResponse
    {
        if ($request->session()->has('central_user')) {
            return redirect()->route('welcome');
        }

        if (LocalDevAuth::enabled()) {
            LocalDevAuth::bootstrap($request, $this->workspaceAccess);

            return redirect()->route('welcome');
        }

        if (blank(config('central.client_id'))) {
            abort(500, 'SSO is not configured. Set CLIENT_ID and CLIENT_SECRET, or enable LOCAL_DEV_AUTH=true for local development.');
        }

        return redirect()->away($this->centralAuth->authorizationUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }

    private function ssoError(string $message, int $status): Response
    {
        return response($message, $status)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
