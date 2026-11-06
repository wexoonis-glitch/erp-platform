<?php
namespace App\Services\Platform;

use App\Models\SysImportLog;
use App\Support\ImportSchemas;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class ImportExportService
{
    /**
     * @param string $entityKey
     * @param array $headers   CSV başlık satırı
     * @param array $rows      Her satır bir array (sıralı veya keyed)
     * @param string|null $fileName
     * @return SysImportLog
     */
    public function import(string $entityKey, array $headers, array $rows, ?string $fileName = null): SysImportLog
    {
        $schema = ImportSchemas::get($entityKey);
        if (!$schema) {
            throw new \InvalidArgumentException("Bilinmeyen entity: {$entityKey}");
        }

        $tenantId = TenantContext::id();
        $userId = Auth::id();

        $created = 0; $updated = 0; $errors = []; $totalRows = count($rows);

        $model = $schema['model'];
        $uniqueBy = $schema['unique_by'];
        $defaults = $schema['defaults'] ?? [];
        $rules = $schema['columns'];

        // Kolon adlarını normalize et (küçük harf, trim)
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // başlık 1. satır

            // CSV'yi keyed hale getir
            $data = [];
            if (array_keys($row) === range(0, count($row) - 1)) {
                foreach ($headers as $idx => $col) {
                    $data[$col] = trim((string) ($row[$idx] ?? ''));
                }
            } else {
                foreach ($row as $k => $v) {
                    $data[strtolower(trim($k))] = trim((string) $v);
                }
            }

            // Boş satırları atla
            if (empty(array_filter($data, fn($v) => $v !== ''))) continue;

            // Default değerleri ekle
            foreach ($defaults as $k => $v) {
                if (!isset($data[$k]) || $data[$k] === '') $data[$k] = $v;
            }

            // Sadece şemada tanımlı alanları al
            $data = array_intersect_key($data, $rules);

            // track_stock normalize
            if (isset($data['track_stock'])) {
                $v = strtolower((string) $data['track_stock']);
                $data['track_stock'] = in_array($v, ['1', 'true', 'yes', 'evet'], true) ? 1 : 0;
            }

            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNum,
                    'messages' => $validator->errors()->all(),
                    'data' => $data,
                ];
                continue;
            }

            try {
                // Model'in özel işlemleri
                $payload = $this->preparePayload($model, $data);

                // Upsert
                $existing = $model::where($uniqueBy, $payload[$uniqueBy])->first();
                if ($existing) {
                    $existing->fill($payload)->save();
                    $updated++;
                } else {
                    $payload['id'] = (string) Str::uuid();
                    $payload['tenant_id'] = $tenantId;
                    $model::create($payload);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'messages' => [$e->getMessage()],
                    'data' => $data,
                ];
            }
        }

        $status = empty($errors) ? 'completed' : ($created + $updated > 0 ? 'partial' : 'failed');

        return SysImportLog::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenantId,
            'entity'        => $entityKey,
            'file_name'     => $fileName,
            'total_rows'    => $totalRows,
            'created_count' => $created,
            'updated_count' => $updated,
            'error_count'   => count($errors),
            'errors'        => array_slice($errors, 0, 100), // ilk 100 hata
            'status'        => $status,
            'user_id'       => $userId,
        ]);
    }

    /**
     * Model'e özgü varsayılanlar (hesap ID'leri gibi).
     */
    private function preparePayload(string $model, array $data): array
    {
        if ($model === \App\Models\InvProduct::class) {
            $data['revenue_account_id'] = $data['revenue_account_id']
                ?? \App\Models\FinAccount::where('code', '600')->value('id');
            $data['inventory_account_id'] = $data['inventory_account_id']
                ?? \App\Models\FinAccount::where('code', '153')->value('id');
            $data['cogs_account_id'] = $data['cogs_account_id']
                ?? \App\Models\FinAccount::where('code', '621')->value('id');

            if (empty($data['revenue_account_id'])) {
                throw new \RuntimeException('Gelir hesabı (600) tanımlı değil. Önce Hesap Planı seed edin.');
            }

            $data['cost_method'] = $data['cost_method'] ?? 'standard';
            $data['replacement_cost'] = $data['replacement_cost'] ?? ($data['purchase_price'] ?? 0);
        }

        return $data;
    }

    /**
     * Export için CSV stream (php://output).
     */
    public function export(string $entityKey)
    {
        $schema = ImportSchemas::get($entityKey);
        if (!$schema) throw new \InvalidArgumentException("Bilinmeyen entity: {$entityKey}");

        $headers = ImportSchemas::headers($entityKey);
        $model = $schema['model'];

        $rows = $model::select($headers)->orderBy($schema['unique_by'])->get();

        $filename = "{$entityKey}-" . date('Ymd-His') . '.csv';

        $callback = function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM (Excel için)
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $r) {
                $line = [];
                foreach ($headers as $h) {
                    $v = $r->{$h} ?? '';
                    if (is_bool($v)) $v = $v ? 1 : 0;
                    $line[] = $v;
                }
                fputcsv($out, $line, ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Boş şablon indir.
     */
    public function template(string $entityKey)
    {
        $schema = ImportSchemas::get($entityKey);
        if (!$schema) throw new \InvalidArgumentException("Bilinmeyen entity: {$entityKey}");

        $headers = ImportSchemas::headers($entityKey);
        $sample = $schema['sample'] ?? [];
        $filename = "{$entityKey}-template.csv";

        $callback = function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($sample as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
