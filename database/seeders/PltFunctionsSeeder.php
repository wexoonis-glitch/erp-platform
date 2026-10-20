<?php

namespace Database\Seeders;

use App\Models\FunctionRegistry;
use Illuminate\Database\Seeder;

class PltFunctionsSeeder extends Seeder
{
    public function run(): void
    {
        $fn = [
            // [id, subprocess, name, type]
            ['PLT-TEN-0001', 'TEN', 'Yeni tenant oluşturma',                     'CRUD'],
            ['PLT-TEN-0002', 'TEN', 'Tenant güncelleme',                         'CRUD'],
            ['PLT-TEN-0003', 'TEN', 'Tenant askıya alma / arşivleme',            'WF'],
            ['PLT-TEN-0004', 'TEN', 'Tenant konfigürasyon paketi içe aktar',     'CFG'],

            ['PLT-IAM-0021', 'IAM', 'Rol–yetki matrisi tanımlama',               'CFG'],
            ['PLT-IAM-0022', 'IAM', 'Kullanıcı rol atama',                       'CRUD'],
            ['PLT-IAM-0023', 'IAM', 'Görevler ayrılığı (SoD) çakışma tespiti',   'VAL'],

            ['PLT-MTD-0051', 'MTD', 'Özel alan (custom field) tanımlama',        'CFG'],
            ['PLT-MTD-0052', 'MTD', 'Özel nesne (custom object) tanımlama',      'CFG'],
            ['PLT-MTD-0053', 'MTD', 'Form/ekran düzeni sürümleme',               'CFG'],

            ['PLT-WF-0081',  'WF',  'Onay akışı tasarlama (BPMN)',               'CFG'],
            ['PLT-WF-0082',  'WF',  'Onay akışı başlatma',                       'WF'],
            ['PLT-WF-0083',  'WF',  'Delegasyon / vekil atama',                  'WF'],
            ['PLT-WF-0084',  'WF',  'Eskalasyon kuralı çalıştırma',              'BATCH'],

            ['PLT-RUL-0101', 'RUL', 'İş kuralı tanımlama (koşul → aksiyon)',     'CFG'],
            ['PLT-RUL-0102', 'RUL', 'Kural simülasyonu (dry-run)',               'VAL'],

            ['PLT-DOC-0121', 'DOC', 'Belge şablonu tanımlama',                   'CFG'],
            ['PLT-DOC-0122', 'DOC', 'Belge üretimi (PDF)',                       'DOC'],

            ['PLT-NTF-0151', 'NTF', 'Bildirim kanalı yapılandırma',              'CFG'],
            ['PLT-NTF-0152', 'NTF', 'Bildirim gönderme (mail/SMS/push)',         'INT'],

            ['PLT-AUD-0191', 'AUD', 'Audit trail sorgulama',                     'RPT'],
            ['PLT-AUD-0192', 'AUD', 'Alan bazlı değişiklik geçmişi',             'RPT'],

            ['PLT-PLG-0201', 'PLG', 'Plugin kaydı ve manifest doğrulama',        'CFG'],
            ['PLT-PLG-0202', 'PLG', 'Plugin etkinleştirme / devre dışı bırakma', 'CFG'],

            ['PLT-FLG-0221', 'FLG', 'Feature flag tanımlama ve rollout',         'CFG'],
        ];

        foreach ($fn as [$id, $sub, $name, $type]) {
            FunctionRegistry::updateOrCreate(
                ['id' => $id],
                [
                    'domain'     => 'PLT',
                    'subprocess' => $sub,
                    'name'       => $name,
                    'type'       => $type,
                    'version'    => '1.0.0',
                    'status'     => 'active',
                    'owner'      => 'Platform Ekibi',
                    'spec'       => ['note' => 'Initial seed'],
                ]
            );
        }

        $this->command->info('PLT fonksiyonları eklendi: ' . count($fn));
    }
}
