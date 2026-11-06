<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MfgBom extends Model {
    use HasUuids;
    protected $table = 'mfg_bom';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'product_id', 'code', 'name', 'quantity', 'status'];
    protected $casts = ['quantity' => 'decimal:4'];

    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
    public function lines() { return $this->hasMany(MfgBomLine::class, 'bom_id'); }
}
