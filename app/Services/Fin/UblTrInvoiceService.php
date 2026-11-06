<?php
namespace App\Services\Fin;

use App\Models\FinArInvoice;
use App\Models\SysCompanyProfile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UblTrInvoiceService
{
    /**
     * Bir müşteri faturasını UBL-TR 1.2 XML stringine dönüştürür.
     * ETTN yoksa üretir ve kaydeder.
     *
     * @throws ValidationException Zorunlu alanlar (VKN, unvan) eksikse
     */
    public function generate(FinArInvoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'lines.product']);

        $profile = SysCompanyProfile::where('tenant_id', $invoice->tenant_id)->first();
        if (!$profile) {
            throw ValidationException::withMessages(['profile' => 'Şirket profili tanımlı değil.']);
        }
        if (empty($profile->tax_no)) {
            throw ValidationException::withMessages(['profile' => 'Şirket profili için Vergi No (VKN) tanımlı olmalı.']);
        }
        if (empty($profile->legal_name)) {
            throw ValidationException::withMessages(['profile' => 'Şirket profili için Resmi Unvan tanımlı olmalı.']);
        }

        $customer = $invoice->customer;
        if (!$customer) {
            throw ValidationException::withMessages(['customer' => 'Fatura müşterisi bulunamadı.']);
        }
        if (empty($customer->tax_no)) {
            throw ValidationException::withMessages(['customer' => 'Müşteri VKN/TCKN tanımlı değil.']);
        }

        if ($invoice->lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Faturada satır yok.']);
        }

        // ETTN otomatik üret
        if (empty($invoice->ettn)) {
            $invoice->ettn = (string) Str::uuid();
            $invoice->save();
        }

        // Profil tipi: VKN varsa TICARIFATURA, aksi halde EARSIVFATURA
        $profileId = !empty($customer->tax_no) ? 'TICARIFATURA' : 'EARSIVFATURA';

        // Fatura tipi
        $invoiceTypeCode = 'SATIS';
        if (str_starts_with($invoice->notes ?? '', 'IADE')) {
            $invoiceTypeCode = 'IADE';
        }

        // Şehir/kasaba ayrıştır
        $supplierCity = $profile->city ?: 'Istanbul';
        $supplierTown  = $profile->city ?: 'Istanbul';

        $customerCity = $customer->address ?: ($profile->city ?: 'Istanbul');
        $customerTown = $profile->city ?: 'Istanbul';

        // XML üret
        $xml = view('xml.ubl-tr-invoice', [
            'invoice'          => $invoice,
            'profile'          => $profile,
            'customer'         => $customer,
            'profileId'        => $profileId,
            'invoiceTypeCode'  => $invoiceTypeCode,
            'issueDate'        => $invoice->invoice_date->format('Y-m-d'),
            'issueTime'        => $invoice->created_at?->format('H:i:s') ?? now()->format('H:i:s'),
            'currency'         => $invoice->currency ?: 'TRY',
            'lineCount'        => $invoice->lines->count(),
            'supplierCity'     => $supplierCity,
            'supplierTown'     => $supplierTown,
            'customerCity'     => $customerCity,
            'customerTown'     => $customerTown,
            'unitMapper'       => fn($u) => $this->mapUnit($u),
            'now'              => now(),
        ])->render();

        return $xml;
    }

    /**
     * İnsan okunur birim → UBL birim kodu (UN/ECE Recommendation 20).
     */
    public function mapUnit(?string $unit): string
    {
        $u = strtolower(trim((string) $unit));
        $map = [
            'adet' => 'C62', 'piece' => 'C62', 'pcs' => 'C62', 'unit' => 'C62',
            'kg' => 'KGM', 'kilogram' => 'KGM',
            'g' => 'GRM', 'gram' => 'GRM',
            'ton' => 'TNE',
            'metre' => 'MTR', 'm' => 'MTR', 'meter' => 'MTR',
            'cm' => 'CMT',
            'm2' => 'MTK', 'metrekare' => 'MTK',
            'm3' => 'MTQ', 'metreküp' => 'MTQ',
            'litre' => 'LTR', 'l' => 'LTR', 'lt' => 'LTR',
            'paket' => 'PA', 'pack' => 'PA',
            'kutu' => 'BX', 'box' => 'BX',
            'çuval' => 'BG',
            'rulo' => 'RO',
            'saat' => 'HUR',
            'gün' => 'DAY',
            'ay' => 'MON',
        ];
        return $map[$u] ?? 'C62';
    }
}
