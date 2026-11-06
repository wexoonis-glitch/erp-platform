<?php
namespace App\Http\Controllers;

use App\Services\Platform\ImportExportService;

class ImportExportController extends Controller
{
    public function __construct(private readonly ImportExportService $svc) {}

    public function export(string $entity)
    {
        return $this->svc->export($entity);
    }

    public function template(string $entity)
    {
        return $this->svc->template($entity);
    }
}
