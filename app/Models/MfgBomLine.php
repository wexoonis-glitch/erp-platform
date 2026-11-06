<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MfgBomLine extends Model {
    use HasUuids;
    protected $table = 'mfg_bom_line';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'bom_id', 'component_id', 'quantity', 'line_no'];
    protected $casts = ['quantity' => 'decimal:4'];

    public function component() { return $this->belongsTo(InvProduct::class, 'component_id'); }
}
