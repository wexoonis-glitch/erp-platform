<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinCostCenter extends Model {
    use HasUuids;
    protected $table = 'fin_cost_center';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'code', 'name', 'manager_name', 'status'];
}
