<div class="space-y-6">
    @if(!$viewingProjectId)
        {{-- PROJE LİSTESİ --}}
        <div class="flex items-center justify-between">
            <div><h2 class="page-title">Projeler</h2></div>
            <button wire:click="openCreate" class="btn-primary">+ Yeni Proje</button>
        </div>
        @if($isSuper)
            <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
        @endif
        @if($errors->any()) <div class="bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div> @endif

        @if($showForm)
            <div class="modal-backdrop" wire:click.self="cancel">
                <div class="modal-content max-w-2xl">
                    <div class="modal-header"><h3 class="text-base font-semibold">Yeni Proje</h3><button wire:click="cancel">X</button></div>
                    <form wire:submit="saveProject" class="modal-body">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="form-label">Proje Kodu</label><input type="text" wire:model="code" class="form-input" placeholder="PRJ-001">@error('code') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                            <div><label class="form-label">Müşteri (Opsiyonel)</label><select wire:model="customer_id" class="form-select"><option value="">— Seç —</option>@foreach($customers as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach</select></div>
                            <div class="col-span-2"><label class="form-label">Proje Adı</label><input type="text" wire:model="name" class="form-input">@error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                            <div><label class="form-label">Başlangıç</label><input type="date" wire:model="start_date" class="form-input"></div>
                            <div><label class="form-label">Bitiş</label><input type="date" wire:model="end_date" class="form-input"></div>
                            <div><label class="form-label">Bütçe (₺)</label><input type="number" wire:model="budget" class="form-input"></div>
                            <div class="col-span-2"><label class="form-label">Açıklama</label><textarea wire:model="description" rows="2" class="form-input"></textarea></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn-primary">Kaydet</button><button type="button" wire:click="cancel" class="btn-secondary">İptal</button></div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card overflow-hidden">
            <table class="table-modern">
                <thead><tr><th>Kod</th><th>Proje Adı</th><th>Müşteri</th><th>Bütçe</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                    @forelse($projects as $p)
                        <tr>
                            <td class="font-mono text-xs">{{ $p['code'] }}</td><td class="font-medium">{{ $p['name'] }}</td>
                            <td>{{ $p['customer']['name'] ?? 'İç Proje' }}</td><td class="font-mono">{{ number_format($p['budget'], 2) }} ₺</td>
                            <td><span class="badge-success">{{ $p['status'] }}</span></td>
                            <td><button wire:click="viewProject('{{ $p['id'] }}')" class="btn-ghost-primary">Detay / Görevler</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-6">Proje bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- PROJE DETAY (KANBAN GÖRÜNÜMÜ) --}}
        @if($details)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button wire:click="closeProject" class="btn-secondary text-xs px-2 py-1">← Geri</button>
                    <div>
                        <h2 class="page-title">{{ $details['name'] }}</h2>
                        <p class="page-subtitle">{{ $details['customer']['name'] ?? 'İç Proje' }} • Bütçe: {{ number_format($details['budget'], 2) }} ₺</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Görevler --}}
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Görevler</h3>
                        <button wire:click="$set('showForm', false)" class="btn-primary text-xs" x-data x-on:click="$dispatch('open-task-modal')">+ Görev Ekle</button>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach(['todo' => 'Yapılacak', 'in_progress' => 'Devam Ediyor', 'done' => 'Tamamlandı'] as $statusKey => $statusLabel)
                            <div class="bg-slate-100 dark:bg-slate-800/50 rounded-xl p-3">
                                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">{{ $statusLabel }}</h4>
                                <div class="space-y-2">
                                    @foreach(collect($details['tasks'])->where('status', $statusKey) as $t)
                                        <div class="bg-white dark:bg-slate-900 p-3 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                                            <div class="font-medium text-sm text-slate-900 dark:text-white">{{ $t['name'] }}</div>
                                            <div class="text-xs text-slate-500 mt-1">Son Tarih: {{ $t['due_date'] ?? '—' }}</div>
                                            <div class="mt-2 flex gap-1">
                                                @if($statusKey !== 'done') <button wire:click="updateTaskStatus('{{ $t['id'] }}', 'done')" class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-1 rounded">Bitti</button> @endif
                                                @if($statusKey === 'todo') <button wire:click="updateTaskStatus('{{ $t['id'] }}', 'in_progress')" class="text-[10px] bg-amber-100 text-amber-700 px-2 py-1 rounded">Başla</button> @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Giderler --}}
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Proje Giderleri</h3>
                        <button x-data x-on:click="$dispatch('open-expense-modal')" class="btn-secondary text-xs">+ Ekle</button>
                    </div>
                    <div class="card overflow-hidden">
                        <table class="table-modern">
                            <thead><tr><th>Tarih</th><th>Açıklama</th><th>Tutar</th></tr></thead>
                            <tbody>
                                @forelse($details['expenses'] as $e)
                                    <tr>
                                        <td class="text-xs">{{ $e['expense_date'] }}</td><td class="text-sm">{{ $e['description'] }}</td>
                                        <td class="font-mono text-xs">{{ number_format($e['amount'], 2) }} ₺</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-slate-500 text-xs">Gider yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Görev ve Gider Modalları (Livewire içinde basit Alpine.js ile) --}}
            <div x-data="{ showTask: false, showExpense: false }" @open-task-modal.window="showTask = true" @open-expense-modal.window="showExpense = true">
                {{-- Görev Modal --}}
                <div x-show="showTask" class="modal-backdrop" style="display:none;">
                    <div class="modal-content max-w-md" @click.away="showTask = false">
                        <div class="modal-header"><h3 class="text-base font-semibold">Yeni Görev</h3><button @click="showTask = false">X</button></div>
                        <div class="modal-body">
                            <div><label class="form-label">Görev Adı</label><input type="text" wire:model="task_name" class="form-input">@error('task_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                            <div><label class="form-label">Son Tarih</label><input type="date" wire:model="task_due_date" class="form-input"></div>
                            <div><label class="form-label">Tahmini Saat</label><input type="number" wire:model="task_estimated_hours" class="form-input"></div>
                            <div class="modal-footer"><button wire:click="addTask" @click="showTask = false" class="btn-primary">Görevi Ekle</button></div>
                        </div>
                    </div>
                </div>
                {{-- Gider Modal --}}
                <div x-show="showExpense" class="modal-backdrop" style="display:none;">
                    <div class="modal-content max-w-md" @click.away="showExpense = false">
                        <div class="modal-header"><h3 class="text-base font-semibold">Yeni Gider</h3><button @click="showExpense = false">X</button></div>
                        <div class="modal-body">
                            <div><label class="form-label">Tarih</label><input type="date" wire:model="expense_date" class="form-input"></div>
                            <div><label class="form-label">Açıklama</label><input type="text" wire:model="expense_description" class="form-input"></div>
                            <div><label class="form-label">Tutar</label><input type="number" wire:model="expense_amount" class="form-input">@error('expense_amount') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                            <div class="modal-footer"><button wire:click="addExpense" @click="showExpense = false" class="btn-primary">Gideri Ekle</button></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
