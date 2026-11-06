<?php
namespace App\Http\Controllers;

use App\Models\SysDocument;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller {
    public function download(string $id) {
        $doc = SysDocument::findOrFail($id);

        // RLS zaten koruyor ama ek bir güvenlik katmanı
        if (!\Auth::user()->isSuperadmin() && $doc->tenant_id !== \Auth::user()->tenant_id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'Dosya sistemde bulunamadı.');
        }

        return Storage::disk('local')->download($doc->file_path, $doc->name);
    }
}
