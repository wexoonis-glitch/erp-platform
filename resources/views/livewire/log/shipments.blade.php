<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Sevkiyatlar</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Sevkiyat</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Sevkiyat</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2"><label class="form-label">Sipariş (PO/SO)</label><select wire:model="sales_order_id" class="form-select"><option value="">— Seç —</option>@foreach($orders as $o)<option value="{{ $o['id'] }}">{{ $o['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Kargo Firması</label><select wire:model="carrier_id" class="form-select"><option value="">— Seç —</option>@foreach($carriers as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Sevk Tarihi</label><input type="date" wire:model="shipment_date" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Takip No</label><input type="text" wire:model="tracking_no" class="form-input" placeholder="Kargo takip numarası"></div>
                        <div class="col-span-2"><label class="form-label">Notlar</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Sevk Et</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Sevk No</th><th>Sipariş</th><th>Kargo</th><th>Takip No</th><th>Sevk Tarihi</th><th>Durum</th></tr></thead>
            <tbody>
                @forelse($shipments as $s)
                    <tr>
                        <td class="font-mono text-xs">{{ $s['shipment_no'] }}</td>
                        <td class="text-sm">{{ $s['sales_order']['so_no'] }}</td>
                        <td class="text-sm">{{ $s['carrier']['name'] }}</td>
                        <td class="font-mono text-xs text-primary-600">{{ $s['tracking_no'] ?? '—' }}</td>
                        <td class="text-xs">{{ $s['shipment_date'] }}</td>
                        <td><span class="badge-success">{{ $s['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Sevkiyat yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
