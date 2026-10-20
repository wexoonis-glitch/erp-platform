<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public function login()
    {
        $this->validate();

        // RLS nedeniyle users tablosunu superadmin bağlamında oku
        $user = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => User::where('email', $this->email)->first(),
            superadmin: true
        );

        if (!$user || !Hash::check($this->password, $user->password)) {
            $this->addError('email', 'Kimlik bilgileri hatalı.');
            return;
        }

        if (!$user->isActive()) {
            $this->addError('email', 'Hesap aktif değil.');
            return;
        }

        if ($user->tenant_id) {
            $tenant = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => \App\Models\Tenant::find($user->tenant_id),
                superadmin: true
            );
            if (!$tenant || $tenant->status !== 'active') {
                $this->addError('email', 'Tenant aktif değil.');
                return;
            }
        }

        Auth::guard('web')->login($user);
        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.login');
    }
}
