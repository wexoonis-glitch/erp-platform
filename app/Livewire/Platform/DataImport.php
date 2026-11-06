<?php
namespace App\Livewire\Platform;

use App\Models\SysImportLog;
use App\Models\Tenant;
use App\Services\Platform\ImportExportService;
use App\Support\ImportSchemas;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class DataImport extends Component
{
    use WithFileUploads;

    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';
    public string $entity = 'products';
    public $file = null;

    public array $preview = [];
    public array $previewErrors = [];
    public ?string $message = null;
    public string $messageType = 'success';

    public function mount()
    {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedFile()
    {
        $this->preview = [];
        $this->previewErrors = [];
        $this->message = null;

        if (!$this->file) return;

        $this->validate(['file' => 'required|file|max:10240|mimes:csv,txt']);

        [$headers, $rows] = $this->parseCsv($this->file->getRealPath());

        // İlk 5 satırı önizleme için sakla
        $this->preview = array_slice($rows, 0, 5);

        // Şemaya göre kolon kontrolü
        $expected = ImportSchemas::headers($this->entity);
        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $headers);
        $missing = array_diff($expected, $normalizedHeaders);
        $this->previewErrors = $missing
            ? ['Eksik kolonlar: ' . implode(', ', $missing)]
            : [];

        // Sadece başlıkları tut
        $this->preview = [
            'headers' => $normalizedHeaders,
            'rows'    => $this->preview,
        ];
    }

    public function updatedEntity()
    {
        $this->file = null;
        $this->preview = [];
        $this->previewErrors = [];
        $this->message = null;
    }

    public function import()
    {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->file) return;

        $this->validate(['file' => 'required|file|max:10240|mimes:csv,txt']);

        [$headers, $rows] = $this->parseCsv($this->file->getRealPath());

        $log = null;
        TenantContext::runAs($tid, function () use ($headers, $rows, &$log) {
            $log = app(ImportExportService::class)->import(
                $this->entity,
                $headers,
                $rows,
                $this->file->getClientOriginalName()
            );
        });

        $this->messageType = $log->status === 'completed' ? 'success' : ($log->status === 'partial' ? 'warning' : 'error');
        $this->message = sprintf(
            'İçe aktarma bitti — Toplam: %d | Oluşturulan: %d | Güncellenen: %d | Hata: %d',
            $log->total_rows, $log->created_count, $log->updated_count, $log->error_count
        );

        $this->file = null;
        $this->preview = [];
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return [[], []];

        // BOM temizle
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
            $firstLine = substr($firstLine, 3);
        }
        rewind($handle);
        fwrite(fopen('php://memory', 'r+'), '');

        $headers = [];
        $rows = [];
        $lineNum = 0;
        $delimiter = $this->detectDelimiter($firstLine);

        rewind($handle);
        // BOM skip
        $firstChar = fgetc($handle);
        if ($firstChar !== "\xEF") rewind($handle);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNum++;
            if ($lineNum === 1) {
                $headers = $row;
                // BOM'u ilk hücreden temizle
                if (isset($headers[0])) {
                    $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
                }
                continue;
            }
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) continue;
            $rows[] = $row;
        }
        fclose($handle);

        return [$headers, $rows];
    }

    private function detectDelimiter(string $line): string
    {
        $semicolons = substr_count($line, ';');
        $commas = substr_count($line, ',');
        return $semicolons >= $commas ? ';' : ',';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $logs = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$logs) {
                $logs = SysImportLog::orderByDesc('created_at')->limit(20)->get()->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                true
            );
        }

        return view('livewire.platform.data-import', [
            'isSuper'  => $isSuper,
            'tenants'  => $tenants,
            'logs'     => $logs,
            'schemas'  => ImportSchemas::all(),
        ])->layout('layouts.app', ['title' => 'Veri İçe Aktarma']);
    }
}
