<?php
namespace App\Livewire\SaaS;
use App\Models\SaasPlan;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Str;

class Plans extends Component {
    public bool $showForm = false;
    public string $name = '', $price = '0', $billing_cycle = 'monthly';

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->showForm = false; }

    public function save() {
        $this->validate(['name' => 'required', 'price' => 'required|numeric|min:0']);
        SaasPlan::create([
            'id' => (string) Str::uuid(), 'name' => $this->name, 'price' => (float)$this->price,
            'billing_cycle' => $this->billing_cycle, 'features' => [], 'status' => 'active',
        ]);
        $this->cancel();
    }

    private function resetForm() {
        $this->reset(['name']); $this->price = '0'; $this->billing_cycle = 'monthly';
    }

    #[Layout('layouts.app')]
    public function render() {
        $plans = SaasPlan::orderBy('price')->get()->toArray();
        return view('livewire.saas.plans', compact('plans'))->layout('layouts.app', ['title' => 'Abonelik Paketleri']);
    }
}
