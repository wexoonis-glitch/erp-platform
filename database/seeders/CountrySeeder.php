<?php
namespace Database\Seeders;

use App\Models\SysCountry;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['TR', 'Türkiye', 'TRY', 'tr', 'Europe/Istanbul', 'KDV', 'd.m.Y', '1.234,56'],
            ['US', 'United States', 'USD', 'en', 'America/New_York', 'Sales Tax', 'm/d/Y', '1,234.56'],
            ['GB', 'United Kingdom', 'GBP', 'en', 'Europe/London', 'VAT', 'd/m/Y', '1,234.56'],
            ['DE', 'Germany', 'EUR', 'de', 'Europe/Berlin', 'VAT', 'd.m.Y', '1.234,56'],
            ['FR', 'France', 'EUR', 'fr', 'Europe/Paris', 'VAT', 'd/m/Y', '1 234,56'],
            ['IT', 'Italy', 'EUR', 'it', 'Europe/Rome', 'IVA', 'd/m/Y', '1.234,56'],
            ['ES', 'Spain', 'EUR', 'es', 'Europe/Madrid', 'IVA', 'd/m/Y', '1.234,56'],
            ['NL', 'Netherlands', 'EUR', 'nl', 'Europe/Amsterdam', 'BTW', 'd-m-Y', '1.234,56'],
            ['AE', 'United Arab Emirates', 'AED', 'ar', 'Asia/Dubai', 'VAT', 'd/m/Y', '1,234.56'],
            ['SA', 'Saudi Arabia', 'SAR', 'ar', 'Asia/Riyadh', 'VAT', 'd/m/Y', '1,234.56'],
            ['IN', 'India', 'INR', 'en', 'Asia/Kolkata', 'GST', 'd/m/Y', '1,23,456.78'],
            ['BR', 'Brazil', 'BRL', 'pt', 'America/Sao_Paulo', 'ICMS', 'd/m/Y', '1.234,56'],
            ['MX', 'Mexico', 'MXN', 'es', 'America/Mexico_City', 'IVA', 'd/m/Y', '1,234.56'],
            ['JP', 'Japan', 'JPY', 'ja', 'Asia/Tokyo', 'Consumption Tax', 'Y/m/d', '1,234'],
            ['CN', 'China', 'CNY', 'zh', 'Asia/Shanghai', 'VAT', 'Y/m/d', '1,234.56'],
            ['RU', 'Russia', 'RUB', 'ru', 'Europe/Moscow', 'VAT', 'd.m.Y', '1 234,56'],
            ['AZ', 'Azerbaijan', 'AZN', 'az', 'Asia/Baku', 'ƏDV', 'd.m.Y', '1.234,56'],
            ['KZ', 'Kazakhstan', 'KZT', 'kk', 'Asia/Almaty', 'VAT', 'd.m.Y', '1 234,56'],
            ['CA', 'Canada', 'CAD', 'en', 'America/Toronto', 'GST/HST', 'd/m/Y', '1,234.56'],
            ['AU', 'Australia', 'AUD', 'en', 'Australia/Sydney', 'GST', 'd/m/Y', '1,234.56'],
        ];

        foreach ($countries as [$code, $name, $cur, $locale, $tz, $tax, $dateFmt, $numFmt]) {
            SysCountry::updateOrCreate(['code' => $code], [
                'name' => $name,
                'default_currency' => $cur,
                'default_locale' => $locale,
                'default_timezone' => $tz,
                'tax_label' => $tax,
                'date_format' => $dateFmt,
                'number_format' => $numFmt,
                'is_active' => true,
            ]);
        }
    }
}
