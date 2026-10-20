<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomObjectData extends Model
{
    use HasUuids;

    protected $table = 'custom_object_data';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['tenant_id', 'object_id', 'data', 'created_at', 'updated_at'];

    protected $casts = [
        'data'       => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
