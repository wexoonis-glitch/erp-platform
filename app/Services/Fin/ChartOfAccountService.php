<?php

namespace App\Services\Fin;

use App\Models\FinAccount;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ChartOfAccountService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data): FinAccount
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $this->validateType($data['type'] ?? '');
        $this->validateCode($data['code'] ?? '');

        if (FinAccount::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
        }

        if (!empty($data['parent_id'])) {
            $parent = FinAccount::find($data['parent_id']);
            if (!$parent) {
                throw ValidationException::withMessages(['parent_id' => 'Üst hesap bulunamadı.']);
            }
            if ($parent->type !== $data['type']) {
                throw ValidationException::withMessages(['parent_id' => 'Üst hesapla tip uyuşmuyor.']);
            }
            if ($parent->is_postable) {
                throw ValidationException::withMessages(['parent_id' => 'Üst hesap postable olamaz; önce alt hesap yapın.']);
            }
        }

        $account = FinAccount::create([
            'id'          => (string) Str::uuid(),
            'tenant_id'   => $tenantId,
            'code'        => $data['code'],
            'name'        => $data['name'],
            'type'        => $data['type'],
            'parent_id'   => $data['parent_id'] ?? null,
            'is_postable' => (bool) ($data['is_postable'] ?? true),
            'currency'    => $data['currency'] ?? 'TRY',
            'status'      => $data['status'] ?? 'active',
            'description' => $data['description'] ?? null,
        ]);

        $this->audit->record([
            'tenant_id'   => $tenantId,
            'entity'      => 'fin_account',
            'entity_id'   => $account->id,
            'action'      => 'create',
            'after_state' => $account->toArray(),
        ]);

        return $account;
    }

    public function update(string $id, array $data): FinAccount
    {
        $account = FinAccount::findOrFail($id);

        if (isset($data['type']) && $data['type'] !== $account->type) {
            throw ValidationException::withMessages(['type' => 'Hesap tipi değiştirilemez.']);
        }
        if (isset($data['code']) && $data['code'] !== $account->code) {
            if (FinAccount::where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
            }
        }

        $before = $account->toArray();
        $account->fill(array_intersect_key($data, array_flip([
            'code', 'name', 'parent_id', 'is_postable', 'currency', 'status', 'description',
        ])));
        $account->save();

        $this->audit->record([
            'tenant_id'    => $account->tenant_id,
            'entity'       => 'fin_account',
            'entity_id'    => $account->id,
            'action'       => 'update',
            'before_state' => $before,
            'after_state'  => $account->fresh()->toArray(),
        ]);

        return $account->fresh();
    }

    public function delete(string $id): void
    {
        $account = FinAccount::findOrFail($id);

        if ($account->lines()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'Hesabın hareketleri var, silinemez.',
            ]);
        }
        if ($account->children()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'Alt hesapları var, silinemez.',
            ]);
        }

        $before = $account->toArray();
        $account->delete();

        $this->audit->record([
            'tenant_id'    => $account->tenant_id,
            'entity'       => 'fin_account',
            'entity_id'    => $account->id,
            'action'       => 'delete',
            'before_state' => $before,
        ]);
    }

    public function treeForTenant(): array
    {
        $accounts = FinAccount::orderBy('code')->get()->toArray();
        return $this->buildTree($accounts);
    }

    private function buildTree(array $items, ?string $parentId = null, int $depth = 0): array
    {
        $out = [];
        foreach ($items as $item) {
            if ($item['parent_id'] === $parentId) {
                $children = $this->buildTree($items, $item['id'], $depth + 1);
                $item['_depth'] = $depth;
                $item['_children'] = $children;
                $out[] = $item;
            }
        }
        return $out;
    }

    private function validateType(string $type): void
    {
        if (!in_array($type, FinAccount::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Geçersiz hesap tipi.']);
        }
    }

    private function validateCode(string $code): void
    {
        if (!preg_match('/^[0-9]{3,10}$/', $code)) {
            throw ValidationException::withMessages([
                'code' => 'Kod 3-10 haneli olmalı (sadece rakam).',
            ]);
        }
    }
}
