<?php

namespace App\Models;

use App\Services\CableCoreConnectionCleanupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkPointDevice extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (NetworkPointDevice $device): void {
            app(CableCoreConnectionCleanupService::class)->detachDevice((int) $device->id);
        });
    }

    protected $fillable = [
        'network_point_id',
        'label',
        'type',
        'sort_order',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(NetworkPoint::class, 'network_point_id');
    }

    public function ports(): HasMany
    {
        return $this->hasMany(NetworkPointPort::class)->orderBy('sort_order')->orderBy('id');
    }
}
