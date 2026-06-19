<?php

namespace App\Models;

use App\Services\CableCoreConnectionCleanupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CableCoreEnd extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (CableCoreEnd $end): void {
            app(CableCoreConnectionCleanupService::class)->detachCoreEnd($end);
        });
    }

    protected $fillable = [
        'cable_core_id',
        'side',
        'network_point_id',
        'connection_type',
        'connected_core_end_id',
        'network_point_port_id',
        'network_point_device_id',
        'device_type',
        'device_label',
        'device_port_label',
        'device_port_direction',
    ];

    public function core(): BelongsTo
    {
        return $this->belongsTo(CableCore::class, 'cable_core_id');
    }

    public function networkPoint(): BelongsTo
    {
        return $this->belongsTo(NetworkPoint::class);
    }

    public function networkPointPort(): BelongsTo
    {
        return $this->belongsTo(NetworkPointPort::class);
    }

    public function networkPointDevice(): BelongsTo
    {
        return $this->belongsTo(NetworkPointDevice::class);
    }

    public function connectedCoreEnd(): BelongsTo
    {
        return $this->belongsTo(self::class, 'connected_core_end_id');
    }
}
