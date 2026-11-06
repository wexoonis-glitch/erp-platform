<?php
namespace App\Services\Platform;
use App\Models\SysDocument;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DocumentService {
    public function upload(UploadedFile $file, ?string $entityType = null, ?string $entityId = null): SysDocument {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        
        // Klasör yapısı: documents/{tenant_id}/{yil}/{ay}/
        $path = $file->storeAs(
            "documents/{$tenantId}/" . date('Y/m'), 
            $fileName, 
            'local'
        );

        return SysDocument::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'entity_type' => $entityType, 'entity_id' => $entityId,
            'name' => $originalName, 'file_path' => $path,
            'mime_type' => $file->getClientMimeType(), 'file_size' => $file->getSize(),
            'uploaded_by' => \Auth::id(),
        ]);
    }

    public function delete(string $id): void {
        $doc = SysDocument::findOrFail($id);
        if (Storage::disk('local')->exists($doc->file_path)) {
            Storage::disk('local')->delete($doc->file_path);
        }
        $doc->delete();
    }
}
