<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('custom-objects.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">← Custom Objects</a>
            <h2 class="page-title mt-1">{{ $object['label'] }}</h2>
            <p class="page-subtitle">{{ count($records) }} kayıt · <code class="font-mono text-xs">{{ $object['code'] }}</code></p>
        </div>
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kayıt
        </button>
    </div>

    {{-- Form Modal --}}
    @if($showForm)
        <div class="modal-backdrop" wire:click.self="cancel">
            <div class="modal-content max-w-xl">
                <div class="modal-header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ $editingRecordId ? 'Kayıt Düzenle' : 'Yeni Kayıt' }}
                    </h3>
                    <button wire:click="cancel" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    @error('saveError')
                        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-lg p-3 text-sm text-rose-700 dark:text-rose-400">
                            {{ $message }}
                        </div>
                    @enderror

                    @foreach($object['schema']['fields'] as $f)
                        @php
                            $code = $f['code'];
                            $type = $f['data_type'];
                            $required = (bool) ($f['required'] ?? false);
                        @endphp
                        <div>
                            <label class="form-label">
                                {{ $f['label'] }}
                                @if($required)<span class="text-rose-500">*</span>@endif
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">({{ $type }})</span>
                            </label>

                            @if($type === 'string')
                                <input type="text" wire:model="recordData.{{ $code }}" class="form-input">
                            @elseif($type === 'number')
                                <input type="number" wire:model="recordData.{{ $code }}" step="any" class="form-input">
                            @elseif($type === 'date')
                                <input type="date" wire:model="recordData.{{ $code }}" class="form-input">
                            @elseif($type === 'bool')
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" wire:model="recordData.{{ $code }}" class="rounded border-slate-300 dark:border-slate-700 text-primary-600">
                                    Evet
                                </label>
                            @elseif($type === 'enum')
                                <select wire:model="recordData.{{ $code }}" class="form-select">
                                    <option value="">— Seçiniz —</option>
                                    @foreach($f['options'] ?? [] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @elseif($type === 'json')
                                <textarea wire:model="recordData.{{ $code }}" rows="3" class="form-textarea"></textarea>
                            @endif

                            @error("recordData.{$code}")
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="modal-footer">
                        <button wire:click="save" class="btn-primary">Kaydet</button>
                        <button wire:click="cancel" class="btn-secondary">İptal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        @foreach($object['schema']['fields'] as $f)
                            <th>{{ $f['label'] }}</th>
                        @endforeach
                        <th>Oluşturuldu</th>
                        <th class="text-center">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                        <tr wire:key="rec-{{ $r['id'] }}">
                            @foreach($object['schema']['fields'] as $f)
                                @php $v = $r['data'][$f['code']] ?? null; @endphp
                                <td>
                                    @if($v === null || $v === '')
                                        <span class="text-slate-400 dark:text-slate-600">—</span>
                                    @elseif(is_bool($v))
                                        {{ $v ? '✓' : '✗' }}
                                    @elseif(is_array($v))
                                        <code class="text-xs font-mono">{{ json_encode($v) }}</code>
                                    @else
                                        {{ $v }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-xs text-slate-500 dark:text-slate-400">{{ $r['created_at'] }}</td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="openEdit('{{ $r['id'] }}')" class="btn-ghost-primary">Düzenle</button>
                                    <button wire:click="delete('{{ $r['id'] }}')"
                                            wire:confirm="Kaydı silmek istediğinize emin misiniz?"
                                            class="btn-ghost-danger">Sil</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($object['schema']['fields']) + 2 }}" class="text-center py-12">
                            <div class="text-4xl mb-2">📋</div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Henüz kayıt yok.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
