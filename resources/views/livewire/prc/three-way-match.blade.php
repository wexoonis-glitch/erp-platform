<div class="space-y-6">
    <div>
        <h2 class="page-title">3-Way Match</h2>
        <p class="page-subtitle">Mal kabul (GR) → Tedarikçi Faturası (AP) dönüşümü ve PO fiyat eşleştirmesi</p>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400' }}">
            {{ $message }}
        </div>
    @endif

    @if($showWarnings)
        <div class="modal-backdrop" wire:click.self="closeWarnings">
            <div class="modal-content max-w-2xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-amber-700 dark:text-amber-400">⚠ 3-Way Match Uyarıları</h3>
                    <button wire:click="closeWarnings" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">X</button>
                </div>
                <div class="modal-body">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Fatura <strong>{{ $createdInvoiceNo }}</strong> oluşturuldu ancak aşağıdaki eşleşme uyarıları var.
                        Lütfen kontrol edip gerekirse faturalandırma sonrası düzeltin.
                    </p>
                    <ul class="mt-4 space-y-2 bg-amber-50 dark:bg-amber-950/30 rounded-lg p-4">
                        @foreach($matchWarnings as $w)
                            <li class="text-sm text-amber-800 dark:text-amber-300">• {{ $w }}</li>
                        @endforeach
                    </ul>
                    <div class="modal-footer">
                        <a href="{{ route('fin.ap-invoices') }}" class="btn-primary">Tedarikçi Faturalarına Git</a>
                        <button wire:click="closeWarnings" class="btn-secondary">Kapat</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>GR No</th>
                    <th>PO No</th>
                    <th>Tedarikçi</th>
                    <th>Tarih</th>
                    <th>Toplam Miktar</th>
                    <th>Toplam Maliyet</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $r)
                    <tr wire:key="twm-{{ $r['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $r['gr_no'] }}</td>
                        <td class="font-mono text-xs">{{ $r['po_no'] }}</td>
                        <td class="font-medium">{{ $r['supplier'] }}</td>
                        <td class="text-xs">{{ $r['receipt_date'] }}</td>
                        <td class="font-mono text-xs">{{ number_format($r['total_qty'], 2) }}</td>
                        <td class="font-mono text-xs">{{ number_format($r['total_cost'], 2) }}</td>
                        <td>
                            @if($r['invoiced'])
                                <span class="badge-success">Faturalanmış</span>
                            @else
                                <span class="badge-warning">Fatura Bekliyor</span>
                            @endif
                        </td>
                        <td>
                            @if(!$r['invoiced'])
                                <div class="flex justify-center">
                                    <button wire:click="convert('{{ $r['id'] }}')" wire:confirm="Mal kabulden otomatik AP faturası oluşturulsun mu? 3-way match kontrolü yapılacak." class="btn-ghost-primary">AP Faturasına Dönüştür</button>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12 text-slate-500">Faturaya dönüştürülecek posting yapılmış mal kabul yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
