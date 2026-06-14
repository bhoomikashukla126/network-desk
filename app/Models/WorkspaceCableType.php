<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'workspace_id',
    'key',
    'label',
    'color',
    'sort_order',
])]
class WorkspaceCableType extends Model
{
}
