<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysUserNavItem extends Model {
    use HasUuids;

    protected $table = 'sys_user_nav_item';
    public $incrementing = false;
    protected $keyType = 'string';

    public const KIND_FAVORITE = 'favorite';
    public const KIND_RECENT   = 'recent';

    protected $fillable = [
        'tenant_id', 'user_id', 'route_name', 'kind',
        'sort_order', 'use_count', 'last_used_at',
    ];

    protected $casts = [
        'sort_order'   => 'integer',
        'use_count'    => 'integer',
        'last_used_at' => 'datetime',
    ];
}
