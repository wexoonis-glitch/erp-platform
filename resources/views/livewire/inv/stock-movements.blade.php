<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Stok Durumu & Hareketler</h2></div>
        <button wire:click="openInForm" class="btn-primary">+ Manuel Stok Girişi</button>
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

    @if($showInForm)
        <div class="modal-backdrop" wire:click.self="cancelInForm">
            <div class="modal-content max-w-xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Manuel Stok Girişi</h3>
                    <button wire:click="cancelInForm" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveIn" class="modal-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Ürün</label>
                            <select wire:model.live="in_product_id" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach($products as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['code'] }} — {{ $p['name'] }}</option>
                                @endforeach
                            </select>
                            @error('in_product_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Tarih</label>
                            <input type="date" wire:model="in_date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Miktar</label>
                            <input type="number" step="0.01" wire:model="in_quantity" class="form-input">
                            @error('in_quantity') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Birim Maliyet</label>
                            <input type="number" step="0.01" wire:model="in_unit_cost" class="form-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Açıklama</label>
                            <input type="text" wire:in_desc="in_desc" class="form-input" placeholder="Örn: Açılış stoğu, Alış faturası vb.">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">Stoğa Ekle</button>
                        <button type="button" wire:click="cancelInForm" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card overflow-hidden">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50"><h3 class="text-sm font-bold uppercase">Anlık Stok</h3></div>
            <table class="table-modern">
                <thead><tr><th>Kod</th><th>Ürün</th><th>Miktar</th></tr></thead>
                <tbody>
                    @forelse($balances as $b)
                        <tr>
                            <td class="font-mono">{{ $b['code'] }}</td>
                            <td>{{ $b['name'] }}</td>
                            <td class="font-mono font-bold {{ $b['qty'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($b['qty'], 2) }} {{ $b['unit'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-6">Kayıt yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50"><h3 class="text-sm font-bold uppercase">Son Hareketler</h3></div>
            <table class="table-modern">
                <thead><tr><th>Tarih</th><th>Ürün</th><th>Tip</th><th>Miktar</th></tr></thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td class="text-xs">{{ $m->movement_date->format('d.m.Y') }}</td>
                            <td class="text-xs">{{ $m->product?->name }}</td>
                            <td>{!! $m->type === 'in' ? '<span class="badge-success">Giriş</span>' : '<span class="badge-danger">Çıkış</span>' !!}</td>
                            <td class="font-mono text-xs">{{ number_format($m->quantity, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-6">Hareket yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
