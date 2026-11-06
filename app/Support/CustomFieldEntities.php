<?php
namespace App\Support;

/**
 * Custom Field'ların uygulanabileceği entity'lerin merkezi listesi.
 * Yeni bir entity eklemek için: (a) buraya ekle, (b) tabloya custom_fields jsonb kolonu ekle,
 * (c) model cast'ine custom_fields ekle, (d) formda <x-custom-fields-inputs entity="..." /> çağır.
 */
final class CustomFieldEntities
{
    public const LIST = [
        'fin_customer'        => 'Müşteri',
        'fin_supplier'        => 'Tedarikçi',
        'inv_product'         => 'Ürün',
        'hr_employee'         => 'Personel',
        'fin_ar_invoice'      => 'Müşteri Faturası',
        'fin_ap_invoice'      => 'Tedarikçi Faturası',
        'prc_purchase_order'  => 'Satın Alma Siparişi',
        'otc_sales_order'     => 'Satış Siparişi',
        'eam_asset'           => 'Varlık',
    ];

    public static function codes(): array
    {
        return array_keys(self::LIST);
    }

    public static function label(?string $code): string
    {
        if (!$code) return '—';
        return self::LIST[$code] ?? $code;
    }
}
