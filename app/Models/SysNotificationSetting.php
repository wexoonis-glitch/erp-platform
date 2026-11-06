<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysNotificationSetting extends Model {
    use HasUuids;
    protected $table = 'sys_notification_settings';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'event_key', 'channel', 'recipient_type', 'custom_email', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
