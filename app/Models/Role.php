<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * @return array<int, string>
     */
    public function permissionKeys(): array
    {
        return $this->permissions->pluck('key')->all();
    }
}
