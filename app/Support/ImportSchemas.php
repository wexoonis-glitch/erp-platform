<?php
namespace App\Support;

use App\Models\FinCustomer;
use App\Models\FinSupplier;
use App\Models\InvProduct;

/**
 * CSV içe/dışa aktarma şemaları. Yeni bir entity eklemek için buraya bir blok ekle.
 */
final class ImportSchemas
{
    public static function all(): array
    {
        return [
            'products' => [
                'label'       => 'Ürünler',
                'model'       => InvProduct::class,
                'unique_by'   => 'code',
                'columns'     => [
                    'code'           => ['required', 'string', 'max:50'],
                    'name'           => ['required', 'string', 'max:255'],
                    'unit'           => ['nullable', 'string', 'max:20'],
                    'sales_price'    => ['nullable', 'numeric', 'min:0'],
                    'purchase_price' => ['nullable', 'numeric', 'min:0'],
                    'vat_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
                    'track_stock'    => ['nullable', 'in:0,1,true,false,yes,no,evet,hayır'],
                ],
                'defaults'    => ['unit' => 'Adet', 'track_stock' => 1, 'vat_rate' => 20, 'sales_price' => 0, 'purchase_price' => 0],
                'sample'      => [
                    ['PRD-001', 'Kalem', 'Adet', '100', '60', '20', '1'],
                    ['PRD-002', 'Defter', 'Adet', '50', '30', '20', '1'],
                ],
            ],

            'customers' => [
                'label'       => 'Müşteriler',
                'model'       => FinCustomer::class,
                'unique_by'   => 'code',
                'columns'     => [
                    'code'       => ['required', 'string', 'max:50'],
                    'name'       => ['required', 'string', 'max:255'],
                    'tax_no'     => ['nullable', 'string', 'max:30'],
                    'tax_office' => ['nullable', 'string', 'max:100'],
                    'email'      => ['nullable', 'email', 'max:255'],
                    'phone'      => ['nullable', 'string', 'max:30'],
                    'address'    => ['nullable', 'string', 'max:500'],
                    'currency'   => ['nullable', 'string', 'size:3'],
                    'status'     => ['nullable', 'in:active,inactive'],
                ],
                'defaults'    => ['currency' => 'TRY', 'status' => 'active'],
                'sample'      => [
                    ['M001', 'ABC Ltd', '1234567890', 'Beşiktaş', 'info@abc.com', '+905551112233', 'Adres', 'TRY', 'active'],
                ],
            ],

            'suppliers' => [
                'label'       => 'Tedarikçiler',
                'model'       => FinSupplier::class,
                'unique_by'   => 'code',
                'columns'     => [
                    'code'       => ['required', 'string', 'max:50'],
                    'name'       => ['required', 'string', 'max:255'],
                    'tax_no'     => ['nullable', 'string', 'max:30'],
                    'tax_office' => ['nullable', 'string', 'max:100'],
                    'email'      => ['nullable', 'email', 'max:255'],
                    'phone'      => ['nullable', 'string', 'max:30'],
                    'address'    => ['nullable', 'string', 'max:500'],
                    'currency'   => ['nullable', 'string', 'size:3'],
                    'status'     => ['nullable', 'in:active,inactive'],
                ],
                'defaults'    => ['currency' => 'TRY', 'status' => 'active'],
                'sample'      => [
                    ['T001', 'XYZ Tedarik', '9876543210', 'Kadıköy', 'satis@xyz.com', '+905554443322', 'Adres', 'TRY', 'active'],
                ],
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function headers(string $key): array
    {
        $schema = self::get($key);
        return $schema ? array_keys($schema['columns']) : [];
    }
}
