<?php

namespace App\Models;

use App\Services\CableCoreConnectionCleanupService;
use App\Support\CableRoute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'from_point_id',
    'to_point_id',
    'name',
    'cable_type',
    'status',
    'length_m',
    'path',
    'route',
    'core_count',
    'notes',
    'created_by',
])]
class CableSegment extends Model
{
    protected static function booted(): void
    {
        static::saving(function (CableSegment $cable): void {
            $route = CableRoute::normalize($cable->route, null);

            if ($route === [] && $cable->from_point_id && $cable->to_point_id) {
                $route = CableRoute::fromLegacy($cable);
            }

            if ($route === []) {
                return;
            }

            $cable->route = $route;
            $pointIds = CableRoute::pointIds($route);

            if (count($pointIds) >= 2) {
                $cable->from_point_id = $pointIds[0];
                $cable->to_point_id = $pointIds[array_key_last($pointIds)];
                $cable->path = CableRoute::legacyPath($route);
            }
        });

        static::deleting(function (CableSegment $cable): void {
            app(CableCoreConnectionCleanupService::class)->detachCable($cable);
        });
    }

    protected function casts(): array
    {
        return [
            'length_m' => 'float',
            'path' => 'array',
            'route' => 'array',
        ];
    }

    public function fromPoint(): BelongsTo
    {
        return $this->belongsTo(NetworkPoint::class, 'from_point_id');
    }

    public function toPoint(): BelongsTo
    {
        return $this->belongsTo(NetworkPoint::class, 'to_point_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CableImage::class)->orderBy('sort_order');
    }

    public function cores(): HasMany
    {
        return $this->hasMany(CableCore::class)->orderBy('core_number');
    }
}
