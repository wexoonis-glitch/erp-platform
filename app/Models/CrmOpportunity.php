<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CrmOpportunity extends Model {
    use HasUuids;
    protected $table = 'crm_opportunity';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'customer_id', 'name', 'amount', 'expected_close_date', 'stage', 'probability', 'notes'];
    protected $casts = ['amount' => 'decimal:4', 'expected_close_date' => 'date', 'probability' => 'integer'];

    public function customer() { return $this->belongsTo(FinCustomer::class, 'customer_id'); }
}
