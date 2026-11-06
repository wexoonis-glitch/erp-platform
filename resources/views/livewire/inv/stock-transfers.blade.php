<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Depo Transferleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Transfer</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Depo Transferi</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="form-label">Çıkış Deposu</label><select wire:model="from_warehouse_id" class="form-select"><option value="">— Seç —</option>@foreach($warehouses as $w)<option value="{{ $w['id'] }}">{{ $w['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Giriş Deposu</label><select wire:model="to_warehouse_id" class="form-select"><option value="">— Seç —</option>@foreach($warehouses as $w)<option value="{{ $w['id'] }}">{{ $w['name'] }}</option>@endforeach</select></div>
                        <div class="col-span-2"><label class="form-label">Ürün</label><select wire:model="product_id" class="form-select"><option value="">— Seç —</option>@foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Miktar</label><input type="number" wire:model="quantity" class="form-input"></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="transfer_date" class="form-input"></div>
                        <div class="col-span-2"><label class="form-label">Notlar</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Transferi Tamamla</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Transfer No</th><th>Tarih</th><th>Nereden</th><th>Nereye</th><th>Ürün / Miktar</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($transfers as $t)
                    <tr>
                        <td class="font-mono text-xs">{{ $t['transfer_no'] }}</td>
                        <td class="text-xs">{{ $t['transfer_date'] }}</td>
                        <td class="text-sm">{{ $t['from_warehouse']['name'] }}</td>
                        <td class="text-sm">{{ $t['to_warehouse']['name'] }}</td>
                        <td>
                            @foreach($t['lines'] as $l)
                                <div class="text-xs">{{ $l['product']['name'] }} ({{ number_format($l['quantity'], 2) }})</div>
                            @endforeach
                        </td>
                        <td><span class="badge-success">{{ $t['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6">Transfer yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
