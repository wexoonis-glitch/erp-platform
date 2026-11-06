<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysCompanyProfile extends Model {
    use HasUuids;
    protected $table = 'sys_company_profile';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'legal_name', 'display_name', 'tax_no', 'tax_office',
        'address', 'city', 'country', 'postal_code', 'phone', 'email', 'website',
        'logo_path', 'default_currency', 'fiscal_year_start_month',
        'invoice_prefix', 'purchase_invoice_prefix', 'sales_order_prefix', 'purchase_order_prefix',
        'einvoice_registered', 'einvoice_integrator', 'notes',
    ];
    protected $casts = [
        'einvoice_registered' => 'boolean',
        'fiscal_year_start_month' => 'integer',
    ];
}
