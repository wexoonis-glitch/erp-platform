<div class="space-y-6">
    <div>
        <h2 class="page-title">Zamanlanmış Görevler</h2>
        <p class="page-subtitle">Otomatik çalışan görevleri izleyin ve manuel tetikleyin.</p>
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
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400' }}">
            {{ $message }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($taskCatalog as $task)
            <div class="card p-5">
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $task['name'] }}</h3>
                    <p class="text-[11px] text-slate-500 mt-1">{{ $task['schedule'] }}</p>
                    <p class="text-[10px] text-slate-400 mt-1 font-mono truncate">
                        {{ $task['command'] }}@foreach(($task['args'] ?? []) as $k => $v) {{ $k }}={{ $v }}@endforeach
                    </p>
                </div>
                <button wire:click="runNow('{{ $task['code'] }}')" wire:loading.attr="disabled" wire:target="runNow" class="btn-primary mt-4 w-full">
                    Şimdi Çalıştır
                </button>
            </div>
        @endforeach
    </div>

    <div class="card p-4 bg-slate-50 dark:bg-slate-800/50">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Cron Kurulumu</h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">Sunucuda aşağıdaki cron satırının ekli olduğundan emin olun:</p>
        <pre class="text-[11px] bg-slate-900 text-emerald-400 p-3 rounded-lg overflow-x-auto">* * * * * cd /var/www/erp && php artisan schedule:run >> /dev/null 2>&1</pre>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold uppercase">Son Çalıştırmalar</h3>
        </div>
        <table class="table-modern">
            <thead>
                <tr><th>Görev</th><th>Başlangıç</th><th>Bitiş</th><th>Durum</th><th>Çıktı / Hata</th></tr>
            </thead>
            <tbody>
                @forelse($runs as $r)
                    <tr wire:key="run-{{ $r['id'] }}">
                        <td class="font-medium text-slate-900 dark:text-white text-sm">{{ $r['task_name'] }}</td>
                        <td class="text-xs">{{ $r['started_at'] }}</td>
                        <td class="text-xs">{{ $r['finished_at'] }}</td>
                        <td>
                            <span class="{{ $r['status'] === 'success' ? 'badge-success' : ($r['status'] === 'failed' ? 'badge-danger' : 'badge-warning') }}">
                                {{ $r['status'] }}
                            </span>
                        </td>
                        <td class="text-xs {{ $r['status'] === 'failed' ? 'text-rose-600' : 'text-slate-600 dark:text-slate-400' }}">
                            {{ $r['error'] ?: $r['output'] ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-slate-500">Henüz çalıştırma yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
