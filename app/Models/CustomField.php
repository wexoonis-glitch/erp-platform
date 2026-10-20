<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasUuids;

    protected $table = 'custom_field';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'entity', 'code', 'label', 'data_type', 'config',
    ];

    protected $casts = ['config' => 'array'];
}
