<?php
namespace App\Http\Controllers;

use App\Models\FinArInvoice;
use App\Models\SysCompanyProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfController extends Controller {
    public function show(string $id) {
        $invoice = FinArInvoice::with(['customer', 'lines.product'])->findOrFail($id);

        $profile = SysCompanyProfile::where('tenant_id', $invoice->tenant_id)->first();

        $logoBase64 = null;
        if ($profile && $profile->logo_path && Storage::disk('local')->exists($profile->logo_path)) {
            $contents = Storage::disk('local')->get($profile->logo_path);
            $mime = Storage::disk('local')->mimeType($profile->logo_path) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($contents);
        }

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'    => $invoice,
            'profile'    => $profile,
            'logoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'defaultFont'          => 'DejaVu Sans',
        ]);

        $safeNo = preg_replace('/[^A-Za-z0-9\-]/', '', $invoice->invoice_no ?: 'invoice');
        return $pdf->download("fatura-{$safeNo}.pdf");
    }
}
