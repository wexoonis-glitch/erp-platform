<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomObject extends Model
{
    use HasUuids;

    protected $table = 'custom_object';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['tenant_id', 'code', 'label', 'schema'];

    protected $casts = ['schema' => 'array'];
}
