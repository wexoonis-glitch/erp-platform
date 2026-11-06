<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysCountryPack extends Model {
    use HasUuids;
    protected $table = 'sys_country_pack';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'pack_code', 'version', 'is_active', 'settings'];
    protected $casts = ['is_active' => 'boolean', 'settings' => 'array'];
}
