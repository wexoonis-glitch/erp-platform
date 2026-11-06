<div class="space-y-6">
    <div>
        <h2 class="page-title">Veri Dışa Aktarma (CSV)</h2>
        <p class="page-subtitle">Mevcut verileri Excel/CSV olarak indirin.</p>
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($schemas as $key => $s)
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $s['label'] }}</h3>
                <p class="text-[11px] text-slate-500 mt-1">{{ count($s['columns']) }} kolon</p>
                <a href="{{ route('data.export', $key) }}" class="btn-primary mt-4 w-full">
                    📤 CSV İndir
                </a>
            </div>
        @endforeach
    </div>
</div>
