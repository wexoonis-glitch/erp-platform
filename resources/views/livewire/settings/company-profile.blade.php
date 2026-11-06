<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="page-title">Şirket Profili</h2>
            <p class="page-subtitle">Fatura, sevkiyat, rapor başlıklarında kullanılacak bilgiler</p>
        </div>
        <button wire:click="save" class="btn-primary">Kaydet</button>
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

    @if($saved)
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 rounded-xl p-3 text-sm text-emerald-700 dark:text-emerald-400">
            ✓ Şirket profili kaydedildi.
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- SOL: Logo + Genel Bilgiler --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">Kimlik Bilgileri</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label">Resmi Unvan</label>
                        <input type="text" wire:model="legal_name" class="form-input" placeholder="XYZ Teknoloji A.Ş.">
                        @error('legal_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Ticari Ad</label>
                        <input type="text" wire:model="display_name" class="form-input" placeholder="XYZ">
                        @error('display_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Vergi No / TCKN</label>
                        <input type="text" wire:model="tax_no" class="form-input" placeholder="1234567890">
                    </div>
                    <div>
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" wire:model="tax_office" class="form-input" placeholder="Beşiktaş">
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">İletişim</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label">Adres</label>
                        <textarea wire:model="address" rows="2" class="form-input" placeholder="Mahalle, Sokak, No"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Şehir</label>
                        <input type="text" wire:model="city" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Posta Kodu</label>
                        <input type="text" wire:model="postal_code" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Ülke</label>
                        <input type="text" wire:model="country" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Telefon</label>
                        <input type="text" wire:model="phone" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">E-posta</label>
                        <input type="email" wire:model="email" class="form-input">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Web Sitesi</label>
                        <input type="text" wire:model="website" class="form-input">
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">Mali Ayarlar</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Varsayılan Para Birimi</label>
                        <input type="text" wire:model="default_currency" maxlength="3" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Mali Yıl Başlangıç Ayı</label>
                        <select wire:model="fiscal_year_start_month" class="form-select">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }} — {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">Belge Serileri</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Satış Faturası Serisi</label>
                        <input type="text" wire:model="invoice_prefix" class="form-input" placeholder="INV">
                    </div>
                    <div>
                        <label class="form-label">Alış Faturası Serisi</label>
                        <input type="text" wire:model="purchase_invoice_prefix" class="form-input" placeholder="AP">
                    </div>
                    <div>
                        <label class="form-label">Satış Siparişi Serisi</label>
                        <input type="text" wire:model="sales_order_prefix" class="form-input" placeholder="SO">
                    </div>
                    <div>
                        <label class="form-label">Satın Alma Siparişi Serisi</label>
                        <input type="text" wire:model="purchase_order_prefix" class="form-input" placeholder="PO">
                    </div>
                </div>
            </div>

            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">e-Fatura</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model.live="einvoice_registered" id="ei" class="rounded">
                        <label for="ei" class="text-sm">e-Fatura mükellefiyim</label>
                    </div>
                    @if($einvoice_registered)
                        <div>
                            <label class="form-label">Entegratör</label>
                            <select wire:model="einvoice_integrator" class="form-select">
                                <option value="">— Seç —</option>
                                <option value="gib">GİB Portal</option>
                                <option value="uyumsoft">Uyumsoft</option>
                                <option value="foriba">Foriba</option>
                                <option value="izibiz">İzibiz</option>
                                <option value="digitalplanet">Digital Planet</option>
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">Notlar</h3>
                <textarea wire:model="notes" rows="3" class="form-input" placeholder="İç kullanım için özel notlar..."></textarea>
            </div>

            <div class="flex justify-end">
                <button wire:click="save" class="btn-primary">Kaydet</button>
            </div>
        </div>

        {{-- SAĞ: Logo ve Önizleme --}}
        <div class="space-y-6">
            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">Logo</h3>

                @if($currentLogoUrl)
                    <div class="mb-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl flex items-center justify-center">
                        <img src="{{ $currentLogoUrl }}" alt="Company Logo" class="max-h-24 max-w-full">
                    </div>
                    <button wire:click="removeLogo" class="btn-secondary w-full" wire:confirm="Logoyu kaldırmak istediğinize emin misiniz?">Logoyu Kaldır</button>
                @else
                    <div class="text-center py-6 text-slate-400 text-sm">
                        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        Henüz logo yüklenmemiş
                    </div>
                @endif

                <div class="mt-4">
                    <label class="form-label">Yeni Logo Yükle</label>
                    <input type="file" wire:model="logo" accept="image/*" class="form-input text-xs file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <div wire:loading wire:target="logo" class="text-xs text-primary-600 mt-2">Yükleniyor...</div>
                    @error('logo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if($logo)
                    <div class="mt-4 p-4 bg-emerald-50 dark:bg-emerald-950/30 rounded-xl">
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 mb-2">Kaydetmek için hazır:</p>
                        <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="max-h-24 max-w-full mx-auto">
                    </div>
                @endif
            </div>

            <div class="card card-body">
                <h3 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-4">Belge Önizleme</h3>
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 bg-white dark:bg-slate-900">
                    <div class="flex items-start gap-3 pb-3 border-b border-slate-200 dark:border-slate-700">
                        @if($currentLogoUrl)
                            <img src="{{ $currentLogoUrl }}" class="w-10 h-10 object-contain">
                        @else
                            <div class="w-10 h-10 rounded bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center text-primary-600 font-bold">
                                {{ strtoupper(substr($display_name ?: 'X', 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $legal_name ?: 'Şirket Adı' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $tax_office ?: 'Vergi Dairesi' }} — {{ $tax_no ?: 'Vergi No' }}</p>
                        </div>
                    </div>
                    <div class="pt-3 space-y-1">
                        <p class="text-[10px] text-slate-500">{{ $address ?: 'Adres' }}</p>
                        <p class="text-[10px] text-slate-500">{{ $city }} {{ $postal_code }} — {{ $country }}</p>
                        <p class="text-[10px] text-slate-500">{{ $phone }} · {{ $email }}</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                        <span class="text-[10px] text-slate-400">Fatura Önizleme</span>
                        <span class="text-[10px] font-mono text-primary-600">{{ $invoice_prefix }}-2026-00001</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
