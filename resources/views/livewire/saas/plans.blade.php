<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Abonelik Paketleri</h2></div>
        <button wire:click="openCreate" class="btn-primary">+ Yeni Paket</button>
    </div>
    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-md">
                <div class="modal-header"><h3 class="text-base font-semibold">Yeni Paket</h3><button wire:click="cancel">X</button></div>
                <form wire:submit="save" class="modal-body">
                    <div class="space-y-4">
                        <div><label class="form-label">Paket Adı</label><input type="text" wire:model="name" class="form-input" placeholder="Örn: Professional"></div>
                        <div><label class="form-label">Fiyat</label><input type="number" wire:model="price" class="form-input"></div>
                        <div><label class="form-label">Döngü</label><select wire:model="billing_cycle" class="form-select"><option value="monthly">Aylık</option><option value="yearly">Yıllık</option></select></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                </form>
            </div>
        </div>
    @endif
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($plans as $p)
            <div class="card p-6 border-t-4 border-primary-500">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $p['name'] }}</h3>
                <div class="mt-4 flex items-baseline text-slate-900 dark:text-white">
                    <span class="text-3xl font-extrabold tracking-tight">{{ number_format($p['price'], 2) }} ₺</span>
                    <span class="ml-1 text-xl font-semibold text-slate-500">/{{ $p['billing_cycle'] === 'monthly' ? 'ay' : 'yıl' }}</span>
                </div>
                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Tüm temel ERP modülleri dahil.</p>
            </div>
        @endforeach
    </div>
</div>
