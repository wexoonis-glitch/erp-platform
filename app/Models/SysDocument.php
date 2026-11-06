<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysDocument extends Model {
    use HasUuids;
    protected $table = 'sys_document';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'entity_type', 'entity_id', 'name', 'file_path',
        'mime_type', 'file_size', 'uploaded_by',
    ];
    protected $casts = ['file_size' => 'integer'];
}
