<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h2 class="page-title">Bütçe Planlama</h2><p class="page-subtitle">Masraf merkezleri için aylık bütçe girişi</p></div>
        <button wire:click="save" class="btn-primary">Kaydet</button>
    </div>
    @if($isSuper)
        <div class="card card-body"><label class="form-label">Tenant</label><select wire:model.live="selectedTenantId" class="form-select sm:max-w-md"><option value="">— Seçiniz —</option>@foreach($tenants as $t)<option value="{{ $t['id'] }}">{{ $t['code'] }}</option>@endforeach</select></div>
    @endif
    <div class="card card-body">
        <div class="flex items-end gap-4">
            <div><label class="form-label">Yıl</label><input type="text" wire:model.live="year" maxlength="4" class="form-input w-24"></div>
            @if($message) <div class="text-sm text-emerald-600">{{ $message }}</div> @endif
        </div>
    </div>

    @if(empty($centers))
        <div class="card p-8 text-center text-slate-500">Önce Finans → Masraf Merkezleri sayfasından bir masraf merkezi tanımla.</div>
    @else
        <div class="card overflow-x-auto">
            <table class="table-modern text-xs">
                <thead>
                    <tr>
                        <th class="sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">Masraf Merkezi</th>
                        @for($m = 1; $m <= 12; $m++)
                            <th>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($centers as $cc)
                        <tr>
                            <td class="sticky left-0 bg-white dark:bg-slate-900 text-left font-medium">{{ $cc['code'] }} — {{ $cc['name'] }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                @php $mm = str_pad($m, 2, '0', STR_PAD_LEFT); @endphp
                                <td class="p-1">
                                    <input type="number" step="0.01" wire:model="grid.{{ $cc['id'] }}.{{ $mm }}"
                                           class="form-input text-xs w-24 text-right" placeholder="0">
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
