<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SaasPlan extends Model {
    use HasUuids;
    protected $table = 'saas_plan';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['name', 'price', 'billing_cycle', 'features', 'status'];
    protected $casts = ['price' => 'decimal:4', 'features' => 'array'];
}
