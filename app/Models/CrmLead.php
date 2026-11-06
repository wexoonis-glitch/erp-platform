<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model {
    use HasUuids;
    protected $table = 'crm_lead';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'name', 'company', 'email', 'phone', 'source', 'status', 'notes'];

    public function opportunities() { return $this->hasMany(CrmOpportunity::class, 'lead_id'); }
}
