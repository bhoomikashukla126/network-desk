<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'network_point_id',
    'path',
    'url',
    'caption',
    'sort_order',
])]
class PointImage extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function point(): BelongsTo
    {
        return $this->belongsTo(NetworkPoint::class, 'network_point_id');
    }
}
