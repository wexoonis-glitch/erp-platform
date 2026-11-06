<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Ürünler</h2><p class="page-subtitle">{{ count($products) }} ürün</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Ürün</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-4xl">
                <div class="modal-header"><h3 class="text-base font-semibold">{{ $editingId ? 'Ürün Düzenle' : 'Yeni Ürün' }}</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input">@error('code') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Birim</label><input type="text" wire:model="unit" class="form-input"></div>
                        <div>
                            <label class="form-label">Vergi Rejimi</label>
                            <select wire:model.live="tax_regime_id" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($taxRegimes as $t)
                                    <option value="{{ $t['id'] }}">{{ $t['label'] }}{{ $t['region'] ? ' [' . $t['region'] . ']' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="form-label">Vergi Oranı (%)</label><input type="number" step="0.01" wire:model="vat_rate" class="form-input"></div>
                        <div><label class="form-label">Satış Fiyatı</label><input type="number" step="0.01" wire:model="sales_price" class="form-input"></div>
                        <div><label class="form-label">Alış Fiyatı (FIFO)</label><input type="number" step="0.01" wire:model="purchase_price" class="form-input"></div>
                        <div><label class="form-label">Son Alış (Yenileme)</label><input type="number" step="0.01" wire:model="replacement_cost" class="form-input"></div>
                        <div><label class="form-label">Birim Genel Gider</label><input type="number" step="0.01" wire:model="overhead_per_unit" class="form-input"></div>
                        <div><label class="form-label">Hedef Marj (%)</label><input type="number" step="0.01" wire:model="target_margin_pct" class="form-input"></div>
                        <div><label class="form-label">Min Marj (%)</label><input type="number" step="0.01" wire:model="min_margin_pct" class="form-input"></div>
                        <div>
                            <label class="form-label">Maliyet Yöntemi</label>
                            <select wire:model="cost_method" class="form-select">
                                <option value="standard">Standart</option>
                                <option value="fifo">FIFO</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2"><label class="form-label">Gelir Hesabı</label><select wire:model="revenue_account_id" class="form-select"><option value="">— Seçiniz —</option>@foreach($revenueAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>@endforeach</select></div>
                        <div class="sm:col-span-2 flex items-center gap-2"><input type="checkbox" wire:model.live="track_stock" id="ts" class="rounded"><label for="ts" class="text-sm">Stok Takibi</label></div>
                        @if($track_stock)
                            <div><label class="form-label">Stok Hesabı</label><select wire:model="inventory_account_id" class="form-select"><option value="">— Seçiniz —</option>@foreach($assetAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>@endforeach</select></div>
                            <div><label class="form-label">Maliyet Hesabı</label><select wire:model="cogs_account_id" class="form-select"><option value="">— Seçiniz —</option>@foreach($expenseAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['code'] }} — {{ $a['name'] }}</option>@endforeach</select></div>
                            @if(!$editingId)
                                <div class="sm:col-span-2"><label class="form-label">Açılış Miktarı</label><input type="number" wire:model="opening_qty" class="form-input" placeholder="0"></div>
                            @endif
                        @endif
                        <div><label class="form-label">Durum</label><select wire:model="status" class="form-select"><option value="active">Aktif</option><option value="inactive">Pasif</option></select></div>
                    </div>

                    <x-custom-fields-inputs entity="inv_product" model="customFields" />

                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Maliyet Yönt.</th><th>Son Alış</th><th>Satış</th><th>Min Fiyat</th><th>Önerilen</th><th>Stok</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($products as $p)
                    @php
                        $minP = $minimumById[$p['id']] ?? 0;
                        $sugP = $suggestedById[$p['id']] ?? 0;
                        $salesP = (float) $p['sales_price'];
                        $belowMin = $salesP > 0 && $salesP < $minP - 0.0001;
                    @endphp
                    <tr wire:key="p-{{ $p['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $p['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $p['name'] }}</td>
                        <td class="text-xs">
                            @if(($p['cost_method'] ?? 'standard') === 'fifo')
                                <span class="badge-info">FIFO</span>
                            @else
                                <span class="badge-muted">Std</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs">{{ number_format((float) ($p['replacement_cost'] ?? 0), 2) }}</td>
                        <td class="font-mono text-xs font-bold {{ $belowMin ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">
                            {{ number_format($salesP, 2) }}
                            @if($belowMin)<div class="text-[9px] font-normal">⚠ min altı</div>@endif
                        </td>
                        <td class="font-mono text-xs text-amber-600">{{ number_format($minP, 2) }}</td>
                        <td class="font-mono text-xs text-emerald-600">{{ number_format($sugP, 2) }}</td>
                        <td>{!! $p['track_stock'] ? '<span class="badge-success">Evet</span>' : '<span class="badge-muted">Hayır</span>' !!}</td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="openEdit('{{ $p['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                <button wire:click="delete('{{ $p['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-12 text-slate-500">Ürün yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
