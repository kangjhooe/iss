<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CorrespondenceImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CorrespondenceImportController extends Controller
{
    public function __construct(
        private CorrespondenceImportService $service
    ) {}

    /**
     * Import correspondence from Excel file.
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
            ], [
                'file.required' => 'File wajib diunggah',
                'file.file' => 'File tidak valid',
                'file.mimes' => 'Format file harus Excel (.xlsx atau .xls)',
                'file.max' => 'Ukuran file maksimal 10MB',
            ]);

            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Store uploaded file temporarily
            $file = $request->file('file');
            $filePath = $file->storeAs('imports', 'correspondence_import_' . time() . '.' . $file->getClientOriginalExtension(), 'public');

            // Import data
            $results = $this->service->importFromExcel($filePath, $institutionId, $request->user()->id);

            // Delete temporary file
            Storage::disk('public')->delete($filePath);

            return response()->json([
                'message' => 'Import selesai',
                'data' => $results,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to import correspondence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat mengimpor data',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download template Excel for import.
     */
    public function downloadTemplate()
    {
        try {
            $templatePath = 'templates/correspondence_import_template.xlsx';
            
            // Create template if not exists
            if (!Storage::disk('public')->exists($templatePath)) {
                $this->createTemplate($templatePath);
            }

            return Storage::disk('public')->download($templatePath, 'template_import_surat.xlsx');
        } catch (\Exception $e) {
            Log::error('Failed to download import template', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunduh template',
            ], 500);
        }
    }

    /**
     * Create Excel template file.
     */
    private function createTemplate(string $filePath): void
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array {
                return [
                    [
                        'masuk',
                        '01',
                        '',
                        'REF-001',
                        'Contoh Surat Masuk',
                        'Dari Instansi',
                        '',
                        '2026-01-15',
                        '2026-01-15',
                        'biasa',
                        'draft',
                        '',
                        'Keterangan contoh',
                    ],
                ];
            }

            public function headings(): array {
                return [
                    'Tipe',
                    'Jenis Surat (01-16)',
                    'Nomor Surat',
                    'Nomor Referensi',
                    'Perihal',
                    'Dari',
                    'Kepada',
                    'Tanggal Surat',
                    'Tanggal Terima',
                    'Prioritas',
                    'Status',
                    'Kategori',
                    'Keterangan',
                ];
            }
        };

        \Maatwebsite\Excel\Facades\Excel::store($export, $filePath, 'public');
    }
}
