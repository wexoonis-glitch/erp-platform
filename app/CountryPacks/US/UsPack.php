<?php
namespace App\CountryPacks\US;

use App\CountryPacks\AbstractCountryPack;

class UsPack extends AbstractCountryPack
{
    public function code(): string { return 'US'; }
    public function name(): string { return 'United States'; }
    public function defaultLocale(): string { return 'en'; }
    public function defaultCurrency(): string { return 'USD'; }
    public function defaultTimezone(): string { return 'America/New_York'; }
    public function taxLabel(): string { return 'Sales Tax'; }
    public function invoiceLabel(): string { return 'Invoice'; }

    public function supportsEInvoiceXml(): bool { return false; }

    public function defaultTaxRegimes(): array
    {
        // ABD'de sales tax eyalet bazlı — tenant kendi eyaletini seçer
        return [
            ['code' => 'US-NY-SALES', 'name' => 'New York Sales Tax', 'rate' => 8.875, 'region' => 'NY', 'is_default' => false],
            ['code' => 'US-CA-SALES', 'name' => 'California Sales Tax', 'rate' => 7.25, 'region' => 'CA', 'is_default' => false],
            ['code' => 'US-TX-SALES', 'name' => 'Texas Sales Tax', 'rate' => 6.25, 'region' => 'TX', 'is_default' => false],
            ['code' => 'US-EXEMPT',   'name' => 'Exempt (0%)', 'rate' => 0.0, 'region' => null, 'is_default' => true],
        ];
    }
}
