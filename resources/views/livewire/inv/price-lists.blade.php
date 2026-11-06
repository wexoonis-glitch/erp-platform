<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Fiyat Listeleri</h2><p class="page-subtitle">{{ count($lists) }} liste</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Fiyat Listesi</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-5xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Fiyat Listesi</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input" placeholder="VIP"></div>
                        <div class="sm:col-span-2"><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input" placeholder="VIP Müşteri Fiyatları"></div>
                        <div><label class="form-label">Para Birimi</label><input type="text" wire:model="currency" maxlength="3" class="form-input"></div>
                        <div><label class="form-label">Geçerlilik Başlangıç</label><input type="date" wire:model="valid_from" class="form-input"></div>
                        <div><label class="form-label">Geçerlilik Bitiş</label><input type="date" wire:model="valid_to" class="form-input"></div>
                        <div><label class="form-label">Öncelik (düşük = önce)</label><input type="number" wire:model="priority" class="form-input"></div>
                        <div class="sm:col-span-2 flex items-center gap-2 pt-7">
                            <input type="checkbox" wire:model="is_default" id="isdef" class="rounded">
                            <label for="isdef" class="text-sm">Varsayılan fiyat listesi (müşteriye atanmamışsa kullanılır)</label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex justify-between mb-2">
                            <h4 class="text-sm font-semibold">Ürün Fiyatları</h4>
                            <button type="button" wire:click="addItem" class="btn-ghost-primary">+ Satır</button>
                        </div>
                        @foreach($items as $i => $item)
                            <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 mb-2">
                                <div class="col-span-12 sm:col-span-5">
                                    <select wire:model="items.{{ $i }}.product_id" class="form-select text-xs">
                                        <option value="">— Ürün —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p['id'] }}">{{ $p['code'] }} — {{ $p['name'] }} (Liste: {{ number_format($p['sales_price'], 2) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="text-[10px] text-slate-500">Birim Fiyat</label>
                                    <input type="number" step="0.01" wire:model="items.{{ $i }}.unit_price" class="form-input text-xs">
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="text-[10px] text-slate-500">Min. Miktar</label>
                                    <input type="number" step="0.01" wire:model="items.{{ $i }}.min_quantity" class="form-input text-xs">
                                </div>
                                <div class="col-span-3 sm:col-span-2">
                                    <label class="text-[10px] text-slate-500">İskonto %</label>
                                    <input type="number" step="0.01" wire:model="items.{{ $i }}.discount_rate" class="form-input text-xs">
                                </div>
                                <div class="col-span-1 flex justify-end pt-4">
                                    <button type="button" wire:click="removeItem({{ $i }})" class="text-rose-600 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">Kaydet</button>
                        <button type="button" wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Döviz</th><th>Geçerlilik</th><th>Ürün Sayısı</th><th>Varsayılan</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($lists as $l)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $l['code'] }}</td>
                        <td class="font-medium">{{ $l['name'] }}</td>
                        <td class="font-mono">{{ $l['currency'] }}</td>
                        <td class="text-xs">{{ $l['valid_from'] ?? '—' }} / {{ $l['valid_to'] ?? '—' }}</td>
                        <td class="text-xs">{{ $l['items_count'] }} kalem</td>
                        <td>{!! $l['is_default'] ? '<span class="badge-success">Evet</span>' : '<span class="badge-muted">Hayır</span>' !!}</td>
                        <td><span class="badge-success">{{ $l['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="delete('{{ $l['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6">Fiyat listesi yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
