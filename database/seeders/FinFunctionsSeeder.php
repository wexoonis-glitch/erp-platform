<?php

namespace Database\Seeders;

use App\Models\FunctionRegistry;
use Illuminate\Database\Seeder;

class FinFunctionsSeeder extends Seeder
{
    public function run(): void
    {
        $fn = [
            ['FIN-GL-0010', 'GL', 'Journal entry oluşturma',           'CRUD'],
            ['FIN-GL-0018', 'GL', 'Borç/alacak dengesi doğrulama',     'VAL'],
            ['FIN-GL-0033', 'GL', 'Dönem kilitleme / yeniden açma',    'WF'],
            ['FIN-AP-0101', 'AP', 'Tedarikçi faturası kaydı',          'CRUD'],
            ['FIN-AP-0110', 'AP', 'Üçlü eşleştirme',                   'VAL'],
            ['FIN-AP-0135', 'AP', 'Ödeme önerisi üretme',              'BATCH'],
            ['FIN-AR-0201', 'AR', 'Müşteri faturası oluşturma',        'CRUD'],
            ['FIN-AR-0215', 'AR', 'Kısmi tahsilat mahsubu',            'CRUD'],
            ['FIN-CM-0415', 'CM', 'Banka ekstresi içe aktarma',        'INT'],
            ['FIN-TX-0515', 'TX', 'KDV hesaplama',                     'CALC'],
            ['FIN-CL-0701', 'CL', 'Kapanış checklist yürütme',         'WF'],
            ['FIN-CL-0725', 'CL', 'Kur revalüasyonu',                  'BATCH'],
        ];

        foreach ($fn as [$id, $sub, $name, $type]) {
            FunctionRegistry::updateOrCreate(
                ['id' => $id],
                [
                    'domain'     => 'FIN',
                    'subprocess' => $sub,
                    'name'       => $name,
                    'type'       => $type,
                    'version'    => '1.0.0',
                    'status'     => 'active',
                    'owner'      => 'Finans Ekibi',
                    'spec'       => ['note' => 'Initial FIN seed'],
                ]
            );
        }

        $this->command->info('FIN fonksiyonları eklendi: ' . count($fn));
    }
}
