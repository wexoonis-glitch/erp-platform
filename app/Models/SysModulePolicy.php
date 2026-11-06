<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysModulePolicy extends Model
{
    use HasUuids;

    protected $table = 'sys_module_policy';
    public $incrementing = false;
    protected $keyType = 'string';

    public const MODE_OVERRIDE = 'override';
    public const MODE_MERGE    = 'merge';
    public const MODE_PATCH    = 'patch';

    protected $fillable = [
        'tenant_id', 'scope_type', 'scope_id', 'modules',
        'mode', 'priority', 'conditions', 'is_active', 'created_by',
    ];

    protected $casts = [
        'modules'    => 'array',
        'conditions' => 'array',
        'is_active'  => 'boolean',
        'priority'   => 'integer',
    ];
}
