<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysImportLog extends Model {
    use HasUuids;
    protected $table = 'sys_import_log';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'entity', 'file_name', 'total_rows',
        'created_count', 'updated_count', 'error_count',
        'errors', 'status', 'user_id',
    ];
    protected $casts = [
        'errors' => 'array',
        'total_rows' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'error_count' => 'integer',
    ];
}
