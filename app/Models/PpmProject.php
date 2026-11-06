<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PpmProject extends Model {
    use HasUuids;
    protected $table = 'ppm_project';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'customer_id', 'cost_center_id', 'code', 'name', 'start_date', 'end_date', 'budget', 'status', 'description'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'budget' => 'decimal:4'];

    public function customer() { return $this->belongsTo(FinCustomer::class, 'customer_id'); }
    public function costCenter() { return $this->belongsTo(FinCostCenter::class, 'cost_center_id'); }
    public function tasks() { return $this->hasMany(PpmTask::class, 'project_id'); }
    public function expenses() { return $this->hasMany(PpmExpense::class, 'project_id'); }
}
