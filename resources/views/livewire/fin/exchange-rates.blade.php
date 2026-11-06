<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Döviz Kurları</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Kur</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Kur Girişi</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Para Birimi</label><select wire:model="currency_id" class="form-select"><option value="">— Seç —</option>@foreach($currencies as $c)<option value="{{ $c['id'] }}">{{ $c['code'] }} - {{ $c['name'] }}</option>@endforeach</select></div>
                        <div><label class="form-label">Tarih</label><input type="date" wire:model="rate_date" class="form-input"></div>
                        <div><label class="form-label">Kur (1 Döviz = ? TRY)</label><input type="number" step="0.000001" wire:model="rate" class="form-input"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Tarih</th><th>Para Birimi</th><th>Kur</th></tr></thead>
            <tbody>
                @forelse($rates as $r)
                    <tr>
                        <td class="text-xs">{{ $r['rate_date'] }}</td>
                        <td class="font-medium">{{ $r['currency']['code'] }} - {{ $r['currency']['name'] }}</td>
                        <td class="font-mono font-bold text-primary-600">{{ number_format($r['rate'], 6) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-6">Kur kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
