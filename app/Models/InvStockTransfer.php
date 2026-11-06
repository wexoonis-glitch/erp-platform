<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvStockTransfer extends Model {
    use HasUuids;
    protected $table = 'inv_stock_transfer';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'transfer_no', 'from_warehouse_id', 'to_warehouse_id', 'transfer_date', 'status', 'notes'];
    protected $casts = ['transfer_date' => 'date'];

    public function fromWarehouse() { return $this->belongsTo(InvWarehouse::class, 'from_warehouse_id'); }
    public function toWarehouse() { return $this->belongsTo(InvWarehouse::class, 'to_warehouse_id'); }
    public function lines() { return $this->hasMany(InvStockTransferLine::class, 'transfer_id'); }
}
