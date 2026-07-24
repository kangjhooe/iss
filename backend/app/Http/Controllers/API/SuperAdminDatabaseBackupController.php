<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuperAdminDatabaseBackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {}

    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * List stored database backups.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        return response()->json([
            'data' => $this->backupService->list(),
            'meta' => [
                'format' => 'sql.gz',
                'note' => 'Backup berisi struktur + data MySQL. File upload di storage tidak termasuk.',
            ],
        ]);
    }

    /**
     * Create a new database backup (.sql.gz) and keep it on the server.
     */
    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $meta = $this->backupService->create();

            Log::info('Database backup created', [
                'user_id' => $request->user()->id,
                'filename' => $meta['filename'],
                'size' => $meta['size'],
            ]);

            return response()->json([
                'message' => 'Backup database berhasil dibuat.',
                'data' => [
                    'filename' => $meta['filename'],
                    'size' => $meta['size'],
                    'created_at' => $meta['created_at'],
                ],
            ], 201);
        } catch (RuntimeException $e) {
            Log::warning('Database backup failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Database backup error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal membuat backup database.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download a stored backup file.
     */
    public function download(Request $request, string $filename): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $this->ensureSuperAdmin($request);

        try {
            $path = $this->backupService->absolutePath($filename);

            return response()->download($path, $filename, [
                'Content-Type' => str_ends_with($filename, '.gz')
                    ? 'application/gzip'
                    : 'application/sql',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Delete a stored backup file.
     */
    public function destroy(Request $request, string $filename)
    {
        $this->ensureSuperAdmin($request);

        try {
            $this->backupService->delete($filename);

            Log::info('Database backup deleted', [
                'user_id' => $request->user()->id,
                'filename' => $filename,
            ]);

            return response()->json([
                'message' => 'Backup berhasil dihapus.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
