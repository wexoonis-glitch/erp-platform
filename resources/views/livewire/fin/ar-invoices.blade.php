<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Müşteri Faturaları</h2><p class="page-subtitle">{{ count($invoices) }} fatura</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Fatura</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($errors->any()) <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-sm text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-6xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Yeni Müşteri Faturası</h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Müşteri</label>
                            <select wire:model="customer_id" wire:change="customerChanged" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($customers as $c)<option value="{{ $c['id'] }}">{{ $c['code'] }} — {{ $c['name'] }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="form-label">Fatura No</label><input type="text" wire:model="invoice_no" class="form-input"></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="invoice_date" class="form-input"></div>
                        <div><label class="form-label">Vade</label><input type="date" wire:model="due_date" class="form-input"></div>
                        <div><label class="form-label">Döviz</label><input type="text" wire:model="currency" maxlength="3" class="form-input"></div>
                    </div>

                    <div class="mt-4">
                        <div class="flex justify-between mb-2">
                            <h4 class="text-sm font-semibold">Satırlar</h4>
                            <button wire:click="addLine" class="btn-ghost-primary" type="button">+ Satır Ekle</button>
                        </div>
                        @foreach($lines as $i => $line)
                            <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 mb-2">
                                <div class="col-span-12 sm:col-span-4">
                                    <select wire:model="lines.{{ $i }}.product_id" wire:change="productChanged({{ $i }})" class="form-select text-xs">
                                        <option value="">— Ürün Seç —</option>
                                        @foreach($products as $p) <option value="{{ $p['id'] }}">{{ $p['code'] }} — {{ $p['name'] }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-span-12 sm:col-span-2"><input type="text" wire:model="lines.{{ $i }}.description" placeholder="Açıklama" class="form-input text-xs"></div>
                                <div class="col-span-4 sm:col-span-1">
                                    <input type="number" step="0.01" wire:model="lines.{{ $i }}.quantity" wire:change="quantityChanged({{ $i }})" placeholder="Miktar" class="form-input text-xs">
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <input type="number" step="0.01" wire:model="lines.{{ $i }}.unit_price" wire:change="priceChanged({{ $i }})" placeholder="Fiyat" class="form-input text-xs">
                                </div>
                                <div class="col-span-4 sm:col-span-1"><input type="number" wire:model="lines.{{ $i }}.vat_rate" placeholder="KDV%" class="form-input text-xs"></div>
                                <div class="col-span-12 sm:col-span-2 flex justify-end">
                                    <button wire:click="removeLine({{ $i }})" type="button" class="text-rose-600 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                @if(!empty($line['margin_warning']))
                                    <div class="col-span-12 mt-1 p-2 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-xs text-rose-700 dark:text-rose-400">
                                        ⚠️ {{ $line['margin_warning']['message'] }} — Min: {{ number_format($line['margin_warning']['minimum_price'], 2) }} ·
                                        Nakit marj: %{{ number_format($line['margin_warning']['cash_margin_pct'], 2) }}
                                        @if($line['margin_warning']['below_cost']) · <strong>Maliyetin altında!</strong> @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button wire:click="save(false)" class="btn-secondary">Draft Kaydet</button>
                        <button wire:click="save(true)" class="btn-primary">Kaydet + Post Et</button>
                        <button wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Fatura No</th><th>Müşteri</th><th>Tarih</th><th>Vade</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($invoices as $i)
                    <tr wire:key="ar-{{ $i['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $i['invoice_no'] }}</td>
                        <td>{{ $i['customer'] }}</td>
                        <td class="text-xs">{{ $i['invoice_date'] }}</td>
                        <td class="text-xs">{{ $i['due_date'] }}</td>
                        <td class="font-mono text-xs font-semibold">{{ number_format($i['grand_total'], 2) }} {{ $i['currency'] }}</td>
                        <td><span class="{{ $i['status'] === 'posted' ? 'badge-info' : 'badge-muted' }}">{{ $i['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <a href="{{ route('fin.ar-invoices.pdf', $i['id']) }}" target="_blank" class="btn-ghost-primary">PDF</a>
                                <a href="{{ route('fin.ar-invoices.xml', $i['id']) }}" target="_blank" class="btn-ghost-primary">XML</a>
                                @if($i['status'] === 'draft')
                                    <button wire:click="post('{{ $i['id'] }}')" class="btn-ghost-warning">Post Et</button>
                                    <button wire:click="delete('{{ $i['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger">Sil</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-12">Fatura yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
