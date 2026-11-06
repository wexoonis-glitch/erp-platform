<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysTenantSetting extends Model {
    use HasUuids;

    protected $table = 'sys_tenant_setting';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['tenant_id', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];
}
