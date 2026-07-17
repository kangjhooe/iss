<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicReleaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = min((int) $request->get('per_page', 50), 100);

            $items = AppRelease::query()
                ->published()
                ->orderByDesc('released_at')
                ->orderByDesc('id')
                ->paginate($perPage);

            return response()->json([
                'data' => $items->getCollection()->map(fn (AppRelease $r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'version' => $r->version,
                    'released_at' => $r->released_at?->format('Y-m-d'),
                    'items' => $r->items ?? [],
                    'published_at' => $r->published_at?->toIso8601String(),
                ]),
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list public releases', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil catatan rilis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
