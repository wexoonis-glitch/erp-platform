<?php
namespace App\Livewire\Settings;

use App\Services\Fin\SarfiyyatService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SarfiyyatSettings extends Component
{
    public bool $show_in_sales = true;
    public bool $required_in_sale = false;
    public bool $charge_customer_default = false;

    /** @var array<int, array{id:string,name:string,default_amount:float,is_active:bool,sort_order:int}> */
    public array $expenses = [];

    /** Yeni sərf formu */
    public string $new_name = '';
    public string $new_amount = '';
    public ?string $message = null;
    public string $messageType = 'success';

    public function mount(SarfiyyatService $svc): void
    {
        $config = $svc->getConfig();
        $this->show_in_sales           = (bool) ($config['show_in_sales'] ?? true);
        $this->required_in_sale        = (bool) ($config['required_in_sale'] ?? false);
        $this->charge_customer_default = (bool) ($config['charge_customer_default'] ?? false);

        $this->reloadExpenses($svc);
    }

    private function reloadExpenses(SarfiyyatService $svc): void
    {
        $this->expenses = $svc->defaultExpenses()->map(fn($e) => [
            'id'             => $e->id,
            'name'           => $e->name,
            'default_amount' => (float) $e->default_amount,
            'is_active'      => (bool) $e->is_active,
            'sort_order'     => (int) $e->sort_order,
        ])->all();
    }

    public function saveConfig(SarfiyyatService $svc): void
    {
        $svc->saveConfig([
            'show_in_sales'           => $this->show_in_sales,
            'required_in_sale'        => $this->required_in_sale,
            'charge_customer_default' => $this->charge_customer_default,
        ]);

        $this->messageType = 'success';
        $this->message = 'Ayarlar kaydedildi.';
        $this->dispatch('$refresh');
    }

    public function addExpense(SarfiyyatService $svc): void
    {
        $name = trim($this->new_name);
        if ($name === '') {
            $this->messageType = 'error';
            $this->message = 'Sərf adı boş olamaz.';
            return;
        }

        $amount = (float) str_replace(',', '.', $this->new_amount ?: '0');
        if ($amount < 0) $amount = 0;

        $order = count($this->expenses);
        $svc->addDefault($name, $amount, $order);

        $this->new_name = '';
        $this->new_amount = '';
        $this->reloadExpenses($svc);

        $this->messageType = 'success';
        $this->message = 'Sərf eklendi.';
    }

    public function updateRow(string $id, string $field, mixed $value, SarfiyyatService $svc): void
    {
        $data = [];
        if ($field === 'name') {
            $data['name'] = (string) $value;
        } elseif ($field === 'default_amount') {
            $data['default_amount'] = (float) str_replace(',', '.', (string) $value);
        } elseif ($field === 'is_active') {
            $data['is_active'] = (bool) $value;
        } elseif ($field === 'sort_order') {
            $data['sort_order'] = (int) $value;
        } else {
            return;
        }

        $svc->updateDefault($id, $data);
        $this->reloadExpenses($svc);
    }

    public function deleteRow(string $id, SarfiyyatService $svc): void
    {
        $svc->deleteDefault($id);
        $this->reloadExpenses($svc);
        $this->messageType = 'info';
        $this->message = 'Sərf silindi.';
    }

    public function moveUp(string $id, SarfiyyatService $svc): void
    {
        $idx = null;
        foreach ($this->expenses as $i => $row) {
            if ($row['id'] === $id) { $idx = $i; break; }
        }
        if ($idx === null || $idx === 0) return;

        $prev = $this->expenses[$idx - 1];
        $svc->updateDefault($id,    ['sort_order' => $prev['sort_order']]);
        $svc->updateDefault($prev['id'], ['sort_order' => $this->expenses[$idx]['sort_order']]);
        $this->reloadExpenses($svc);
    }

    public function moveDown(string $id, SarfiyyatService $svc): void
    {
        $idx = null;
        foreach ($this->expenses as $i => $row) {
            if ($row['id'] === $id) { $idx = $i; break; }
        }
        if ($idx === null || $idx === count($this->expenses) - 1) return;

        $next = $this->expenses[$idx + 1];
        $svc->updateDefault($id,          ['sort_order' => $next['sort_order']]);
        $svc->updateDefault($next['id'],  ['sort_order' => $this->expenses[$idx]['sort_order']]);
        $this->reloadExpenses($svc);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.settings.sarfiyyat-settings')
            ->layout('layouts.app', ['title' => 'Sərfiyyat Ayarları']);
    }
}
