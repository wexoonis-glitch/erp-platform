<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Zamanlanmış ERP Görevleri
|--------------------------------------------------------------------------
| Sunucuda cron: * * * * * cd /var/www/erp && php artisan schedule:run >> /dev/null 2>&1
| Veya elle: php artisan schedule:run
*/

Schedule::command('erp:depreciation-run')
    ->monthlyOn(1, '03:00')
    ->description('Aylık amortisman koşusu');

Schedule::command('erp:ar-reminders --days=3')
    ->dailyAt('09:00')
    ->description('Müşteri fatura hatırlatmaları');

Schedule::command('erp:bank-auto-reconcile')
    ->dailyAt('10:00')
    ->description('Banka ekstrelerinin otomatik eşleştirilmesi');

Schedule::command('queue:prune-failed --hours=168')
    ->weekly()
    ->description('Haftalık başarısız kuyruk temizliği');
