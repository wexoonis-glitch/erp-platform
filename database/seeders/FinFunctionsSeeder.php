<?php

namespace Database\Seeders;

use App\Models\FunctionRegistry;
use Illuminate\Database\Seeder;

class FinFunctionsSeeder extends Seeder
{
    public function run(): void
    {
        $fn = [
            // COA
            ['FIN-COA-0010', 'COA', 'Hesap oluşturma',                        'CRUD'],
            ['FIN-COA-0020', 'COA', 'Hesap güncelleme',                       'CRUD'],
            ['FIN-COA-0030', 'COA', 'Hesap silme',                            'CRUD'],
            ['FIN-COA-0040', 'COA', 'Hesap bakiyesi hesaplama',               'CALC'],

            // Yevmiye
            ['FIN-JE-0101',  'JE',  'Yevmiye kaydı oluşturma',                'CRUD'],
            ['FIN-JE-0110',  'JE',  'Borç=Alacak denge doğrulama',            'VAL'],
            ['FIN-JE-0120',  'JE',  'Yevmiye kaydı post etme',                'WF'],
            ['FIN-JE-0130',  'JE',  'Yevmiye kaydı ters kayıt',               'WF'],
            ['FIN-JE-0140',  'JE',  'Otomatik yevmiye no üretimi',            'CALC'],

            // Genel Muhasebe
            ['FIN-GL-0010',  'GL',  'Mizan (trial balance)',                  'RPT'],
            ['FIN-GL-0020',  'GL',  'Hesap defteri (ledger)',                 'RPT'],

            // AP — Tedarikçi
            ['FIN-SUP-0010', 'SUP', 'Tedarikçi oluşturma',                    'CRUD'],
            ['FIN-SUP-0020', 'SUP', 'Tedarikçi güncelleme',                   'CRUD'],
            ['FIN-SUP-0030', 'SUP', 'Tedarikçi silme',                        'CRUD'],
            ['FIN-SUP-0040', 'SUP', 'Tedarikçi bakiye hesaplama',             'CALC'],

            // AP — Fatura
            ['FIN-AP-0101',  'AP',  'Tedarikçi faturası oluşturma',           'CRUD'],
            ['FIN-AP-0110',  'AP',  'Tedarikçi faturası post etme',           'WF'],
            ['FIN-AP-0120',  'AP',  'Tedarikçi faturası silme (draft)',       'CRUD'],
            ['FIN-AP-0130',  'AP',  'Otomatik yevmiye oluşturma (fatura)',    'INT'],
            ['FIN-AP-0140',  'AP',  'Fatura yaşlandırma (aging)',             'RPT'],

            // AP — Ödeme
            ['FIN-PAY-0010', 'PAY', 'Tedarikçi ödemesi oluşturma',            'CRUD'],
            ['FIN-PAY-0020', 'PAY', 'Ödeme post etme',                        'WF'],
            ['FIN-PAY-0030', 'PAY', 'Otomatik yevmiye oluşturma (ödeme)',     'INT'],
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
                    'spec'       => ['note' => 'Step 19B seed'],
                ]
            );
        }

        $this->command->info('FIN fonksiyonları güncellendi: ' . count($fn));
    }
}
