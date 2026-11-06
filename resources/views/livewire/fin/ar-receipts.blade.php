<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Müşteri Tahsilatları</h2><p class="page-subtitle">{{ count($receipts) }} tahsilat</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Tahsilat</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-4xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Tahsilat</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2"><label class="form-label">Müşteri</label><select wire:model.live="customer_id" class="form-select"><option value="">— Seç —</option>@foreach($customers as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="receipt_date" class="form-input"></div>
                        <div><label class="form-label">Kasa/Banka</label><select wire:model="bank_account_id" class="form-select"><option value="">— Seç —</option>@foreach($bankAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Tutar</label><input type="number" step="0.01" wire:model.live="amount" class="form-input"></div>
                        <div><label class="form-label">Döviz</label><input type="text" wire:model="currency" maxlength="3" class="form-input"></div>
                    </div>

                    @if($customer_id && count($openInvoices) > 0)
                        <div class="mt-4">
                            <h4 class="text-sm font-semibold mb-2">Açık Faturalar</h4>
                            @foreach($openInvoices as $i => $inv)
                                <div class="grid grid-cols-12 gap-2 bg-slate-50 p-2 mb-2 rounded items-center text-xs">
                                    <div class="col-span-4 font-mono font-semibold">{{ $inv['invoice_no'] }}</div>
                                    <div class="col-span-4 text-right">Kalan: {{ number_format($inv['remaining'], 2) }}</div>
                                    <div class="col-span-4"><input type="number" wire:model="allocations.{{ $i }}.amount" placeholder="Tahsilat Tutarı" class="form-input text-xs"></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="modal-footer"><button wire:click="save(false)" class="btn-secondary">Draft Kaydet</button><button wire:click="save(true)" class="btn-primary">Kaydet + Post Et</button><button wire:click="cancel" class="btn-secondary">İptal</button></div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Tahsilat No</th><th>Müşteri</th><th>Tarih</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($receipts as $r)
                    <tr wire:key="ar-r-{{ $r['id'] }}">
                        <td class="font-mono">{{ $r['receipt_no'] }}</td><td>{{ $r['customer'] }}</td><td>{{ $r['receipt_date'] }}</td>
                        <td class="font-mono font-semibold">{{ number_format($r['amount'], 2) }} {{ $r['currency'] }}</td>
                        <td><span class="{{ $r['status'] === 'posted' ? 'badge-success' : 'badge-muted' }}">{{ $r['status'] }}</span></td>
                        <td>
                            @if($r['status'] === 'draft')
                                <div class="flex justify-center gap-1"><button wire:click="post('{{ $r['id'] }}')" class="btn-ghost-warning">Post Et</button><button wire:click="delete('{{ $r['id'] }}')" class="btn-ghost-danger">Sil</button></div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Kayıt yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
