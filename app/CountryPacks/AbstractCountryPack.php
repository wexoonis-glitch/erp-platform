<?php
namespace App\CountryPacks;

abstract class AbstractCountryPack
{
    abstract public function code(): string;          // 'TR', 'US'
    abstract public function name(): string;          // 'Türkiye', 'United States'
    abstract public function defaultLocale(): string;
    abstract public function defaultCurrency(): string;
    abstract public function defaultTimezone(): string;
    abstract public function taxLabel(): string;      // 'KDV', 'Sales Tax'
    abstract public function invoiceLabel(): string;  // 'Fatura', 'Invoice'

    /** Bu paket e-fatura/UBL-TR benzeri ülkeye özel XML üretir mi? */
    public function supportsEInvoiceXml(): bool { return false; }

    /** Bu paketin sağladığı ek servis/route isimleri (örn. 'invoice.xml') */
    public function extraRoutes(): array { return []; }

    /** Tenant aktive ettiğinde seed edilecek varsayılan vergi rejimleri */
    public function defaultTaxRegimes(): array { return []; }

    /** UI etiketleri — ülkeye özel metinler */
    public function labels(): array
    {
        return [
            'invoice' => $this->invoiceLabel(),
            'tax'     => $this->taxLabel(),
        ];
    }
}
