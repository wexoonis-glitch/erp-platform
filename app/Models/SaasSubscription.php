<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SaasSubscription extends Model {
    use HasUuids;
    protected $table = 'saas_subscription';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'plan_id', 'start_date', 'end_date', 'status'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function tenant() { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function plan() { return $this->belongsTo(SaasPlan::class, 'plan_id'); }
}
