<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Muayene Planları</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Plan</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-3xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Muayene Planı</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="form-label">Ürün</label><select wire:model="product_id" class="form-select"><option value="">— Seç —</option>@foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Plan Kodu</label><input type="text" wire:model="code" class="form-input" placeholder="QM-001"></div>
                        <div class="col-span-2"><label class="form-label">Plan Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Örn: Metal Parça Muayenesi"></div>
                    </div>
                    <div class="mt-4">
                        <button wire:click="addCriteria" class="btn-ghost-primary mb-2">+ Kriter Ekle</button>
                        @foreach($criteria as $i => $c)
                            <div class="grid grid-cols-12 gap-2 bg-slate-50 p-2 mb-2 rounded">
                                <div class="col-span-6"><input type="text" wire:model="criteria.{{ $i }}.name" placeholder="Kriter (Örn: Uzunluk)" class="form-input text-xs"></div>
                                <div class="col-span-4"><input type="text" wire:model="criteria.{{ $i }}.expected_value" placeholder="Beklenen Değer" class="form-input text-xs"></div>
                                <div class="col-span-2"><button wire:click="removeCriteria({{ $i }})" class="btn-ghost-danger">Sil</button></div>
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
            <thead><tr><th>Kod</th><th>Plan Adı</th><th>Ürün</th><th>Kriterler</th><th>Durum</th></tr></thead>
            <tbody>
                @forelse($plans as $p)
                    <tr>
                        <td class="font-mono text-xs">{{ $p['code'] }}</td><td class="font-medium">{{ $p['name'] }}</td>
                        <td>{{ $p['product']['name'] }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($p['criteria'] as $c)
                                    <span class="badge-muted text-xs">{{ $c['name'] }}: {{ $c['expected_value'] }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td><span class="badge-success">{{ $p['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6">Plan yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
