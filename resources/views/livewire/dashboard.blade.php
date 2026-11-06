<div class="space-y-6">
    {{-- Başlık --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Yönetim Paneli</h2>
            <p class="page-subtitle">Hoş geldin, {{ auth()->user()->name }}. İşte bugünkü durumun.</p>
        </div>
        <div class="text-sm text-slate-500 font-medium bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
            {{ now()->translatedFormat('d F Y, l') }}
        </div>
    </div>

    @if($isSuper && empty($kpis))
        <div class="card p-12 text-center">
            <div class="text-4xl mb-4">👋</div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Süper Yönetici Modundasınız</h3>
            <p class="text-sm text-slate-500 mt-2">Dashboard verilerini görmek için lütfen yukarıdan bir Tenant seçin veya Tenants sayfasına gidin.</p>
            <a href="{{ route('tenants.index') }}" class="btn-primary mt-4 inline-flex">Tenant Seç</a>
        </div>
    @else
        {{-- KPI Kartları --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Aylık Ciro --}}
            <div class="card p-5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-500/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Bu Ay Ciro</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($kpis['monthly_revenue'] ?? 0, 2) }} ₺</div>
                <div class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Satış Faturaları
                </div>
            </div>

            {{-- Tahsilatlar --}}
            <div class="card p-5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-sky-500/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Bu Ay Tahsilat</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($kpis['monthly_collections'] ?? 0, 2) }} ₺</div>
                <div class="mt-2 text-xs font-semibold text-sky-600 dark:text-sky-400 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Nakit Girişi
                </div>
            </div>

            {{-- Açık Alacak --}}
            <div class="card p-5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-amber-500/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Açık Alacak</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($kpis['open_receivables'] ?? 0, 2) }} ₺</div>
                <div class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Tahsil Edilecek
                </div>
            </div>

            {{-- Açık Borç --}}
            <div class="card p-5 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-rose-500/10 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Açık Borç</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($kpis['open_payables'] ?? 0, 2) }} ₺</div>
                <div class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    Ödenecek
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Satış Grafiği --}}
            <div class="lg:col-span-2 card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Son 6 Aylık Satış Trendi</h3>
                </div>
                <div class="relative h-72 w-full">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            {{-- Son İşlemler & Özet --}}
            <div class="space-y-6">
                <div class="card p-6">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Hızlı Özet</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                Stok Değeri
                            </div>
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($kpis['stock_value'] ?? 0, 2) }} ₺</span>
                        </div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Aktif Personel
                            </div>
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $kpis['active_employees'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Son Satışlar</h3>
                    <div class="space-y-3">
                        @forelse($recent as $r)
                            <div class="flex items-center justify-between text-sm">
                                <div class="min-w-0 pr-2">
                                    <div class="font-medium text-slate-900 dark:text-white truncate">{{ $r['desc'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $r['date'] }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-mono font-semibold">{{ number_format($r['amount'], 2) }} ₺</div>
                                    <div class="text-[10px] uppercase {{ $r['status'] === 'invoiced' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $r['status'] }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">Henüz satış yok.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        let salesChartInstance = null;

        function renderChart(data) {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;

            if (salesChartInstance) {
                salesChartInstance.destroy();
            }

            salesChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Aylık Ciro (₺)',
                        data: data.values,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.1)' },
                            ticks: { color: '#94a3b8' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        }

        // Livewire'dan gelen veriyi dinle
        Livewire.on('dashboard-chart-updated', (event) => {
            renderChart(event.data);
        });
    });
</script>
@endpush
