<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Tedarikçi Faturaları</h2>
            <p class="page-subtitle">{{ count($invoices) }} fatura</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Fatura</button>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">Tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $errors->first() }}
        </div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-5xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Yeni Tedarikçi Faturası</h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Tedarikçi</label>
                            <select wire:model="supplier_id" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['code'] }} — {{ $s['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Fatura No</label>
                            <input type="text" wire:model="invoice_no" placeholder="FTR-001" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Fatura Tarihi</label>
                            <input type="date" wire:model="invoice_date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Vade Tarihi</label>
                            <input type="date" wire:model="due_date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Para Birimi</label>
                            <input type="text" wire:model="currency" maxlength="3" class="form-input">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Satırlar</h4>
                            <button wire:click="addLine" class="btn-ghost-primary" type="button">+ Satır Ekle</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($lines as $i => $line)
                                <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2">
                                    <div class="col-span-12 sm:col-span-3">
                                        <select wire:model="lines.{{ $i }}.expense_account_id" class="form-select text-xs">
                                            <option value="">— Hesap —</option>
                                            @foreach($expenseAccounts as $a)
                                                <option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-12 sm:col-span-3">
                                        <input type="text" wire:model="lines.{{ $i }}.description" placeholder="Açıklama" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-4 sm:col-span-1">
                                        <input type="number" step="0.01" wire:model="lines.{{ $i }}.quantity" placeholder="Miktar" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-4 sm:col-span-2">
                                        <input type="number" step="0.01" wire:model="lines.{{ $i }}.unit_price" placeholder="Birim Fiyat" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-4 sm:col-span-1">
                                        <input type="number" step="0.01" wire:model="lines.{{ $i }}.vat_rate" placeholder="KDV%" class="form-input text-xs">
                                    </div>
                                    <div class="col-span-2 sm:col-span-2 flex justify-end pt-1">
                                        @if(count($lines) > 1)
                                            <button wire:click="removeLine({{ $i }})" type="button" class="text-rose-600 dark:text-rose-400 hover:text-rose-800 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label">Notlar</label>
                        <textarea wire:model="notes" rows="2" class="form-input"></textarea>
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
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tedarikçi</th>
                    <th>Tarih</th>
                    <th>Vade</th>
                    <th>Tutar</th>
                    <th>Ödenen</th>
                    <th>Durum</th>
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $i)
                    @php
                        $badge = match($i['status']) {
                            'paid'           => 'badge-success',
                            'partially_paid' => 'badge-warning',
                            'posted'         => 'badge-info',
                            'cancelled'      => 'badge-muted',
                            default          => 'badge-muted',
                        };
                    @endphp
                    <tr wire:key="ap-{{ $i['id'] }}">
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $i['invoice_no'] }}</td>
                        <td class="text-sm">{{ $i['supplier'] }}</td>
                        <td class="text-xs">{{ $i['invoice_date'] }}</td>
                        <td class="text-xs">{{ $i['due_date'] }}</td>
                        <td class="text-xs font-mono">{{ number_format($i['grand_total'], 2) }} {{ $i['currency'] }}</td>
                        <td class="text-xs font-mono">{{ number_format($i['paid_total'], 2) }}</td>
                        <td><span class="{{ $badge }}">{{ $i['status'] }}</span></td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if($i['status'] === 'draft')
                                    <button wire:click="post('{{ $i['id'] }}')" class="btn-ghost-warning">Post Et</button>
                                    <button wire:click="delete('{{ $i['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12">
                        <div class="text-4xl mb-2">📄</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            @if($isSuper && !$selectedTenantId) Yukarıdan tenant seçin. @else Henüz fatura yok. @endif
                        </p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
