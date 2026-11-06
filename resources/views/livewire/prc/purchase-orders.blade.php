<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Satın Alma Siparişleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Sipariş</button>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($message)
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 rounded-xl p-3 text-sm text-emerald-700 dark:text-emerald-400">{{ $message }}</div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-5xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Sipariş</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2"><label class="form-label">Tedarikçi</label><select wire:model="supplier_id" class="form-select"><option value="">— Seç —</option>@foreach($suppliers as $s)<option value="{{ $s['id'] }}">{{ $s['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="order_date" class="form-input"></div>
                        <div><label class="form-label">Beklenen Tarih</label><input type="date" wire:model="expected_date" class="form-input"></div>
                    </div>
                    <div class="mt-4">
                        <button wire:click="addLine" class="btn-ghost-primary mb-2">+ Satır Ekle</button>
                        @foreach($lines as $i => $line)
                            <div class="grid grid-cols-12 gap-2 bg-slate-50 p-2 mb-2 rounded">
                                <div class="col-span-6"><select wire:model="lines.{{ $i }}.product_id" wire:change="productChanged({{ $i }})" class="form-select"><option value="">— Ürün —</option>@foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach</select></div>
                                <div class="col-span-2"><input type="number" wire:model="lines.{{ $i }}.quantity" placeholder="Miktar" class="form-input"></div>
                                <div class="col-span-2"><input type="number" wire:model="lines.{{ $i }}.unit_price" placeholder="Fiyat" class="form-input"></div>
                                <div class="col-span-2"><button wire:click="removeLine({{ $i }})" class="btn-ghost-danger">Sil</button></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer"><button wire:click="save" class="btn-primary">Kaydet</button><button wire:click="cancel" class="btn-secondary">İptal</button></div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>PO No</th><th>Tedarikçi</th><th>Tarih</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($orders as $o)
                    @php $isPending = in_array($o['id'], $pendingPoIds, true); @endphp
                    <tr wire:key="po-{{ $o['id'] }}">
                        <td class="font-mono">{{ $o['po_no'] }}</td>
                        <td>{{ $o['supplier']['name'] ?? '—' }}</td>
                        <td>{{ $o['order_date'] }}</td>
                        <td class="font-mono">{{ number_format($o['grand_total'], 2) }}</td>
                        <td>
                            @if($isPending)
                                <span class="badge-warning">Onay Bekliyor</span>
                            @else
                                <span class="badge-muted">{{ $o['status'] }}</span>
                            @endif
                        </td>
                        <td>
                            @if($o['status'] === 'draft' && !$isPending)
                                <button wire:click="approve('{{ $o['id'] }}')" class="btn-ghost-primary">Onaya Sun</button>
                            @elseif($isPending)
                                <a href="{{ route('approvals.inbox') }}" class="btn-ghost-warning text-xs">Onay Kutusu</a>
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
