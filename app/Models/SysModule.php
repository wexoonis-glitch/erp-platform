<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysModule extends Model
{
    use HasUuids;

    protected $table = 'sys_module_registry';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key', 'name', 'version', 'provider', 'status',
        'manifest', 'description', 'sort_order',
    ];

    protected $casts = [
        'manifest'   => 'array',
        'sort_order' => 'integer',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
