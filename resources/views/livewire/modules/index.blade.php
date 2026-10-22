<div class="space-y-6">
    <div>
        <h2 class="page-title">Modüller</h2>
        <p class="page-subtitle">{{ count($catalog) }} katalog modülü</p>
    </div>

    @if($isSuper)
        <div class="card card-body">
            <label class="form-label">İşlem yapılacak tenant</label>
            <select wire:model.live="selectedTenantId" class="form-select sm:max-w-md">
                <option value="">— Seçiniz —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t['id'] }}">{{ $t['code'] }} — {{ $t['name'] }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($errors->has('general'))
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-sm text-rose-700 dark:text-rose-400">
            {{ $errors->first('general') }}
        </div>
    @endif

    @if(session('message'))
        <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900 rounded-xl p-3 text-sm text-emerald-700 dark:text-emerald-400">
            {{ session('message') }}
        </div>
    @endif

    <div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-3">Katalog</h3>
        <div class="card overflow-hidden">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Modül</th>
                        <th>Fonksiyon</th>
                        <th>Kurulu mu?</th>
                        <th class="text-center">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($catalog as $m)
                        <tr wire:key="cat-{{ $m['module_id'] }}">
                            <td>
                                <span class="inline-flex items-center gap-2 font-mono font-semibold text-slate-900 dark:text-white">
                                    <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                                    {{ $m['module_id'] }}
                                </span>
                            </td>
                            <td class="font-medium">{{ $m['function_count'] }}</td>
                            <td>
                                @if(in_array($m['module_id'], $installedCodes, true))
                                    <span class="badge-success">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Kurulu
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isSuper)
                                    @if(in_array($m['module_id'], $installedCodes, true))
                                        @if($m['module_id'] !== 'PLT')
                                            <button wire:click="uninstall('{{ $m['module_id'] }}')"
                                                    wire:confirm="Modülü kaldırmak istediğinize emin misiniz?"
                                                    class="btn-ghost-danger">Kaldır</button>
                                        @else
                                            <span class="text-xs text-slate-400 dark:text-slate-500">Zorunlu</span>
                                        @endif
                                    @else
                                        <button wire:click="install('{{ $m['module_id'] }}')"
                                                class="btn-ghost-primary">Kur</button>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(!empty($installed))
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-3">Kurulu Modüller</h3>
            <div class="card overflow-hidden">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Modül</th>
                            <th>Versiyon</th>
                            <th>Durum</th>
                            <th>Kurulum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($installed as $i)
                            <tr wire:key="inst-{{ $i['module_id'] }}">
                                <td>
                                    <span class="inline-flex items-center gap-2 font-mono font-semibold text-slate-900 dark:text-white">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        {{ $i['module_id'] }}
                                    </span>
                                </td>
                                <td class="text-sm">{{ $i['version'] }}</td>
                                <td>
                                    <span class="badge-success">
                                        <span class="badge-dot bg-emerald-500"></span>
                                        {{ $i['status'] }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500 dark:text-slate-400">{{ $i['installed_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
