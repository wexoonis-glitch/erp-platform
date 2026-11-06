<?php
namespace App\CountryPacks\TR;

use App\CountryPacks\AbstractCountryPack;

class TrPack extends AbstractCountryPack
{
    public function code(): string { return 'TR'; }
    public function name(): string { return 'Türkiye'; }
    public function defaultLocale(): string { return 'tr'; }
    public function defaultCurrency(): string { return 'TRY'; }
    public function defaultTimezone(): string { return 'Europe/Istanbul'; }
    public function taxLabel(): string { return 'KDV'; }
    public function invoiceLabel(): string { return 'Fatura'; }

    public function supportsEInvoiceXml(): bool { return true; }
    public function extraRoutes(): array { return ['fin.ar-invoices.xml']; }

    public function defaultTaxRegimes(): array
    {
        return [
            ['code' => 'TR-KDV-1',  'name' => 'KDV %1',  'rate' => 1.0,  'is_default' => false],
            ['code' => 'TR-KDV-10', 'name' => 'KDV %10', 'rate' => 10.0, 'is_default' => false],
            ['code' => 'TR-KDV-20', 'name' => 'KDV %20', 'rate' => 20.0, 'is_default' => true],
        ];
    }
}
