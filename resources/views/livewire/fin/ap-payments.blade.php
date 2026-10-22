<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Tedarikçi Ödemeleri</h2>
            <p class="page-subtitle">{{ count($payments) }} ödeme</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Ödeme</button>
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
            <div class="modal-content max-w-4xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Yeni Ödeme</h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Tedarikçi</label>
                            <select wire:model.live="supplier_id" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['code'] }} — {{ $s['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Ödeme Tarihi</label>
                            <input type="date" wire:model="payment_date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Ödeme Hesabı</label>
                            <select wire:model="bank_account_id" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($bankAccounts as $a)
                                    <option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tutar</label>
                            <input type="number" step="0.01" wire:model.live="amount" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Para Birimi</label>
                            <input type="text" wire:model="currency" maxlength="3" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Yöntem</label>
                            <select wire:model="method" class="form-select">
                                <option value="bank">Banka</option>
                                <option value="cash">Nakit</option>
                                <option value="check">Çek</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Referans (banka/slip)</label>
                            <input type="text" wire:model="reference" class="form-input">
                        </div>
                    </div>

                    @if($supplier_id && count($openInvoices) > 0)
                        <div class="mt-4">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Açık Faturalar</h4>
                            <div class="space-y-2">
                                @foreach($openInvoices as $i => $inv)
                                    <div class="grid grid-cols-12 gap-2 items-center bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 text-xs">
                                        <div class="col-span-3 font-mono font-semibold">{{ $inv['invoice_no'] }}</div>
                                        <div class="col-span-2">{{ $inv['invoice_date'] }}</div>
                                        <div class="col-span-2">Vade: {{ $inv['due_date'] }}</div>
                                        <div class="col-span-2 text-right">Toplam: {{ number_format($inv['grand_total'], 2) }}</div>
                                        <div class="col-span-2 text-right">Kalan: {{ number_format($inv['remaining'], 2) }}</div>
                                        <div class="col-span-1">
                                            <input type="number" step="0.01" wire:model="allocations.{{ $i }}.amount" placeholder="0" class="form-input text-xs">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Dağıtım toplamı, ödeme tutarına eşit olmalı.
                            </p>
                        </div>
                    @elseif($supplier_id)
                        <div class="mt-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 rounded-lg p-3 text-xs text-amber-700 dark:text-amber-400">
                            Bu tedarikçinin açık faturası yok.
                        </div>
                    @endif

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
                    <th>Ödeme No</th>
                    <th>Tedarikçi</th>
                    <th>Tarih</th>
                    <th>Tutar</th>
                    <th>Yöntem</th>
                    <th>Referans</th>
                    <th>Durum</th>
                    <th class="text-center">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    @php
                        $badge = match($p['status']) {
                            'posted' => 'badge-success',
                            'cancelled' => 'badge-muted',
                            default => 'badge-warning',
                        };
                    @endphp
                    <tr wire:key="pay-{{ $p['id'] }}">
                        <td class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $p['payment_no'] }}</td>
                        <td class="text-sm">{{ $p['supplier'] }}</td>
                        <td class="text-xs">{{ $p['payment_date'] }}</td>
                        <td class="text-xs font-mono font-semibold">{{ number_format($p['amount'], 2) }} {{ $p['currency'] }}</td>
                        <td class="text-xs">{{ $p['method'] }}</td>
                        <td class="text-xs text-slate-500">{{ $p['reference'] ?? '—' }}</td>
                        <td><span class="{{ $badge }}">{{ $p['status'] }}</span></td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if($p['status'] === 'draft')
                                    <button wire:click="post('{{ $p['id'] }}')" class="btn-ghost-warning">Post Et</button>
                                    <button wire:click="delete('{{ $p['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12">
                        <div class="text-4xl mb-2">💳</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            @if($isSuper && !$selectedTenantId) Yukarıdan tenant seçin. @else Henüz ödeme yok. @endif
                        </p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
