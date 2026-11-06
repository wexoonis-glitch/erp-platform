<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysRole extends Model {
    use HasUuids;
    protected $table = 'sys_role';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'code', 'name', 'description', 'is_system', 'is_active'];
    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean'];

    public function modules() { return $this->hasMany(SysRoleModule::class, 'role_id'); }
    public function moduleCodes(): array { return $this->modules()->pluck('module_id')->toArray(); }
}
