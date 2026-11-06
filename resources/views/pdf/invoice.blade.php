<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fatura {{ $invoice->invoice_no }}</title>
    <style>
        @page { margin: 30px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 0; }

        .header { width: 100%; margin-bottom: 18px; border-bottom: 2px solid #4f46e5; padding-bottom: 14px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .header .left { width: 60%; }
        .header .right { width: 40%; text-align: right; }

        .logo { max-height: 60px; max-width: 180px; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: bold; color: #4f46e5; margin-bottom: 5px; }
        .company-info { font-size: 10px; color: #64748b; line-height: 1.5; }

        .doc-title { font-size: 24px; font-weight: bold; color: #4f46e5; letter-spacing: 2px; margin-bottom: 8px; }
        .doc-info { font-size: 11px; line-height: 1.8; color: #1e293b; }
        .doc-info strong { color: #1e293b; }

        .customer-box { background: #f8fafc; border-left: 3px solid #4f46e5; padding: 12px; margin-bottom: 18px; }
        .customer-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .customer-name { font-size: 13px; font-weight: bold; color: #1e293b; margin-bottom: 4px; }
        .customer-info { font-size: 10px; color: #475569; line-height: 1.5; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items thead { background: #4f46e5; color: #ffffff; }
        table.items thead th { padding: 8px 6px; text-align: left; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items thead th.right { text-align: right; }
        table.items tbody td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; vertical-align: top; }
        table.items tbody td.right { text-align: right; }
        table.items tbody tr.alt { background: #f8fafc; }
        .line-desc { font-weight: bold; color: #1e293b; }
        .line-code { font-size: 9px; color: #64748b; }

        .totals { width: 100%; margin-top: 10px; }
        .totals table.outer { width: 100%; border-collapse: collapse; }
        .totals table.outer td { vertical-align: top; padding: 0; }
        .totals .left-col { width: 55%; padding-right: 20px; }
        .totals .right-col { width: 45%; }

        .notes-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; font-size: 10px; color: #475569; margin-top: 4px; }
        .notes-box strong { color: #1e293b; display: block; margin-bottom: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

        table.totals-table { width: 100%; border-collapse: collapse; }
        table.totals-table td { padding: 6px 8px; font-size: 11px; }
        table.totals-table td.label { text-align: right; color: #64748b; }
        table.totals-table td.value { text-align: right; font-weight: bold; width: 45%; }
        table.totals-table tr.grand { background: #4f46e5; color: #ffffff; }
        table.totals-table tr.grand td { font-size: 13px; padding: 10px 8px; }
        table.totals-table tr.grand td.label { color: #ffffff; }

        .footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <table>
            <tr>
                <td class="left">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                    <div class="company-name">{{ $profile->legal_name ?? 'Şirket Adı' }}</div>
                    <div class="company-info">
                        @if($profile && $profile->tax_office && $profile->tax_no)
                            {{ $profile->tax_office }} V.D. — {{ $profile->tax_no }}<br>
                        @endif
                        @if($profile && $profile->address)
                            {{ $profile->address }}<br>
                        @endif
                        @if($profile)
                            {{ $profile->city }} {{ $profile->postal_code }} {{ $profile->country }}<br>
                            @if($profile->phone || $profile->email)
                                {{ $profile->phone }}@if($profile->phone && $profile->email) &middot; @endif{{ $profile->email }}
                            @endif
                        @endif
                    </div>
                </td>
                <td class="right">
                    <div class="doc-title">FATURA</div>
                    <div class="doc-info">
                        <strong>No:</strong> {{ $invoice->invoice_no }}<br>
                        <strong>Tarih:</strong> {{ $invoice->invoice_date->format('d.m.Y') }}<br>
                        <strong>Vade:</strong> {{ $invoice->due_date->format('d.m.Y') }}<br>
                        <strong>Para Birimi:</strong> {{ $invoice->currency }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- CUSTOMER --}}
    <div class="customer-box">
        <div class="customer-label">Sayın</div>
        <div class="customer-name">{{ $invoice->customer?->name ?? '—' }}</div>
        <div class="customer-info">
            @if($invoice->customer && $invoice->customer->tax_office && $invoice->customer->tax_no)
                {{ $invoice->customer->tax_office }} V.D. — {{ $invoice->customer->tax_no }}<br>
            @endif
            @if($invoice->customer && $invoice->customer->address)
                {{ $invoice->customer->address }}<br>
            @endif
            @if($invoice->customer && $invoice->customer->email)
                {{ $invoice->customer->email }}
            @endif
        </div>
    </div>

    {{-- ITEMS --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>Açıklama</th>
                <th class="right" style="width: 60px;">Miktar</th>
                <th class="right" style="width: 80px;">Birim Fiyat</th>
                <th class="right" style="width: 45px;">KDV</th>
                <th class="right" style="width: 90px;">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $i => $line)
                <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                    <td>{{ $line->line_no }}</td>
                    <td>
                        <div class="line-desc">{{ $line->description }}</div>
                        @if($line->product)
                            <div class="line-code">{{ $line->product->code }}</div>
                        @endif
                    </td>
                    <td class="right">{{ number_format((float) $line->quantity, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $line->unit_price, 2, ',', '.') }}</td>
                    <td class="right">%{{ number_format((float) $line->vat_rate, 0, ',', '.') }}</td>
                    <td class="right"><strong>{{ number_format((float) $line->total, 2, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="totals">
        <table class="outer">
            <tr>
                <td class="left-col">
                    @if($invoice->notes)
                        <div class="notes-box">
                            <strong>Notlar</strong>
                            {{ $invoice->notes }}
                        </div>
                    @endif
                </td>
                <td class="right-col">
                    <table class="totals-table">
                        <tr>
                            <td class="label">Ara Toplam</td>
                            <td class="value">{{ number_format((float) $invoice->subtotal, 2, ',', '.') }} {{ $invoice->currency }}</td>
                        </tr>
                        <tr>
                            <td class="label">KDV Toplam</td>
                            <td class="value">{{ number_format((float) $invoice->vat_total, 2, ',', '.') }} {{ $invoice->currency }}</td>
                        </tr>
                        <tr class="grand">
                            <td class="label">GENEL TOPLAM</td>
                            <td class="value">{{ number_format((float) $invoice->grand_total, 2, ',', '.') }} {{ $invoice->currency }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Bu fatura {{ $profile->legal_name ?? 'şirketimiz' }} tarafından elektronik ortamda oluşturulmuştur.<br>
        Ödeme vadesi: {{ $invoice->due_date->format('d.m.Y') }} &middot; Düzenleme tarihi: {{ $invoice->invoice_date->format('d.m.Y') }}
    </div>

</body>
</html>
