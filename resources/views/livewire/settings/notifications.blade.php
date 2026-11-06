<div class="space-y-6">
    <div><h2 class="page-title">Bildirim Ayarları</h2></div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif

    <div class="card card-body">
        <p class="text-sm text-slate-500 mb-4">Hangi olay gerçekleştiğinde sistemin otomatik e-posta göndereceğini buradan belirleyebilirsiniz.</p>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 border rounded-xl dark:border-slate-700">
                <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">Vadesi Yaklaşan Faturalar</h4>
                    <p class="text-xs text-slate-500">Müşteri faturasının vadesi dolmadan 3 gün önce uyarı e-postası gönderilir.</p>
                </div>
                <button wire:click="toggleSetting('fin.ar_invoice.due_soon', 'email')" class="btn-secondary">
                    {{ isset($settings['fin.ar_invoice.due_soon']) && $settings['fin.ar_invoice.due_soon'] ? 'Aktif (Kapat)' : 'Pasif (Aç)' }}
                </button>
            </div>
            
            <div class="flex items-center justify-between p-4 border rounded-xl dark:border-slate-700">
                <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">Kritik Stok Uyarısı</h4>
                    <p class="text-xs text-slate-500">Bir ürünün stoğu emniyet seviyesinin altına düştüğünde uyarı gönderilir (Geliştirme aşamasında).</p>
                </div>
                <button wire:click="toggleSetting('inv.stock.critical', 'email')" class="btn-secondary">
                    {{ isset($settings['inv.stock.critical']) && $settings['inv.stock.critical'] ? 'Aktif (Kapat)' : 'Pasif (Aç)' }}
                </button>
            </div>
        </div>
    </div>
</div>
