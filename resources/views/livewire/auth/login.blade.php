<div>
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white text-2xl font-bold mb-4">
            ◆
        </div>
        <h1 class="text-2xl font-bold text-white">Kurumsal ERP</h1>
        <p class="mt-1 text-sm text-slate-400">Hesabınıza giriş yapın</p>
    </div>

    <form wire:submit="login" class="bg-slate-800/60 backdrop-blur rounded-2xl p-6 sm:p-8 border border-slate-700/50 shadow-2xl space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">E-posta</label>
            <input id="email" type="email" wire:model="email" autofocus autocomplete="email"
                   class="w-full px-3.5 py-2.5 rounded-lg bg-slate-900/70 border border-slate-700 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                   placeholder="admin@erp.local">
            @error('email') <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">Parola</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                   class="w-full px-3.5 py-2.5 rounded-lg bg-slate-900/70 border border-slate-700 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                   placeholder="••••••••">
            @error('password') <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove>Giriş Yap</span>
            <span wire:loading>Giriş yapılıyor...</span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500">
        Demo: admin@erp.local / Passw0rd!
    </p>
</div>
