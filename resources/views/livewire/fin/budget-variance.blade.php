<div class="space-y-6">
    <div><h2 class="page-title">Bütçe vs Gerçekleşen</h2><p class="page-subtitle">Masraf merkezi bazında planlanan ve gerçekleşen giderler</p></div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    <div class="card card-body">
        <div class="flex items-end gap-4">
            <div><label class="form-label">Yıl</label><input type="text" wire:model.live="year" maxlength="4" class="form-input w-24"></div>
        </div>
    </div>

    @if(empty($report) || empty($report['rows']))
        <div class="card p-8 text-center text-slate-500">Bu yıl için bütçe veya gerçekleşen veri yok.</div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="card p-5">
                <div class="text-xs uppercase text-slate-500">Toplam Planlanan</div>
                <div class="mt-2 text-2xl font-bold text-sky-600">{{ number_format($report['grand_planned'], 2) }} ₺</div>
            </div>
            <div class="card p-5">
                <div class="text-xs uppercase text-slate-500">Toplam Gerçekleşen</div>
                <div class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($report['grand_actual'], 2) }} ₺</div>
            </div>
            <div class="card p-5">
                <div class="text-xs uppercase text-slate-500">Kalan Bütçe (Sapma)</div>
                <div class="mt-2 text-2xl font-bold {{ $report['grand_variance'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ number_format($report['grand_variance'], 2) }} ₺
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="table-modern">
                <thead>
                    <tr><th>Kod</th><th>Masraf Merkezi</th><th>Planlanan</th><th>Gerçekleşen</th><th>Sapma</th><th>Kullanım %</th></tr>
                </thead>
                <tbody>
                    @foreach($report['rows'] as $r)
                        <tr>
                            <td class="font-mono text-xs">{{ $r['code'] }}</td>
                            <td class="text-left font-medium">{{ $r['name'] }}</td>
                            <td class="font-mono text-xs">{{ number_format($r['planned'], 2) }}</td>
                            <td class="font-mono text-xs">{{ number_format($r['actual'], 2) }}</td>
                            <td class="font-mono text-xs font-bold {{ $r['variance'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ number_format($r['variance'], 2) }}
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 {{ $r['variance_pct'] > 100 ? 'bg-rose-500' : ($r['variance_pct'] > 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                             style="width: {{ min(100, $r['variance_pct']) }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono">{{ $r['variance_pct'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/80 border-t-2 border-slate-200 dark:border-slate-700">
                    <tr>
                        <td colspan="2" class="text-right font-bold uppercase text-sm">TOPLAM</td>
                        <td class="font-mono font-bold">{{ number_format($report['grand_planned'], 2) }}</td>
                        <td class="font-mono font-bold">{{ number_format($report['grand_actual'], 2) }}</td>
                        <td class="font-mono font-bold {{ $report['grand_variance'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($report['grand_variance'], 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
