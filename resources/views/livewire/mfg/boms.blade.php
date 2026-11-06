<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Reçeteler (BOM)</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Reçete</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-4xl">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Reçete</h3><button wire:click="cancel">X</button></div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2"><label class="form-label">Üretilecek Mamul</label><select wire:model="product_id" class="form-select"><option value="">— Seç —</option>@foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Miktar (Kaç birim için?)</label><input type="number" wire:model="quantity" class="form-input" placeholder="1"></div>
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input" placeholder="BOM-01"></div>
                        <div class="sm:col-span-2"><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input" placeholder="Masa Üretim Reçetesi"></div>
                    </div>
                    <div class="mt-4">
                        <button wire:click="addLine" class="btn-ghost-primary mb-2">+ Hammadde Ekle</button>
                        @foreach($lines as $i => $line)
                            <div class="grid grid-cols-12 gap-2 bg-slate-50 p-2 mb-2 rounded">
                                <div class="col-span-8"><select wire:model="lines.{{ $i }}.component_id" class="form-select"><option value="">— Hammadde —</option>@foreach($products as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach</select></div>
                                <div class="col-span-2"><input type="number" wire:model="lines.{{ $i }}.quantity" placeholder="Miktar" class="form-input"></div>
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
            <thead><tr><th>Kod</th><th>Ad</th><th>Mamul</th><th>Hammaddeler</th></tr></thead>
            <tbody>
                @forelse($boms as $b)
                    <tr>
                        <td class="font-mono">{{ $b['code'] }}</td><td>{{ $b['name'] }}</td><td>{{ $b['product']['name'] }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($b['lines'] as $l)
                                    <span class="badge-muted text-xs">{{ $l['component']['name'] }} ({{ $l['quantity'] }})</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6">Reçete yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
