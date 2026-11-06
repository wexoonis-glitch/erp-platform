<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LogShipment extends Model {
    use HasUuids;
    protected $table = 'log_shipment';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'sales_order_id', 'carrier_id', 'shipment_no', 'shipment_date', 'tracking_no', 'status', 'notes'];
    protected $casts = ['shipment_date' => 'date'];

    public function salesOrder() { return $this->belongsTo(OtcSalesOrder::class, 'sales_order_id'); }
    public function carrier() { return $this->belongsTo(LogCarrier::class, 'carrier_id'); }
    public function lines() { return $this->hasMany(LogShipmentLine::class, 'shipment_id'); }
}
