<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PpmTask extends Model {
    use HasUuids;
    protected $table = 'ppm_task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'project_id', 'name', 'status', 'due_date', 'estimated_hours', 'actual_hours', 'description'];
    protected $casts = ['due_date' => 'date', 'estimated_hours' => 'decimal:2', 'actual_hours' => 'decimal:2'];
}
