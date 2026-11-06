<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QmNcr extends Model {
    use HasUuids;
    protected $table = 'qm_ncr';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'product_id', 'ncr_no', 'report_date', 'source', 'reference_no', 'quantity', 'status', 'description'];
    protected $casts = ['report_date' => 'date', 'quantity' => 'decimal:4'];

    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
}
