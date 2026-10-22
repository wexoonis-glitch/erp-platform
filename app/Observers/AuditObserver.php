<?php

namespace App\Observers;

use App\Models\TenantModule;
use App\Services\Platform\AuditService;

final class AuditObserver
{
    public function __construct(private readonly AuditService $audit) {}

    public function created($model): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        $this->audit->record([
            'tenant_id'    => $this->tenantIdFor($model),
            'entity'       => $model->getTable(),
            'entity_id'    => $this->entityIdFor($model),
            'action'       => 'create',
            'after_state'  => $model->getAttributes(),
        ]);
    }

    public function updated($model): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $before = [];
        foreach ($changes as $key => $_) {
            $before[$key] = $model->getOriginal($key);
        }

        $this->audit->record([
            'tenant_id'    => $this->tenantIdFor($model),
            'entity'       => $model->getTable(),
            'entity_id'    => $this->entityIdFor($model),
            'action'       => 'update',
            'before_state' => $before,
            'after_state'  => $changes,
        ]);
    }

    public function deleted($model): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        $this->audit->record([
            'tenant_id'    => $this->tenantIdFor($model),
            'entity'       => $model->getTable(),
            'entity_id'    => $this->entityIdFor($model),
            'action'       => 'delete',
            'before_state' => $model->getAttributes(),
        ]);
    }

    private function shouldSkip(): bool
    {
        // Seeder/migration sırasında gürültü olmasın
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return true;
        }
        return false;
    }

    private function tenantIdFor($model): ?string
    {
        // Custom entity'lerde tenant_id var
        if (isset($model->tenant_id)) {
            return (string) $model->tenant_id;
        }
        return null;
    }

    private function entityIdFor($model): string
    {
        // Composite PK özel durumu
        if ($model instanceof TenantModule) {
            return $model->tenant_id . '|' . $model->module_id;
        }
        return (string) ($model->getKey() ?? '');
    }
}
