<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Masraf Merkezleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Merkez</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Masraf Merkezi</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input" placeholder="CC-001"></div>
                        <div><label class="form-label">Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Pazarlama Departmanı"></div>
                        <div><label class="form-label">Yönetici (Opsiyonel)</label><input type="text" wire:model="manager_name" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Adı</th><th>Yönetici</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($centers as $c)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $c['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $c['name'] }}</td>
                        <td class="text-sm">{{ $c['manager_name'] ?? '—' }}</td>
                        <td><span class="badge-success">{{ $c['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="delete('{{ $c['id'] }}')" wire:confirm="Silmek istediğinize emin misiniz?" class="btn-ghost-danger">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6">Masraf merkezi yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
