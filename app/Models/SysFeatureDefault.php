<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysFeatureDefault extends Model
{
    protected $table = 'sys_feature_default';
    protected $primaryKey = 'feature_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['feature_key', 'enabled', 'config'];

    protected $casts = [
        'enabled' => 'boolean',
        'config'  => 'array',
    ];
}
