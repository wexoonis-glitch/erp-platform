<div class="space-y-6">
    <div>
        <h2 class="page-title">Finansal Raporlar</h2>
        <p class="page-subtitle">Mizan · Bilanço · Gelir Tablosu · KDV Beyannamesi</p>
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

    {{-- Tabs --}}
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="-mb-px flex gap-6">
            <button wire:click="setTab('tb')" class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $tab === 'tb' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">Mizan</button>
            <button wire:click="setTab('bs')" class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $tab === 'bs' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">Bilanço</button>
            <button wire:click="setTab('is')" class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $tab === 'is' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">Gelir Tablosu</button>
            <button wire:click="setTab('vat')" class="pb-3 px-1 border-b-2 text-sm font-medium transition-colors {{ $tab === 'vat' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">KDV Beyannamesi</button>
        </nav>
    </div>

    {{-- Date range --}}
    <div class="card card-body">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @if(in_array($tab, ['tb', 'bs']))
                <div>
                    <label class="form-label">Tarih itibarıyla</label>
                    <input type="date" wire:model.live="asOfDate" class="form-input">
                </div>
            @else
                <div>
                    <label class="form-label">Başlangıç</label>
                    <input type="date" wire:model.live="fromDate" class="form-input">
                </div>
                <div>
                    <label class="form-label">Bitiş</label>
                    <input type="date" wire:model.live="toDate" class="form-input">
                </div>
            @endif
        </div>
    </div>

    @if(!$selectedTenantId && $isSuper)
        <div class="card p-12 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">Yukarıdan tenant seçin.</p>
        </div>
    @else

        {{-- ============ MİZAN ============ --}}
        @if($tab === 'tb')
            <div class="card overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">KOD</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">HESAP</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">TİP</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400">BORÇ</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400">ALACAK</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400">BAKİYE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @php $totD = 0; $totC = 0; @endphp
                        @forelse($tb ?? [] as $r)
                            @php $totD += $r['debit']; $totC += $r['credit']; @endphp
                            <tr wire:key="tb-{{ $r['id'] }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $r['code'] }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $r['name'] }}</td>
                                <td class="px-4 py-3"><span class="badge-muted">{{ $r['type'] }}</span></td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-slate-700 dark:text-slate-300">{{ number_format($r['debit'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-slate-700 dark:text-slate-300">{{ number_format($r['credit'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $r['balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ number_format($r['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">Hareket yok.</td></tr>
                        @endforelse
                    </tbody>
                    @if(!empty($tb))
                        <tfoot class="bg-slate-50 dark:bg-slate-800/80 border-t-2 border-slate-200 dark:border-slate-700">
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-right font-bold text-slate-900 dark:text-white uppercase">TOPLAM</td>
                                <td class="px-4 py-4 text-right font-mono text-sm font-bold text-slate-900 dark:text-white">{{ number_format($totD, 2) }}</td>
                                <td class="px-4 py-4 text-right font-mono text-sm font-bold text-slate-900 dark:text-white">{{ number_format($totC, 2) }}</td>
                                <td class="px-4 py-4 text-right font-mono text-sm font-bold {{ abs($totD - $totC) < 0.01 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($totD - $totC, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif

        {{-- ============ BİLANÇO ============ --}}
        @if($tab === 'bs')
            @php $bs = $bs ?? null; @endphp
            @if($bs)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Aktif --}}
                    <div class="card overflow-hidden">
                        <div class="px-4 py-3 bg-gradient-to-r from-primary-50 to-transparent dark:from-primary-950/30 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">AKTİF (Varlıklar)</h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($bs['assets'] as $a)
                                    <tr wire:key="bs-a-{{ $a['id'] }}">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $a['code'] }}</td>
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $a['name'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-xs text-slate-900 dark:text-white">{{ number_format($a['balance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-6 text-slate-500 text-sm">Varlık yok.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white uppercase">TOPLAM AKTİF</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm font-bold text-slate-900 dark:text-white">{{ number_format($bs['total_assets'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Pasif --}}
                    <div class="card overflow-hidden">
                        <div class="px-4 py-3 bg-gradient-to-r from-rose-50 to-transparent dark:from-rose-950/30 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">PASİF (Yükümlülük + Özkaynak)</h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr><td colspan="3" class="px-4 py-2 text-xs font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/20">Yükümlülükler</td></tr>
                                @forelse($bs['liabilities'] as $l)
                                    <tr wire:key="bs-l-{{ $l['id'] }}">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $l['code'] }}</td>
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $l['name'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-xs text-slate-900 dark:text-white">{{ number_format($l['balance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-2 text-slate-500 text-xs">—</td></tr>
                                @endforelse
                                <tr><td colspan="3" class="px-4 py-2 text-xs font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-200 dark:border-slate-700">Özkaynaklar</td></tr>
                                @forelse($bs['equity'] as $e)
                                    <tr wire:key="bs-e-{{ $e['id'] }}">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $e['code'] }}</td>
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $e['name'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-xs text-slate-900 dark:text-white">{{ number_format($e['balance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-2 text-slate-500 text-xs">—</td></tr>
                                @endforelse
                                <tr class="bg-emerald-50/50 dark:bg-emerald-950/20">
                                    <td class="px-4 py-2 font-mono text-xs text-slate-500">—</td>
                                    <td class="px-4 py-2 font-medium text-emerald-700 dark:text-emerald-400">Dönem Net Kârı</td>
                                    <td class="px-4 py-2 text-right font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($bs['net_profit'], 2) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white uppercase">TOPLAM PASİF</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm font-bold text-slate-900 dark:text-white">{{ number_format($bs['total_liabilities'] + $bs['total_equity'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                @if($bs['balanced'])
                    <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 rounded-xl p-3 text-sm text-emerald-700 dark:text-emerald-400">
                        ✓ Bilanço dengeli: Aktif = Pasif ({{ number_format($bs['total_assets'], 2) }})
                    </div>
                @else
                    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
                        ⚠ Bilanço dengesiz! Aktif ({{ number_format($bs['total_assets'], 2) }}) ≠ Pasif ({{ number_format($bs['total_liabilities'] + $bs['total_equity'], 2) }})
                    </div>
                @endif
            @endif
        @endif

        {{-- ============ GELİR TABLOSU ============ --}}
        @if($tab === 'is')
            @php $is = $is ?? null; @endphp
            @if($is)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="card overflow-hidden">
                        <div class="px-4 py-3 bg-gradient-to-r from-emerald-50 to-transparent dark:from-emerald-950/30 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">GELİRLER</h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($is['revenues'] as $r)
                                    <tr wire:key="is-r-{{ $loop->index }}">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $r['code'] }}</td>
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $r['name'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-xs text-slate-900 dark:text-white">{{ number_format($r['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-6 text-slate-500 text-sm">Gelir yok.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white uppercase">TOPLAM GELİR</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($is['total_revenue'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="card overflow-hidden">
                        <div class="px-4 py-3 bg-gradient-to-r from-rose-50 to-transparent dark:from-rose-950/30 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">GİDERLER</h3>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($is['expenses'] as $e)
                                    <tr wire:key="is-e-{{ $loop->index }}">
                                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $e['code'] }}</td>
                                        <td class="px-4 py-2 text-slate-700 dark:text-slate-300">{{ $e['name'] }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-xs text-slate-900 dark:text-white">{{ number_format($e['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-6 text-slate-500 text-sm">Gider yok.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-slate-200 dark:border-slate-700">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white uppercase">TOPLAM GİDER</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm font-bold text-rose-600 dark:text-rose-400">{{ number_format($is['total_expense'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card p-6 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dönem Net Kârı</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $is['from_date'] }} → {{ $is['to_date'] }}</div>
                    </div>
                    <div class="text-3xl font-bold {{ $is['net_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ number_format($is['net_profit'], 2) }}
                    </div>
                </div>
            @endif
        @endif

        {{-- ============ KDV BEYANNAMESİ ============ --}}
        @if($tab === 'vat')
            @php $vat = $vat ?? null; @endphp
            @if($vat && !isset($vat['error']))
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="card p-5">
                        <div class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Hesaplanan KDV (391)</div>
                        <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($vat['output_vat'], 2) }}</div>
                    </div>
                    <div class="card p-5">
                        <div class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">İndirilecek KDV (191)</div>
                        <div class="mt-2 text-2xl font-bold text-sky-600 dark:text-sky-400">{{ number_format($vat['input_vat'], 2) }}</div>
                    </div>
                    <div class="card p-5 {{ $vat['payable'] >= 0 ? 'ring-2 ring-rose-500/30' : 'ring-2 ring-emerald-500/30' }}">
                        <div class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                            {{ $vat['payable'] >= 0 ? 'ÖDENECEK KDV' : 'DEVREDEN KDV' }}
                        </div>
                        <div class="mt-2 text-2xl font-bold {{ $vat['payable'] >= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ number_format(abs($vat['payable']), 2) }}
                        </div>
                    </div>
                </div>
                <div class="card p-6 mt-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Dönem</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $vat['from_date'] }} → {{ $vat['to_date'] }}</p>
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <strong>Not:</strong> Bu rapor yalnızca posted yevmiye kayıtlarını dikkate alır. Resmi beyanname için mali müşavir kontrolü gereklidir.
                        </p>
                    </div>
                </div>
            @elseif($vat && isset($vat['error']))
                <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
                    {{ $vat['error'] }}
                </div>
            @endif
        @endif

    @endif
</div>
