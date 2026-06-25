<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\CableImageController;
use App\Http\Controllers\Api\CableSegmentController;
use App\Http\Controllers\Api\MediaUploadController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NetworkDashboardController;
use App\Http\Controllers\Api\NetworkPointController;
use App\Http\Controllers\Api\PointImageController;
use App\Http\Controllers\Api\WorkspaceCableTypeController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\Auth\CentralAuthController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\PublicLocaleController;
use App\Http\Controllers\WelcomeController;
use App\Http\Middleware\EnsureCentralAuthenticated;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SyncWorkspaceMember;
use App\Http\Middleware\TrackWorkspaceQuota;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('welcome');
Route::get('/docs', DocumentationController::class)->name('docs');
Route::get('/locale/{locale}', PublicLocaleController::class)->name('public.locale');
Route::get('/login', [CentralAuthController::class, 'login'])->name('login');
Route::get('/auth/callback', [CentralAuthController::class, 'callback'])->name('auth.callback');
Route::post('/logout', [CentralAuthController::class, 'logout'])->name('auth.logout');

Route::middleware([
    EnsureCentralAuthenticated::class,
    SyncWorkspaceMember::class,
    \App\Http\Middleware\SetLocaleFromWorkspace::class,
])->group(function () {
    Route::prefix('api')->group(function () {
        Route::get('/session', [SessionController::class, 'show']);
        Route::post('/session/quotas/refresh', [SessionController::class, 'refreshQuotas']);
        Route::get('/user/preferences', [UserPreferenceController::class, 'show']);
        Route::put('/user/preferences', [UserPreferenceController::class, 'update']);

        Route::middleware(TrackWorkspaceQuota::class)->group(function () {
            Route::get('/activity', [ActivityLogController::class, 'index']);

            Route::get('/network/meta', [NetworkPointController::class, 'meta'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::get('/network/dashboard', [NetworkDashboardController::class, 'show'])
                ->middleware(EnsurePermission::class.':network.view');

            Route::get('/network/points', [NetworkPointController::class, 'index'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::get('/network/points/{networkPoint}', [NetworkPointController::class, 'show'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::post('/network/points', [NetworkPointController::class, 'store'])
                ->middleware(EnsurePermission::class.':network.create');
            Route::put('/network/points/{networkPoint}', [NetworkPointController::class, 'update'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::delete('/network/points/{networkPoint}', [NetworkPointController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':network.delete');

            Route::post('/network/points/{networkPoint}/images', [PointImageController::class, 'store'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::delete('/network/points/{networkPoint}/images/{pointImage}', [PointImageController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':network.edit');

            Route::get('/network/cables', [CableSegmentController::class, 'index'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::get('/network/cables/{cableSegment}', [CableSegmentController::class, 'show'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::get('/network/cables/{cableSegment}/core-connections', [CableSegmentController::class, 'coreConnectionOptions'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::get('/network/cables/{cableSegment}/split-options', [CableSegmentController::class, 'splitOptions'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::get('/network/cables/{cableSegment}/join-candidates', [CableSegmentController::class, 'joinCandidates'])
                ->middleware(EnsurePermission::class.':network.view');
            Route::post('/network/cables/{cableSegment}/split', [CableSegmentController::class, 'split'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::post('/network/cables/{cableSegment}/join', [CableSegmentController::class, 'join'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::post('/network/cables', [CableSegmentController::class, 'store'])
                ->middleware(EnsurePermission::class.':network.create');
            Route::put('/network/cables/{cableSegment}', [CableSegmentController::class, 'update'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::delete('/network/cables/{cableSegment}', [CableSegmentController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':network.delete');

            Route::post('/network/cables/{cableSegment}/images', [CableImageController::class, 'store'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::delete('/network/cables/{cableSegment}/images/{cableImage}', [CableImageController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':network.edit');

            Route::post('/network/cable-types', [WorkspaceCableTypeController::class, 'store'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::delete('/network/cable-types/{workspaceCableType}', [WorkspaceCableTypeController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':network.edit');

            Route::post('/uploads', [MediaUploadController::class, 'store'])
                ->middleware(EnsurePermission::class.':network.edit');
            Route::delete('/uploads', [MediaUploadController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':network.edit');

            Route::middleware(EnsurePermission::class.':roles.manage')->group(function () {
                Route::get('/permissions', [RoleController::class, 'permissions']);
                Route::get('/roles', [RoleController::class, 'index']);
                Route::post('/roles', [RoleController::class, 'store']);
                Route::put('/roles/{role}', [RoleController::class, 'update']);
                Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
            });

            Route::middleware(EnsurePermission::class.':members.manage')->group(function () {
                Route::get('/members', [MemberController::class, 'index']);
                Route::put('/members/{member}', [MemberController::class, 'update']);
            });
        });
    });

    Route::get('/{any}', AppController::class)->where('any', '.+')->name('app');
});
