<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Malzeme İhtiyaç Planlaması (MRP)</h2>
            <p class="page-subtitle">Onaylı satış siparişlerine göre hammadde ihtiyaç analizi.</p>
        </div>
        <button wire:click="calculate" class="btn-primary">Hesapla</button>
    </div>
    
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($calculated)
        <div class="card overflow-hidden">
            <table class="table-modern">
                <thead><tr><th>Kod</th><th>Hammadde</th><th>Birim</th><th>Gereken</th><th>Mevcut Stok</th><th>Eksik (Net İhtiyaç)</th></tr></thead>
                <tbody>
                    @forelse($requirements as $r)
                        <tr>
                            <td class="font-mono text-xs">{{ $r['code'] }}</td>
                            <td class="font-medium text-slate-900 dark:text-white">{{ $r['name'] }}</td>
                            <td class="text-xs">{{ $r['unit'] }}</td>
                            <td class="font-mono">{{ number_format($r['required'], 2) }}</td>
                            <td class="font-mono">{{ number_format($r['available'], 2) }}</td>
                            <td class="font-mono font-bold {{ $r['net_required'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $r['net_required'] > 0 ? number_format($r['net_required'], 2) : 'Yeterli' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-6 text-slate-500">İhtiyaç duyulan hammadde bulunamadı (Onaylı sipariş veya Reçete yok).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="card p-12 text-center">
            <div class="text-4xl mb-4">📊</div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Analizi Başlatın</h3>
            <p class="text-sm text-slate-500 mt-2">Onaylanmış satış siparişlerini ve mevcut stokları karşılaştırmak için "Hesapla" butonuna tıklayın.</p>
        </div>
    @endif
</div>
