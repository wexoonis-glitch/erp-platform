<div class="space-y-6">
    <div>
        <h2 class="page-title">Cari Ekstre</h2>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">Tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="card card-body">
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <div>
                <label class="form-label">Tür</label>
                <select wire:model.live="type" class="form-select">
                    <option value="customer">Müşteri</option>
                    <option value="supplier">Tedarikçi</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Cari Hesap</label>
                <select wire:model.live="entityId" class="form-select">
                    <option value="">— Seçiniz —</option>
                    @foreach($entities as $e)
                        <option value="{{ $e['id'] }}">{{ $e['code'] }} — {{ $e['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Başlangıç</label>
                <input type="date" wire:model.live="startDate" class="form-input">
            </div>
            <div>
                <label class="form-label">Bitiş</label>
                <input type="date" wire:model.live="endDate" class="form-input">
            </div>
        </div>
    </div>

    @if($entityId)
        <div class="card overflow-hidden">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Açıklama</th>
                        <th>Borç</th>
                        <th>Alacak</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td class="text-xs">{{ $r['date'] }}</td>
                            <td class="text-sm">{{ $r['desc'] }}</td>
                            <td class="font-mono text-xs text-rose-600">{{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '' }}</td>
                            <td class="font-mono text-xs text-emerald-600">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-6">Hareket yok.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/80 border-t-2 border-slate-200 dark:border-slate-700">
                    <tr>
                        <td colspan="2" class="text-right font-bold uppercase text-sm">Bakiye</td>
                        <td colspan="2" class="text-center font-mono font-bold text-lg {{ $balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ number_format(abs($balance), 2) }} {{ $balance > 0 ? '(Borçlu)' : '(Alacaklı)' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
