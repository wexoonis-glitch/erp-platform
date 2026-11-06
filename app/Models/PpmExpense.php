<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PpmExpense extends Model {
    use HasUuids;
    protected $table = 'ppm_expense';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'project_id', 'expense_date', 'description', 'amount'];
    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:4'];
}
