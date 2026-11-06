<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Varlık Yönetimi</h2><p class="page-subtitle">{{ count($assets) }} varlık</p></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Varlık</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-2xl">
                <div class="modal-header"><h3 class="text-base font-semibold">{{ $editingId ? 'Varlık Düzenle' : 'Yeni Varlık' }}</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input">@error('code') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Ad</label><input type="text" wire:model="name" class="form-input">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Kategori</label><select wire:model="category" class="form-select"><option value="Makine">Makine</option><option value="Araç">Araç</option><option value="Bina">Bina</option><option value="BT">BT Ekipmanı</option></select></div>
                        <div><label class="form-label">Seri No</label><input type="text" wire:model="serial_no" class="form-input"></div>
                        <div><label class="form-label">Alım Tarihi</label><input type="date" wire:model="purchase_date" class="form-input"></div>
                        <div><label class="form-label">Alım Maliyeti</label><input type="number" wire:model="purchase_cost" class="form-input"></div>
                        <div><label class="form-label">Durum</label><select wire:model="status" class="form-select"><option value="active">Aktif</option><option value="maintenance">Bakımda</option><option value="broken">Arızalı</option><option value="retired">Hurda</option></select></div>
                        <div class="sm:col-span-2"><label class="form-label">Notlar</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Kategori</th><th>Alım Tarihi</th><th>Maliyet</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($assets as $a)
                    <tr>
                        <td class="font-mono text-xs">{{ $a['code'] }}</td><td class="font-medium">{{ $a['name'] }}</td><td>{{ $a['category'] }}</td>
                        <td class="text-xs">{{ $a['purchase_date'] ?? '—' }}</td><td class="font-mono">{{ number_format($a['purchase_cost'], 2) }}</td>
                        <td><span class="{{ $a['status'] === 'active' ? 'badge-success' : ($a['status'] === 'broken' ? 'badge-danger' : 'badge-warning') }}">{{ $a['status'] }}</span></td>
                        <td><div class="flex justify-center gap-1"><button wire:click="openEdit('{{ $a['id'] }}')" class="btn-ghost-primary">Düzenle</button><button wire:click="delete('{{ $a['id'] }}')" class="btn-ghost-danger">Sil</button></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6">Varlık yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
