<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QmInspectionCriteria extends Model {
    use HasUuids;
    protected $table = 'qm_inspection_criteria';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'plan_id', 'name', 'expected_value', 'line_no'];
}
