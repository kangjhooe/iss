<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\AdditionalDuty;
use App\Services\StructuralDutySync;
use App\Support\VocationalAccess;
use Illuminate\Http\Request;

class AdditionalDutyController extends Controller
{
    use ResolvesInstitution;

    /**
     * List all additional duties (master) with their permission keys.
     * For use in forms (e.g. assign tugas tambahan to teacher).
     * Duty kejuruan (Kaprog, Bengkel, Hubin, PKL, BKK) hanya untuk SMK/MAK.
     */
    public function index(Request $request)
    {
        $query = AdditionalDuty::query()
            ->with('permissions:id,key,label')
            ->orderBy('sort_order')
            ->orderBy('label');

        $institutionId = $this->resolveInstitutionId($request);
        if ($institutionId && ! VocationalAccess::isVocationalInstitution($institutionId)) {
            $query->whereNotIn('key', VocationalAccess::DUTY_KEYS);
        }

        $duties = $query->get();

        $structural = app(StructuralDutySync::class);
        $data = $duties->map(function (AdditionalDuty $duty) use ($structural) {
            return [
                'id' => $duty->id,
                'key' => $duty->key,
                'label' => $duty->label,
                'description' => $duty->description,
                'sort_order' => $duty->sort_order,
                'is_structural' => $structural->isKey($duty->key),
                'permission_keys' => $duty->permissions->pluck('key')->values()->all(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
