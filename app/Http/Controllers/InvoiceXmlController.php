<?php
namespace App\Http\Controllers;

use App\CountryPacks\CountryPackManager;
use App\Models\FinArInvoice;
use App\Models\Tenant;
use App\Services\Fin\UblTrInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceXmlController extends Controller {
    public function show(Request $request, string $id) {
        $invoice = FinArInvoice::findOrFail($id);

        if (!\Auth::user()->isSuperadmin() && $invoice->tenant_id !== \Auth::user()->tenant_id) {
            abort(403);
        }

        // Country pack UBL-TR gibi ülkeye özel XML'i destekliyor mu?
        $tenant = Tenant::find($invoice->tenant_id);
        $pack = $tenant ? app(CountryPackManager::class)->forTenant($tenant) : null;
        if (!$pack || !$pack->supportsEInvoiceXml()) {
            return redirect()->route('fin.ar-invoices')->with('xml_error', 'Bu tenant\'ın country pack\'i e-fatura XML üretimini desteklemiyor. Ülke ayarlarını kontrol edin.');
        }

        try {
            $xml = app(UblTrInvoiceService::class)->generate($invoice);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'XML üretilemedi.';
            return redirect()->route('fin.ar-invoices')->with('xml_error', $msg);
        }

        $safeNo = preg_replace('/[^A-Za-z0-9\-]/', '', $invoice->invoice_no ?: 'invoice');
        $filename = "{$safeNo}.xml";

        return response($xml, 200, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length'      => (string) strlen($xml),
        ]);
    }
}
