<?php
/**
 * FEATURE REGISTRY — Tüm özelliklerin tek kaynağı
 *
 * Yeni flag = buraya blok ekle. Migration gerekmez.
 * Admin UI otomatik buradan okur.
 *
 * Alanlar:
 *   label       : Görünen isim (admin panelinde)
 *   group       : Hangi kategoride görünsün
 *   type        : bool | int | text
 *   default     : Sistem varsayılanı
 *   requires    : Bağımlı olduğu diğer flag'ler
 *   description : Açıklama
 *   order       : Aynı grupta sıralama
 */
return [

    // ═══════════════════════════════════════════════════════
    // MODÜLLER
    // ═══════════════════════════════════════════════════════
    'fin.enabled' => [
        'label' => 'Finans', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 10,
        'description' => 'Muhasebe, banka, AR/AP, cari',
    ],
    'otc.enabled' => [
        'label' => 'Satış', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 20,
        'description' => 'Satış siparişleri, faturalar',
    ],
    'inv.enabled' => [
        'label' => 'Envanter', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 30,
        'description' => 'Ürün, stok, depo',
    ],
    'prc.enabled' => [
        'label' => 'Satın Alma', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 40,
        'description' => 'PO, mal kabul, 3-way match',
    ],
    'mfg.enabled' => [
        'label' => 'Üretim', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 50,
        'description' => 'BOM, üretim emri, MRP',
    ],
    'hrm.enabled' => [
        'label' => 'İnsan Kaynakları', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 60,
        'description' => 'Personel, bordro, izin',
    ],
    'crm.enabled' => [
        'label' => 'CRM', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 70,
        'description' => 'Potansiyel müşteri, fırsat',
    ],
    'ppm.enabled' => [
        'label' => 'Projeler', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 80,
    ],
    'eam.enabled' => [
        'label' => 'Varlık Yönetimi', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 90,
    ],
    'qm.enabled' => [
        'label' => 'Kalite', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 100,
    ],
    'log.enabled' => [
        'label' => 'Lojistik', 'group' => 'modules', 'type' => 'bool',
        'default' => true, 'requires' => [], 'order' => 110,
    ],

    // ═══════════════════════════════════════════════════════
    // SATIŞ ALT ÖZELLİKLERİ
    // ═══════════════════════════════════════════════════════
    'otc.sarfiyyat' => [
        'label' => 'Sərfiyyat (Satış Masrafları)', 'group' => 'otc',
        'type' => 'bool', 'default' => true,
        'requires' => ['otc.enabled'], 'order' => 10,
        'description' => 'Satışa masraf ekle, kârdan düş veya müşteriye yansıt',
    ],
    'otc.sarfiyyat.required' => [
        'label' => 'Sərfiyyat zorunlu', 'group' => 'otc',
        'type' => 'bool', 'default' => false,
        'requires' => ['otc.sarfiyyat'], 'order' => 11,
        'description' => 'En az 1 sərf girilmeden satış kaydedilemez',
    ],
    'otc.sarfiyyat.charge_customer_default' => [
        'label' => 'Sərfiyyat: varsayılan müşteri öder', 'group' => 'otc',
        'type' => 'bool', 'default' => false,
        'requires' => ['otc.sarfiyyat'], 'order' => 12,
    ],
    'otc.karma_payment' => [
        'label' => 'Karma Ödeme', 'group' => 'otc',
        'type' => 'bool', 'default' => true,
        'requires' => ['otc.enabled'], 'order' => 20,
        'description' => 'Tek satışta nakit + açık hesap',
    ],
    'otc.gift_product' => [
        'label' => 'Hədiyyə Ürün', 'group' => 'otc',
        'type' => 'bool', 'default' => true,
        'requires' => ['inv.enabled'], 'order' => 30,
        'description' => '0 maliyetli stok girişi',
    ],
    'otc.returns' => [
        'label' => 'Satış İadesi', 'group' => 'otc',
        'type' => 'bool', 'default' => true,
        'requires' => ['otc.enabled'], 'order' => 40,
        'description' => 'Ürün bazlı iade',
    ],
    'otc.manual_supplier_debt' => [
        'label' => 'Manuel Tedarikçi Borcu', 'group' => 'otc',
        'type' => 'bool', 'default' => false,
        'requires' => ['fin.enabled'], 'order' => 50,
        'description' => 'Stok girişinde tedarikçi borcunu elle gir',
    ],

    // ═══════════════════════════════════════════════════════
    // UX
    // ═══════════════════════════════════════════════════════
    'ux.command_palette' => [
        'label' => 'Hızlı Arama (Ctrl+K)', 'group' => 'ux',
        'type' => 'bool', 'default' => true,
        'requires' => [], 'order' => 10,
    ],
    'ux.favorites' => [
        'label' => 'Favoriler', 'group' => 'ux',
        'type' => 'bool', 'default' => true,
        'requires' => [], 'order' => 20,
    ],
    'ux.undo' => [
        'label' => 'Geri Al (Undo)', 'group' => 'ux',
        'type' => 'bool', 'default' => true,
        'requires' => [], 'order' => 30,
    ],
    'ux.undo.duration_seconds' => [
        'label' => 'Geri alma süresi (sn)', 'group' => 'ux',
        'type' => 'int', 'default' => 60,
        'requires' => ['ux.undo'], 'order' => 31,
        'min' => 10, 'max' => 600,
    ],

    // ═══════════════════════════════════════════════════════
    // ANALİTİK
    // ═══════════════════════════════════════════════════════
    'analytics.abc' => [
        'label' => 'ABC Analizi', 'group' => 'analytics',
        'type' => 'bool', 'default' => true,
        'requires' => ['otc.enabled'], 'order' => 10,
    ],
    'analytics.combo' => [
        'label' => 'Combo Analizi', 'group' => 'analytics',
        'type' => 'bool', 'default' => true,
        'requires' => ['otc.enabled'], 'order' => 20,
    ],
    'analytics.forecast' => [
        'label' => 'Satış Tahmini', 'group' => 'analytics',
        'type' => 'bool', 'default' => false,
        'requires' => ['otc.enabled'], 'order' => 30,
    ],

    // ═══════════════════════════════════════════════════════
    // GÜVENLİK
    // ═══════════════════════════════════════════════════════
    'security.2fa' => [
        'label' => 'İki Faktörlü Doğrulama (TOTP)', 'group' => 'security',
        'type' => 'bool', 'default' => false,
        'requires' => [], 'order' => 10,
        'description' => 'Google Authenticator uyumlu',
    ],
    'security.ip_whitelist' => [
        'label' => 'IP Kısıtlaması', 'group' => 'security',
        'type' => 'bool', 'default' => false,
        'requires' => [], 'order' => 20,
    ],
];
