<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Kargo Firmaları</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Kargo</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Kargo Firması</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Kod</label><input type="text" wire:model="code" class="form-input" placeholder="YURTICI"></div>
                        <div><label class="form-label">Firma Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Yurtiçi Kargo"></div>
                        <div><label class="form-label">Takip URL (Opsiyonel)</label><input type="text" wire:model="tracking_url" class="form-input" placeholder="https://..."></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Firma Adı</th><th>Takip URL</th><th>Durum</th></tr></thead>
            <tbody>
                @forelse($carriers as $c)
                    <tr>
                        <td class="font-mono text-xs">{{ $c['code'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $c['name'] }}</td>
                        <td class="text-xs">{{ $c['tracking_url'] ?? '—' }}</td>
                        <td><span class="badge-success">{{ $c['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6">Kargo firması yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
