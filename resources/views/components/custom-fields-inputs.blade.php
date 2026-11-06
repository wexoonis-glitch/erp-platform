@props(['entity', 'model' => 'customFields'])
@php
    $fields = \App\Models\CustomField::forEntity($entity);
@endphp
@if($fields->isNotEmpty())
    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
        <h4 class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mb-3">Özel Alanlar</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($fields as $f)
                @php $key = "{$model}.{$f->code}"; @endphp
                <div>
                    <label class="form-label">
                        {{ $f->label }}
                        @if($f->config['required'] ?? false)<span class="text-rose-500">*</span>@endif
                    </label>
                    @switch($f->data_type)
                        @case('number')
                            <input type="number" step="any" wire:model="{{ $key }}" class="form-input">
                            @break
                        @case('date')
                            <input type="date" wire:model="{{ $key }}" class="form-input">
                            @break
                        @case('bool')
                            <label class="flex items-center gap-2 text-sm mt-2">
                                <input type="checkbox" wire:model="{{ $key }}" class="rounded"> Evet
                            </label>
                            @break
                        @case('enum')
                            <select wire:model="{{ $key }}" class="form-select">
                                <option value="">— Seçiniz —</option>
                                @foreach(($f->config['options'] ?? []) as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                            @break
                        @case('json')
                            <textarea wire:model="{{ $key }}" rows="2" class="form-input font-mono text-xs"></textarea>
                            @break
                        @default
                            <input type="text" wire:model="{{ $key }}" class="form-input">
                    @endswitch
                    @error($key) <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </div>
@endif
