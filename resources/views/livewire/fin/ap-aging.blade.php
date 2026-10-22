<div class="space-y-6">
    <div>
        <h2 class="page-title">AP Aging — Vade Analizi</h2>
        <p class="page-subtitle">Vadesi geçmiş ve yaklaşan tedarikçi borçları</p>
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

    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        @php
            $buckets = [
                ['label' => 'Vadesi gelmemiş', 'value' => $totals['current'], 'color' => 'emerald'],
                ['label' => '1-30 gün', 'value' => $totals['d1_30'], 'color' => 'amber'],
                ['label' => '31-60 gün', 'value' => $totals['d31_60'], 'color' => 'orange'],
                ['label' => '61-90 gün', 'value' => $totals['d61_90'], 'color' => 'rose'],
                ['label' => '90+ gün', 'value' => $totals['d90_plus'], 'color' => 'rose'],
                ['label' => 'TOPLAM', 'value' => $totals['total'], 'color' => 'indigo'],
            ];
        @endphp
        @foreach($buckets as $b)
            <div class="card p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $b['label'] }}</p>
                <p class="mt-1 text-lg font-bold text-{{ $b['color'] }}-600 dark:text-{{ $b['color'] }}-400">
                    {{ number_format($b['value'], 2) }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>Tedarikçi</th>
                    <th class="text-right">Vadesi Gelmemiş</th>
                    <th class="text-right">1-30</th>
                    <th class="text-right">31-60</th>
                    <th class="text-right">61-90</th>
                    <th class="text-right">90+</th>
                    <th class="text-right">Toplam</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr wire:key="aging-{{ $r['supplier_id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $r['code'] }}</td>
                        <td class="text-sm font-medium text-slate-900 dark:text-white">{{ $r['name'] }}</td>
                        <td class="text-right text-xs font-mono">{{ number_format($r['current'], 2) }}</td>
                        <td class="text-right text-xs font-mono">{{ number_format($r['d1_30'], 2) }}</td>
                        <td class="text-right text-xs font-mono">{{ number_format($r['d31_60'], 2) }}</td>
                        <td class="text-right text-xs font-mono">{{ number_format($r['d61_90'], 2) }}</td>
                        <td class="text-right text-xs font-mono text-rose-600 dark:text-rose-400">{{ number_format($r['d90_plus'], 2) }}</td>
                        <td class="text-right text-xs font-mono font-bold text-slate-900 dark:text-white">{{ number_format($r['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12">
                        <div class="text-4xl mb-2">📊</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            @if($isSuper && !$selectedTenantId) Yukarıdan tenant seçin. @else Açık fatura yok. @endif
                        </p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
