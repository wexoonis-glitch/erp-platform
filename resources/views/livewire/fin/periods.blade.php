<div class="space-y-6">
    <div>
        <h2 class="page-title">Dönem Kapanışı</h2>
        <p class="page-subtitle">Kilitlenen dönemlere geriye dönük kayıt atılamaz.</p>
    </div>
    
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Dönem</th><th>Durum</th><th>Kapanış Tarihi</th><th>İşlem</th></tr></thead>
            <tbody>
                @foreach($periods as $p)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $p['label'] }}</td>
                        <td>
                            <span class="{{ $p['status'] === 'closed' ? 'badge-danger' : 'badge-success' }}">
                                {{ $p['status'] === 'closed' ? 'Kilitli' : 'Açık' }}
                            </span>
                        </td>
                        <td class="text-xs">{{ $p['closed_at'] ?? '—' }}</td>
                        <td>
                            @if($p['status'] === 'closed')
                                <button wire:click="toggle('{{ $p['year'] }}', '{{ $p['month'] }}')" wire:confirm="Dönemi geri açmak istediğinize emin misiniz?" class="btn-ghost-primary">Kilidi Aç</button>
                            @else
                                <button wire:click="toggle('{{ $p['year'] }}', '{{ $p['month'] }}')" wire:confirm="Dönemi kapatmak istediğinize emin misiniz? Bu işlem geriye dönük kayıtları engeller." class="btn-ghost-danger">Dönemi Kapat</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
