<div class="space-y-6">
    <div>
        <h2 class="page-title">Veri İçe Aktarma (CSV)</h2>
        <p class="page-subtitle">Ürün, müşteri, tedarikçi listelerini toplu yükleyin.</p>
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
        <div class="rounded-xl p-3 text-sm border {{ $messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : ($messageType === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-rose-50 border-rose-200 text-rose-700') }}">
            {{ $message }}
        </div>
    @endif

    <div class="card card-body space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Veri Türü</label>
                <select wire:model.live="entity" class="form-select">
                    @foreach($schemas as $key => $s)
                        <option value="{{ $key }}">{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Şablon İndir</label>
                <a href="{{ route('data.template', $entity) }}" class="btn-secondary w-full">
                    📥 {{ $schemas[$entity]['label'] ?? 'Şablon' }} Şablonu
                </a>
            </div>
        </div>

        <div>
            <label class="form-label">CSV Dosyası</label>
            <input type="file" wire:model="file" accept=".csv,.txt" class="form-input text-sm">
            <div wire:loading wire:target="file" class="text-xs text-primary-600 mt-1">Dosya yükleniyor...</div>
            @error('file') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-slate-500 mt-1">
                Ayraç: <code>;</code> (noktalı virgül) veya <code>,</code> (virgül). UTF-8 kodlama desteklenir.
                Maks 10MB.
            </p>
        </div>

        @if(!empty($previewErrors))
            <div class="rounded-xl p-3 bg-rose-50 border border-rose-200 text-sm text-rose-700">
                <strong>Uyarı:</strong>
                @foreach($previewErrors as $err){{ $err }}@endforeach
            </div>
        @endif

        @if(!empty($preview) && isset($preview['headers']))
            <div>
                <h4 class="text-sm font-semibold mb-2">Önizleme (ilk 5 satır)</h4>
                <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-lg">
                    <table class="table-modern text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                @foreach($preview['headers'] as $h)
                                    <th>{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['rows'] as $row)
                                <tr>
                                    @foreach($preview['headers'] as $i => $h)
                                        <td>{{ $row[$i] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button wire:click="import" wire:loading.attr="disabled" wire:target="import"
                        class="btn-primary mt-4">
                    <span wire:loading.remove wire:target="import">İçe Aktar ({{ count($preview['rows']) }} satır)</span>
                    <span wire:loading wire:target="import">İşleniyor...</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Geçmiş --}}
    @if(!empty($logs))
        <div class="card overflow-hidden">
            <div class="card-header bg-slate-50 dark:bg-slate-800/50">
                <h3 class="text-sm font-bold uppercase">Son İçe Aktarmalar</h3>
            </div>
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Entity</th>
                        <th>Dosya</th>
                        <th>Toplam</th>
                        <th>Oluşturulan</th>
                        <th>Güncellenen</th>
                        <th>Hata</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="text-xs">{{ \Carbon\Carbon::parse($log['created_at'])->format('d.m.Y H:i') }}</td>
                            <td class="text-xs font-mono">{{ $log['entity'] }}</td>
                            <td class="text-xs">{{ $log['file_name'] ?? '—' }}</td>
                            <td class="font-mono">{{ $log['total_rows'] }}</td>
                            <td class="font-mono text-emerald-600">{{ $log['created_count'] }}</td>
                            <td class="font-mono text-sky-600">{{ $log['updated_count'] }}</td>
                            <td class="font-mono text-rose-600">{{ $log['error_count'] }}</td>
                            <td>
                                <span class="{{ $log['status'] === 'completed' ? 'badge-success' : ($log['status'] === 'partial' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ $log['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
