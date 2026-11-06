<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysRoleModule extends Model {
    use HasUuids;
    protected $table = 'sys_role_module';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'role_id', 'module_id'];

    public function role() { return $this->belongsTo(SysRole::class, 'role_id'); }
}
