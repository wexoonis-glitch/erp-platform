<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Ülke & Vergi Ayarları</h2>
            <p class="page-subtitle">Tenant'ın hangi ülke paketiyle çalıştığını ve vergi oranlarını yönetin.</p>
        </div>
        @if($isSuper)
            <button wire:click="openCountryForm" class="btn-primary">+ Yeni Ülke Ekle</button>
        @endif
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">Tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>@endforeach
            </select>
        </div>
    @endif

    @if($message)
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' }}">{{ $message }}</div>
    @endif

    {{-- Yeni Ülke Modal --}}
    @if($showCountryForm)
        <div class="modal-backdrop" wire:click.self="cancelCountryForm">
            <div class="modal-content max-w-xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold">Yeni Ülke Ekle</h3>
                    <button wire:click="cancelCountryForm">X</button>
                </div>
                <form wire:submit="saveCountry" class="modal-body space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Ülke Kodu (ISO 2)</label>
                            <input type="text" wire:model="new_code" maxlength="2" class="form-input uppercase" placeholder="AZ, GB, KZ...">
                            @error('new_code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Ülke Adı</label>
                            <input type="text" wire:model="new_name" class="form-input" placeholder="Azerbaijan">
                            @error('new_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Varsayılan Para Birimi</label>
                            <input type="text" wire:model="new_currency" maxlength="3" class="form-input uppercase" placeholder="USD">
                        </div>
                        <div>
                            <label class="form-label">Locale</label>
                            <input type="text" wire:model="new_locale" class="form-input" placeholder="en, az, de...">
                        </div>
                        <div>
                            <label class="form-label">Vergi Etiketi</label>
                            <input type="text" wire:model="new_tax_label" class="form-input" placeholder="Sales Tax, VAT, KDV, ƏDV...">
                        </div>
                        <div>
                            <label class="form-label">Timezone</label>
                            <input type="text" wire:model="new_timezone" class="form-input" placeholder="Asia/Baku">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">
                        Bu ülke eklendiğinde ülke listesinde görünür ve tenant tarafından aktive edilebilir.
                        e-Fatura/UBL desteği varsayılan olarak YOK'tur — özel bir entegrasyon gerekiyorsa
                        developer tarafından <code>app/CountryPacks/</code> altına bir kod sınıfı eklenmelidir.
                    </p>
                    <div class="modal-footer">
                        <button type="submit" class="btn-primary">Ülkeyi Ekle</button>
                        <button type="button" wire:click="cancelCountryForm" class="btn-secondary">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card p-5">
        <h3 class="text-sm font-bold uppercase text-slate-500 mb-3">Aktif Country Pack</h3>
        <div class="text-2xl font-bold text-primary-600 mb-1">{{ $activePack ?: 'Tanımsız' }}</div>
        <p class="text-xs text-slate-500">Bu tenant'a ülkeye özel kurallar (vergi, e-fatura, fatura etiketi) bu paketten gelir.</p>
    </div>

    {{-- Mevcut Country Pack'ler --}}
    <div class="card overflow-hidden">
        <div class="card-header bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold uppercase">Mevcut Country Pack'ler ({{ count($packList) }})</h3>
        </div>
        <div class="max-h-[500px] overflow-y-auto">
            <table class="table-modern">
                <thead><tr><th>Kod</th><th>Ülke</th><th>Vergi</th><th>Para Birimi</th><th>Locale</th><th>e-Fatura</th><th>Tip</th><th>İşlem</th></tr></thead>
                <tbody>
                    @foreach($packList as $p)
                        <tr>
                            <td class="font-mono font-bold">{{ $p['code'] }}</td>
                            <td>{{ $p['name'] }}</td>
                            <td class="text-xs">{{ $p['tax_label'] }}</td>
                            <td class="font-mono">{{ $p['currency'] }}</td>
                            <td class="font-mono">{{ $p['locale'] }}</td>
                            <td>{!! $p['supports_einvoice'] ? '<span class="badge-success">Var</span>' : '<span class="badge-muted">Yok</span>' !!}</td>
                            <td>
                                @if($p['is_advanced'])
                                    <span class="badge-primary text-[10px]">Gelişmiş</span>
                                @else
                                    <span class="badge-muted text-[10px]">Generic</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    @if($activePack === $p['code'])
                                        <span class="badge-success">Aktif</span>
                                    @else
                                        <button wire:click="activatePack('{{ $p['code'] }}')" wire:confirm="Tenant bu pakete geçirilecek. Varsayılan vergiler eklenecek. Devam?" class="btn-ghost-primary text-xs">Aktive Et</button>
                                    @endif
                                    @if($isSuper && !$p['is_advanced'] && $activePack !== $p['code'])
                                        <button wire:click="deleteCountry('{{ $p['code'] }}')" wire:confirm="Bu ülkeyi silmek istediğinize emin misiniz?" class="btn-ghost-danger text-xs">Sil</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vergi Rejimleri --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase text-slate-500">Vergi Rejimleri</h3>
        <button wire:click="openCreateTax" class="btn-primary">+ Yeni Vergi</button>
    </div>

    @if($showTaxForm)
        <div class="card card-body space-y-4">
            <h4 class="font-semibold">{{ $editingTaxId ? 'Vergi Düzenle' : 'Yeni Vergi' }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div><label class="form-label">Kod</label><input type="text" wire:model="tax_code" class="form-input" placeholder="US-NY-SALES"></div>
                <div><label class="form-label">Ad</label><input type="text" wire:model="tax_name" class="form-input" placeholder="New York Sales Tax"></div>
                <div><label class="form-label">Oran (%)</label><input type="number" step="0.0001" wire:model="tax_rate" class="form-input"></div>
                <div><label class="form-label">Bölge (Opsiyonel)</label><input type="text" wire:model="tax_region" class="form-input" placeholder="CA, NY..."></div>
                <div><label class="form-label">Uygulanır</label><select wire:model="tax_applies_to" class="form-select"><option value="both">Mal+Hizmet</option><option value="goods">Mal</option><option value="services">Hizmet</option></select></div>
                <div class="flex flex-col gap-2 pt-7">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="tax_is_inclusive" class="rounded"> Vergi fiyata dahil</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="tax_is_default" class="rounded"> Varsayılan</label>
                </div>
            </div>
            <div class="flex gap-2">
                <button wire:click="saveTax" class="btn-primary">Kaydet</button>
                <button wire:click="cancelTax" class="btn-secondary">İptal</button>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="table-modern">
            <thead><tr><th>Kod</th><th>Ad</th><th>Ülke</th><th>Bölge</th><th>Oran</th><th>Uygulanır</th><th>Varsayılan</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($taxRegimes as $t)
                    <tr>
                        <td class="font-mono text-xs font-bold">{{ $t['code'] }}</td>
                        <td>{{ $t['name'] }}</td>
                        <td class="font-mono">{{ $t['country'] }}</td>
                        <td class="font-mono text-xs">{{ $t['region'] ?? '—' }}</td>
                        <td class="font-mono font-bold">%{{ rtrim(rtrim(number_format($t['rate'], 4, '.', ''), '0'), '.') }}</td>
                        <td class="text-xs">{{ $t['applies_to'] }}</td>
                        <td>{!! $t['is_default'] ? '<span class="badge-success">Evet</span>' : '—' !!}</td>
                        <td>
                            <div class="flex gap-1">
                                <button wire:click="openEditTax('{{ $t['id'] }}')" class="btn-ghost-primary text-xs">Düzenle</button>
                                <button wire:click="deleteTax('{{ $t['id'] }}')" wire:confirm="Silinsin mi?" class="btn-ghost-danger text-xs">Sil</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-6 text-slate-500">Vergi rejimi yok. Bir country pack aktive edin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
