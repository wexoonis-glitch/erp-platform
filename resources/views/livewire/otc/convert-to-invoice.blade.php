<div class="space-y-6">
    <div>
        <h2 class="page-title">Siparişten Faturaya</h2>
        <p class="page-subtitle">Onaylanmış satış siparişlerini tek tıkla müşteri faturasına dönüştürün.</p>
    </div>

    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400' }}">
            {{ $message }}
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead>
                <tr><th>SO No</th><th>Müşteri</th><th>Tarih</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr wire:key="conv-{{ $o['id'] }}">
                        <td class="font-mono text-xs font-semibold">{{ $o['so_no'] }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $o['customer'] }}</td>
                        <td class="text-xs">{{ $o['order_date'] }}</td>
                        <td class="font-mono text-xs font-semibold">{{ number_format($o['grand_total'], 2) }} {{ $o['currency'] }}</td>
                        <td><span class="badge-info">{{ $o['status'] }}</span></td>
                        <td>
                            <div class="flex justify-center gap-1">
                                <button wire:click="convert('{{ $o['id'] }}', false)" class="btn-ghost-primary">Draft Fatura</button>
                                <button wire:click="convert('{{ $o['id'] }}', true)" wire:confirm="Fatura oluşturulup post edilecek. Stok düşülecek ve yevmiye atılacak. Devam edilsin mi?" class="btn-ghost-warning">Faturalaştır + Post Et</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-12">
                        <div class="text-4xl mb-2">📄</div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Faturaya dönüştürülecek onaylı sipariş yok.
                        </p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
