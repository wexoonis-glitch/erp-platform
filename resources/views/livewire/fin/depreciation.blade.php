<div class="space-y-6">
    <div><h2 class="page-title">Amortisman Koşusu</h2><p class="page-subtitle">Aylık amortisman giderlerini hesaplar ve yevmiye atar.</p></div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    <div class="card card-body">
        <div class="flex flex-wrap items-end gap-4">
            <div><label class="form-label">Yıl</label><input type="text" wire:model="year" class="form-input w-24" maxlength="4"></div>
            <div><label class="form-label">Ay</label><input type="text" wire:model="month" class="form-input w-20" maxlength="2"></div>
            <button wire:click="run" class="btn-primary">Amortismanı Çalıştır</button>
            @if($message) <div class="text-sm text-slate-600 dark:text-slate-400">{{ $message }}</div> @endif
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-slate-50 dark:bg-slate-800/50"><h3 class="text-sm font-bold uppercase">Bekleyen Amortismanlar</h3></div>
        <table class="table-modern">
            <thead><tr><th>Varlık</th><th>Dönem</th><th>Tutar</th></tr></thead>
            <tbody>
                @forelse($pending as $p)
                    <tr>
                        <td>{{ $p['asset']['code'] ?? '' }} — {{ $p['asset']['name'] ?? '' }}</td>
                        <td>{{ $p['period_month'] }}/{{ $p['period_year'] }}</td>
                        <td class="font-mono font-bold">{{ number_format($p['depreciation_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-6">Bekleyen amortisman yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-slate-50 dark:bg-slate-800/50"><h3 class="text-sm font-bold uppercase">Son Post Edilenler</h3></div>
        <table class="table-modern">
            <thead><tr><th>Varlık</th><th>Dönem</th><th>Tutar</th><th>Post Tarihi</th></tr></thead>
            <tbody>
                @forelse($posted as $p)
                    <tr>
                        <td>{{ $p['asset']['code'] ?? '' }} — {{ $p['asset']['name'] ?? '' }}</td>
                        <td>{{ $p['period_month'] }}/{{ $p['period_year'] }}</td>
                        <td class="font-mono">{{ number_format($p['depreciation_amount'], 2) }}</td>
                        <td class="text-xs">{{ $p['posted_at'] ? \Carbon\Carbon::parse($p['posted_at'])->format('d.m.Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6">Henüz amortisman post edilmedi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
