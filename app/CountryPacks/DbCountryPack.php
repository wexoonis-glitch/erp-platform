<?php
namespace App\CountryPacks;

use App\Models\SysCountry;

/**
 * sys_country tablosundan okuyan generic pack.
 * Kod yazmadan yeni ülke eklenebilmesini sağlar.
 */
class DbCountryPack extends AbstractCountryPack
{
    public function __construct(private readonly SysCountry $country) {}

    public function code(): string { return strtoupper($this->country->code); }
    public function name(): string { return $this->country->name; }
    public function defaultLocale(): string { return $this->country->default_locale; }
    public function defaultCurrency(): string { return $this->country->default_currency; }
    public function defaultTimezone(): string { return $this->country->default_timezone; }
    public function taxLabel(): string { return $this->country->tax_label ?: 'Tax'; }
    public function invoiceLabel(): string { return 'Invoice'; }

    public function supportsEInvoiceXml(): bool { return false; }

    public function defaultTaxRegimes(): array
    {
        // Generic pack: kullanıcı sonradan düzenleyebilsin diye 0% bir tane
        return [
            [
                'code' => $this->code() . '-STD',
                'name' => $this->taxLabel() . ' Standard',
                'rate' => 0.0,
                'is_default' => true,
            ],
        ];
    }
}
