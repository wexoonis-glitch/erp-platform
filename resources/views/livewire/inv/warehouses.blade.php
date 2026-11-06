<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Depolar</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Depo</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Depo</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Depo Kodu</label><input type="text" wire:model="code" class="form-input" placeholder="DEP-01">@error('code') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="form-label">Depo Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Merkez Depo">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Depo Adı</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($warehouses as $w)
                    <tr>
                        <td class="font-mono text-xs">{{ $w['code'] }}</td><td class="font-medium">{{ $w['name'] }}</td>
                        <td><span class="badge-success">{{ $w['status'] }}</span></td>
                        <td>
                            @if($w['code'] !== 'MAIN')
                                <button wire:click="delete('{{ $w['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            @else
                                <span class="text-xs text-slate-400">Sistem Deposu</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6">Depo yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
