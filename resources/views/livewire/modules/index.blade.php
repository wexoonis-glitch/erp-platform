<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Modüller</h2>
            <p class="mt-1 text-slate-600">{{ count($catalog) }} katalog modülü</p>
        </div>
    </div>

    @if($isSuper)
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">İşlem yapılacak tenant</label>
            <select wire:model.live="selectedTenantId"
                    class="w-full sm:w-96 px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($errors->has('general'))
        <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-sm text-rose-700">
            {{ $errors->first('general') }}
        </div>
    @endif

    {{-- Katalog --}}
    <div>
        <h3 class="text-lg font-semibold text-slate-900 mb-3">Katalog</h3>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Modül</th>
                        <th class="px-6 py-3">Fonksiyon</th>
                        <th class="px-6 py-3">Kurulu mu?</th>
                        <th class="px-6 py-3 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($catalog as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-mono font-medium text-slate-900">{{ $m['module_id'] }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $m['function_count'] }}</td>
                            <td class="px-6 py-3">
                                @if(in_array($m['module_id'], $installedCodes, true))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        ✓ Kurulu
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if($isSuper)
                                    @if(in_array($m['module_id'], $installedCodes, true))
                                        @if($m['module_id'] !== 'PLT')
                                            <button wire:click="uninstall('{{ $m['module_id'] }}')"
                                                    class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                                Kaldır
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400">Zorunlu</span>
                                        @endif
                                    @else
                                        <button wire:click="install('{{ $m['module_id'] }}')"
                                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                            Kur
                                        </button>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kurulu Modüller --}}
    @if(!empty($installed))
        <div>
            <h3 class="text-lg font-semibold text-slate-900 mb-3">Kurulu Modüller</h3>
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Modül</th>
                            <th class="px-6 py-3">Versiyon</th>
                            <th class="px-6 py-3">Durum</th>
                            <th class="px-6 py-3">Kurulum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($installed as $i)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-mono font-medium text-slate-900">{{ $i['module_id'] }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $i['version'] }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $i['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">{{ $i['installed_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
