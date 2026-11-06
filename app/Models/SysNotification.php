<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysNotification extends Model {
    use HasUuids;
    protected $table = 'sys_notification';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'title', 'message', 'link', 'is_read', 'read_at'
    ];
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
