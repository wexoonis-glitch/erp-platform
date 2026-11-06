<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="page-title">Sərfiyyat Ayarları</h2>
            <p class="page-subtitle">Satışa eklenen masraf kalemlerinin varsayılanlarını ve davranışını yönetin.</p>
        </div>
    </div>

    @if($message)
        <div class="rounded-xl p-3 text-sm border
            {{ $messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' :
               ($messageType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' :
               'bg-sky-50 border-sky-200 text-sky-700') }}">
            {{ $message }}
        </div>
    @endif

    {{-- Genel ayarlar --}}
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                <i class="ri-settings-3-line mr-2 text-primary-600"></i> Genel Davranış
            </h3>
        </div>
        <div class="card-body space-y-3">
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <input type="checkbox" wire:model="show_in_sales" class="mt-0.5 w-4 h-4 accent-primary-600">
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">Satış ekranında göster</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Kapatılırsa satış formunda sərfiyyat bölümü gizlenir. Varsayılan: açık.
                    </div>
                </div>
            </label>

            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <input type="checkbox" wire:model="required_in_sale" class="mt-0.5 w-4 h-4 accent-primary-600">
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">Satışta en az 1 sərf zorunlu</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        İşaretliyse sərf girilmeden satış kaydedilemez.
                    </div>
                </div>
            </label>

            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <input type="checkbox" wire:model="charge_customer_default" class="mt-0.5 w-4 h-4 accent-primary-600">
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">Varsayılan: müşteri öder</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        İşaretliyse yeni satışta sərf müşteriye yansıtılır (kârdan düşülmez). Satır bazında değiştirilebilir.
                    </div>
                </div>
            </label>

            <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                <button wire:click="saveConfig" wire:loading.attr="disabled" class="btn-primary">
                    <i class="ri-save-line mr-1"></i> Ayarları Kaydet
                </button>
            </div>
        </div>
    </div>

    {{-- Varsayılan sərfler --}}
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                <i class="ri-receipt-line mr-2 text-primary-600"></i> Varsayılan Sərflər
            </h3>
            <span class="badge bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs">
                {{ count($expenses) }} kayıt
            </span>
        </div>

        <div class="card-body">
            {{-- Yeni ekleme --}}
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_160px_auto] gap-2 mb-4">
                <input type="text"
                       wire:model="new_name"
                       wire:keydown.enter="addExpense"
                       placeholder="Sərf adı (Yol, Yemek, Kargo...)"
                       class="form-input">
                <input type="text"
                       wire:model="new_amount"
                       wire:keydown.enter="addExpense"
                       placeholder="Varsayılan tutar"
                       class="form-input">
                <button wire:click="addExpense" class="btn-primary whitespace-nowrap">
                    <i class="ri-add-line mr-1"></i> Ekle
                </button>
            </div>

            {{-- Liste --}}
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Sərf Adı</th>
                            <th class="px-3 py-2 text-left w-40">Varsayılan Tutar</th>
                            <th class="px-3 py-2 text-center w-24">Aktif</th>
                            <th class="px-3 py-2 text-center w-40">Sıra</th>
                            <th class="px-3 py-2 text-right w-28">Sil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($expenses as $e)
                            <tr wire:key="exp-{{ $e['id'] }}">
                                <td class="px-3 py-2">
                                    <input type="text"
                                           value="{{ $e['name'] }}"
                                           wire:change="updateRow('{{ $e['id'] }}', 'name', $event.target.value)"
                                           class="form-input text-sm w-full">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text"
                                           value="{{ number_format($e['default_amount'], 2, ',', '.') }}"
                                           wire:change="updateRow('{{ $e['id'] }}', 'default_amount', $event.target.value)"
                                           class="form-input text-sm w-full text-right">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox"
                                           {{ $e['is_active'] ? 'checked' : '' }}
                                           wire:change="updateRow('{{ $e['id'] }}', 'is_active', $event.target.checked)"
                                           class="w-4 h-4 accent-primary-600">
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                    <button wire:click="moveUp('{{ $e['id'] }}')"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30"
                                            {{ $loop->first ? 'disabled' : '' }}>
                                        <i class="ri-arrow-up-line"></i>
                                    </button>
                                    <button wire:click="moveDown('{{ $e['id'] }}')"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30"
                                            {{ $loop->last ? 'disabled' : '' }}>
                                        <i class="ri-arrow-down-line"></i>
                                    </button>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button wire:click="deleteRow('{{ $e['id'] }}')"
                                            wire:confirm="Bu sərfi silmək istədiyinizə əminsiniz?"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-slate-500 dark:text-slate-400">
                                    <i class="ri-inbox-line text-3xl block mb-2 opacity-40"></i>
                                    Henüz varsayılan sərf yok. Yukarıdan ekleyin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-3 rounded-lg bg-sky-50 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-900/50 text-xs text-sky-800 dark:text-sky-300">
                <i class="ri-information-line mr-1"></i>
                Bu sərflər satış formunda hazır seçenek olarak çıkar. Satış sırasında yeni sərf de eklenebilir.
            </div>
        </div>
    </div>
</div>
