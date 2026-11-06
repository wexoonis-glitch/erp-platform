<?php
namespace App\Http\Controllers;

use App\Models\SysCompanyProfile;
use Illuminate\Support\Facades\Storage;

class CompanyLogoController extends Controller {
    public function show() {
        $profile = SysCompanyProfile::where('tenant_id', \Auth::user()->tenant_id)->first();
        if (!$profile || !$profile->logo_path) {
            abort(404);
        }
        if (!Storage::disk('local')->exists($profile->logo_path)) {
            abort(404);
        }
        return Storage::disk('local')->response($profile->logo_path);
    }
}
