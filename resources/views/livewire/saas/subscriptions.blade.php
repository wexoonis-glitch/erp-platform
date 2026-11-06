<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Tenant Abonelikleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Abonelik</button>
    </div>
    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-lg">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Abonelik</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Tenant</label><select wire:model="tenant_id" class="form-select"><option value="">— Seç —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Paket</label><select wire:model="plan_id" class="form-select"><option value="">— Seç —</option>@foreach($plans as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }} ({{ number_format($p['price'], 2) }} ₺)</option>@endforeach</select></div>
                        <div><label class="form-label">Başlangıç</label><input type="date" wire:model="start_date" class="form-input"></div>
                        <div><label class="form-label">Bitiş</label><input type="date" wire:model="end_date" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif
    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Tenant</th><th>Paket</th><th>Başlangıç</th><th>Bitiş</th><th>Durum</th></tr></thead>
            <tbody>
                @forelse($subs as $s)
                    <tr>
                        <td class="font-medium">{{ $s['tenant']['name'] }}</td>
                        <td><span class="badge-primary">{{ $s['plan']['name'] }}</span></td>
                        <td class="text-xs">{{ $s['start_date'] }}</td>
                        <td class="text-xs">{{ $s['end_date'] }}</td>
                        <td><span class="{{ $s['status'] === 'active' ? 'badge-success' : 'badge-danger' }}">{{ $s['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6">Abonelik yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
