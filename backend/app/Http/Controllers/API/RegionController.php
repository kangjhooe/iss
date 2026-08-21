<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\RegionLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RegionController extends Controller
{
    public function provinces(): JsonResponse
    {
        return $this->respond(fn (RegionLookupService $service) => $service->provinces());
    }

    public function regencies(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('province_code', ''));

        return $this->respond(fn (RegionLookupService $service) => $service->regencies($code));
    }

    public function districts(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('regency_code', ''));

        return $this->respond(fn (RegionLookupService $service) => $service->districts($code));
    }

    public function villages(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('district_code', ''));

        return $this->respond(fn (RegionLookupService $service) => $service->villages($code));
    }

    /**
     * @param  callable(RegionLookupService): list<array{code: string, name: string}>  $callback
     */
    protected function respond(callable $callback): JsonResponse
    {
        try {
            $data = $callback(RegionLookupService::fromConfig());

            return response()->json(['data' => $data]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => [],
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Data wilayah sedang tidak tersedia. Isi alamat secara manual.',
                'data' => [],
            ], 503);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Data wilayah sedang tidak tersedia. Isi alamat secara manual.',
                'data' => [],
            ], 503);
        }
    }
}
