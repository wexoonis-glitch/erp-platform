<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SysCountry extends Model {
    protected $table = 'sys_country';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'code', 'name', 'default_currency', 'default_locale', 'default_timezone',
        'tax_label', 'date_format', 'number_format', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];
}
