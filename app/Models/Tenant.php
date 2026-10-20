<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'code',
        'name',
        'tier',
        'status',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
