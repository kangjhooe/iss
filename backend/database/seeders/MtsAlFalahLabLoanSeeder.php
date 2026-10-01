<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Peminjaman alat Lab Komputer — proyektor, laptop, webcam.
 * ±25 catatan (kebanyakan sudah dikembalikan).
 *
 *   php artisan db:seed --class=MtsAlFalahLabLoanSeeder
 */
class MtsAlFalahLabLoanSeeder extends Seeder
{
    public const NPSN = '10816663';

    public const SEED_MARKER = 'SEED:LAB-LOAN-ALFALAH';

    public function run(): void
    {
        $institution = Institution::where('npsn', self::NPSN)->first();
        if (! $institution) {
            $this->command?->error('Institution NPSN '.self::NPSN.' tidak ditemukan.');

            return;
        }

        $room = Room::where('institution_id', $institution->id)
            ->where('type', 'Laboratorium')
            ->where('lab_type', 'Komputer')
            ->first();
        if (! $room) {
            $this->command?->error('Ruang Lab Komputer belum ada.');

            return;
        }

        $laptop = InventoryItem::where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('name', 'like', '%Laptop%')
            ->first();
        $proyektor = InventoryItem::where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('name', 'like', '%Proyektor%')
            ->first();
        $webcam = InventoryItem::where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('name', 'like', '%Webcam%')
            ->first();

        if (! $laptop || ! $proyektor || ! $webcam) {
            $this->command?->error('Item Laptop/Proyektor/Webcam lab belum ada. Jalankan MtsAlFalahLabInventorySeeder dulu.');

            return;
        }

        $teachers = Employee::where('institution_id', $institution->id)
            ->orderBy('id')
            ->get();
        if ($teachers->isEmpty()) {
            $this->command?->error('Tidak ada employee untuk peminjam.');

            return;
        }

        $admin = User::where('institution_id', $institution->id)->orderBy('id')->first()
            ?? User::orderBy('id')->first();
        if (! $admin) {
            $this->command?->error('User admin tidak ditemukan.');

            return;
        }

        InventoryLoan::withTrashed()
            ->where('institution_id', $institution->id)
            ->where('notes', 'like', '%'.self::SEED_MARKER.'%')
            ->forceDelete();

        $purposes = [
            'Presentasi materi di kelas',
            'Rapat guru & OSIS',
            'Ujian praktik / CBT',
            'Pelatihan guru',
            'Dokumentasi kegiatan sekolah',
            'Lomba / ekstrakurikuler',
            'Sosialisasi program sekolah',
            'Pembelajaran multimedia',
            'Zoom / pertemuan daring',
            'Persiapan akreditasi',
        ];

        /**
         * 25 peminjaman: laptop sering, proyektor sering, webcam sesekali.
         * Tanggal relatif ke "hari ini" (2026-09-19 konteks lokal) — pakai Carbon::now().
         *
         * @var list<array{item:string,days_ago:int,duration:int,qty:int,status:string}>
         */
        $defs = [
            // Laptop (15)
            ['item' => 'laptop', 'days_ago' => 120, 'duration' => 3, 'qty' => 2, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 110, 'duration' => 2, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 98,  'duration' => 5, 'qty' => 3, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 90,  'duration' => 1, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 82,  'duration' => 4, 'qty' => 2, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 75,  'duration' => 2, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 68,  'duration' => 3, 'qty' => 2, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 55,  'duration' => 7, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 48,  'duration' => 2, 'qty' => 2, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 40,  'duration' => 3, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 32,  'duration' => 1, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 22,  'duration' => 4, 'qty' => 2, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 14,  'duration' => 3, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'laptop', 'days_ago' => 5,   'duration' => 7, 'qty' => 2, 'status' => 'Dipinjam'],
            ['item' => 'laptop', 'days_ago' => 10,  'duration' => 3, 'qty' => 1, 'status' => 'Terlambat'],

            // Proyektor (7)
            ['item' => 'proyektor', 'days_ago' => 115, 'duration' => 1, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'proyektor', 'days_ago' => 95,  'duration' => 2, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'proyektor', 'days_ago' => 70,  'duration' => 1, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'proyektor', 'days_ago' => 50,  'duration' => 3, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'proyektor', 'days_ago' => 35,  'duration' => 1, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'proyektor', 'days_ago' => 18,  'duration' => 2, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'proyektor', 'days_ago' => 2,   'duration' => 5, 'qty' => 1, 'status' => 'Dipinjam'],

            // Webcam sesekali (3)
            ['item' => 'webcam', 'days_ago' => 100, 'duration' => 2, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'webcam', 'days_ago' => 45,  'duration' => 1, 'qty' => 1, 'status' => 'Dikembalikan'],
            ['item' => 'webcam', 'days_ago' => 8,   'duration' => 4, 'qty' => 1, 'status' => 'Dipinjam'],
        ];

        $itemMap = [
            'laptop' => $laptop,
            'proyektor' => $proyektor,
            'webcam' => $webcam,
        ];

        $created = 0;
        $counts = ['laptop' => 0, 'proyektor' => 0, 'webcam' => 0];

        foreach ($defs as $i => $def) {
            $item = $itemMap[$def['item']];
            $teacher = $teachers[$i % $teachers->count()];
            $loanDate = Carbon::now()->subDays($def['days_ago'])->startOfDay();
            $expected = $loanDate->copy()->addDays($def['duration']);
            $status = $def['status'];

            $actualReturn = null;
            $returnCondition = null;
            if ($status === 'Dikembalikan') {
                $actualReturn = $expected->copy()->subHours(2)->toDateString();
                $returnCondition = 'Baik';
            } elseif ($status === 'Terlambat') {
                // masih aktif, lewat jatuh tempo
                $actualReturn = null;
            }

            InventoryLoan::create([
                'institution_id' => $institution->id,
                'item_id' => $item->id,
                'asset_id' => null,
                'borrower_type' => 'Employee',
                'borrower_id' => $teacher->id,
                'borrower_name' => $teacher->name,
                'borrower_phone' => $teacher->phone,
                'loan_date' => $loanDate->toDateString(),
                'expected_return_date' => $expected->toDateString(),
                'actual_return_date' => $actualReturn,
                'quantity' => $def['qty'],
                'purpose' => $purposes[$i % count($purposes)],
                'status' => $status,
                'return_condition' => $returnCondition,
                'notes' => self::SEED_MARKER.' | '.$def['item'],
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            $created++;
            $counts[$def['item']]++;
        }

        $this->command?->info(sprintf(
            'Lab loans seeded: %d (laptop %d, proyektor %d, webcam %d). Refresh tab Peminjaman.',
            $created,
            $counts['laptop'],
            $counts['proyektor'],
            $counts['webcam']
        ));
    }
}
