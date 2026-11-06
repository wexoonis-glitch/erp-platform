<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Mal Kabul (Goods Receipt)</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Mal Kabul</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-4xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Mal Kabul</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="form-label">Sipariş (PO)</label><select wire:model.live="po_id" class="form-select"><option value="">— Seç —</option>@foreach($pendingPos as $po)<option value="{{ $po['id'] }}">{{ $po['po_no'] }} ({{ $po['supplier'] }})</option>@endforeach</select></div>
                        <div><label class="form-label">Kabul Tarihi</label><input type="date" wire:model="receipt_date" class="form-input"></div>
                    </div>
                    @if(count($lines) > 0)
                        <div class="mt-4">
                            <h4 class="text-sm font-semibold mb-2">Bekleyen Ürünler</h4>
                            @foreach($lines as $i => $l)
                                <div class="grid grid-cols-12 gap-2 bg-slate-50 p-2 mb-2 rounded items-center">
                                    <div class="col-span-6 text-sm">{{ $l['name'] }}</div>
                                    <div class="col-span-3"><input type="number" wire:model="lines.{{ $i }}.quantity" placeholder="Miktar" class="form-input text-xs"></div>
                                    <div class="col-span-3"><input type="number" wire:model="lines.{{ $i }}.unit_cost" placeholder="Birim Maliyet" class="form-input text-xs"></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="modal-footer"><button wire:click="save" class="btn-primary">Kabul Et (Stoğa Ekle)</button><button wire:click="cancel" class="btn-secondary">İptal</button></div>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>GR No</th><th>PO No</th><th>Tedarikçi</th><th>Tarih</th></tr></thead>
            <tbody>
                @forelse($receipts as $r)
                    <tr>
                        <td class="font-mono">{{ $r['gr_no'] }}</td><td>{{ $r['purchase_order']['po_no'] }}</td><td>{{ $r['purchase_order']['supplier']['name'] }}</td><td>{{ $r['receipt_date'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6">Kayıt yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
