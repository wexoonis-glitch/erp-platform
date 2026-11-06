<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Satış Siparişleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Sipariş</button>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">Tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach
            </select>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 p-3 text-rose-700 rounded">
            {{ $errors->first() }}
        </div>
    @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-5xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold">Yeni Satış Siparişi</h3>
                    <button wire:click="cancel">X</button>
                </div>

                <div class="modal-body">
                    {{-- Müşteri + Tarih --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Müşteri</label>
                            <select wire:model="customer_id" class="form-select">
                                <option value="">— Seç —</option>
                                @foreach($customers as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tarih</label>
                            <input type="date" wire:model="order_date" class="form-input">
                        </div>
                    </div>

                    {{-- Ürün satırları --}}
                    <div class="mt-4">
                        <button wire:click="addLine" class="btn-ghost-primary mb-2">+ Satır Ekle</button>
                        @foreach($lines as $i => $line)
                            <div class="grid grid-cols-12 gap-2 bg-slate-50 p-2 mb-2 rounded">
                                <div class="col-span-6">
                                    <select wire:model="lines.{{ $i }}.product_id" wire:change="productChanged({{ $i }})" class="form-select">
                                        <option value="">— Ürün —</option>
                                        @foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-span-2"><input type="number" wire:model="lines.{{ $i }}.quantity" placeholder="Miktar" class="form-input"></div>
                                <div class="col-span-2"><input type="number" wire:model="lines.{{ $i }}.unit_price" placeholder="Fiyat" class="form-input"></div>
                                <div class="col-span-2"><button wire:click="removeLine({{ $i }})" class="btn-ghost-danger">Sil</button></div>
                            </div>
                        @endforeach
                    </div>

                    {{-- SƏRFİYYAT (feature flag ile) --}}
                    @if($sarfEnabled)
                        <div class="mt-6 pt-4 border-t border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Masraflar (Sərfiyyat)</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Yol, kargo, yemek, komisyon gibi satış masrafları</p>
                                </div>
                                <button type="button" wire:click="addSarfiyyatLine" class="btn-ghost-primary text-xs">
                                    + Masraf Ekle
                                </button>
                            </div>

                            @if($sarfRequired)
                                <div class="bg-amber-50 border border-amber-200 rounded p-2 mb-3 text-xs text-amber-800">
                                    ⚠ Bu işletmede en az bir masraf girilmesi zorunludur.
                                </div>
                            @endif

                            @if(empty($sarfiyyatLines))
                                <div class="text-xs text-slate-400 py-3 text-center bg-slate-50 rounded">
                                    Henüz masraf eklenmedi. "+ Masraf Ekle" ile başlayın.
                                </div>
                            @else
                                @foreach($sarfiyyatLines as $si => $sline)
                                    <div class="grid grid-cols-12 gap-2 mb-2 items-center">
                                        <div class="col-span-6">
                                            <input type="text"
                                                   wire:model="sarfiyyatLines.{{ $si }}.name"
                                                   placeholder="Masraf adı (örn: Kargo)"
                                                   class="form-input">
                                        </div>
                                        <div class="col-span-3">
                                            <input type="number"
                                                   wire:model="sarfiyyatLines.{{ $si }}.amount"
                                                   placeholder="Tutar"
                                                   step="0.01"
                                                   class="form-input">
                                        </div>
                                        <div class="col-span-3">
                                            <button type="button"
                                                    wire:click="removeSarfiyyatLine({{ $si }})"
                                                    class="btn-ghost-danger text-xs">
                                                Sil
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            {{-- Toplam + Müşteri öder toggle --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 p-3 rounded mt-3">
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox"
                                           wire:click="toggleSarfiyyatChargeCustomer"
                                           @checked($sarfiyyat_charge_customer)
                                           class="w-4 h-4">
                                    <span>Müşteri öder <span class="text-xs text-slate-500">(faturaya eklenir)</span></span>
                                </label>
                                <div class="text-sm">
                                    Toplam Masraf:
                                    <strong class="font-mono text-base ml-1">
                                        {{ number_format($this->sarfiyyatTotal(), 2, ',', '.') }} ₺
                                    </strong>
                                </div>
                            </div>

                            @error('sarfiyyat')
                                <p class="text-xs text-rose-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Kaydet / İptal --}}
                    <div class="modal-footer">
                        <button wire:click="save" class="btn-primary">Kaydet</button>
                        <button wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Liste --}}
    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>SO No</th>
                    <th>Müşteri</th>
                    <th>Tarih</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="font-mono">{{ $o['so_no'] }}</td>
                        <td>{{ $o['customer']['name'] ?? '—' }}</td>
                        <td>{{ $o['order_date'] }}</td>
                        <td class="font-mono">{{ number_format($o['grand_total'], 2) }}</td>
                        <td><span class="badge-muted">{{ $o['status'] }}</span></td>
                        <td>
                            @if($o['status'] === 'draft')
                                <button wire:click="approve('{{ $o['id'] }}')" class="btn-ghost-primary">Onayla</button>
                            @elseif($o['status'] === 'approved')
                                <button wire:click="invoice('{{ $o['id'] }}')" class="btn-ghost-warning">Faturaya Dönüştür</button>
                            @elseif($o['status'] === 'invoiced')
                                <span class="text-xs text-emerald-600">Faturalandı</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Sipariş yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
