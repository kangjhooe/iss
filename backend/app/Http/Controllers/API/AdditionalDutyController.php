<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdditionalDuty;
use Illuminate\Http\Request;

class AdditionalDutyController extends Controller
{
    /**
     * List all additional duties (master) with their permission keys.
     * For use in forms (e.g. assign tugas tambahan to teacher).
     */
    public function index(Request $request)
    {
        $duties = AdditionalDuty::query()
            ->with('permissions:id,key,label')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $data = $duties->map(function (AdditionalDuty $duty) {
            return [
                'id' => $duty->id,
                'key' => $duty->key,
                'label' => $duty->label,
                'description' => $duty->description,
                'sort_order' => $duty->sort_order,
                'permission_keys' => $duty->permissions->pluck('key')->values()->all(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
