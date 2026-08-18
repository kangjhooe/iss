<?php

namespace App\Services;

use App\Models\UksMedicine;
use App\Models\UksMedicineTransaction;
use App\Models\UksVisit;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UksMedicineService
{
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = UksMedicine::forInstitution($institutionId)
            ->orderBy('name');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['low_stock'])) {
            $query->whereNotNull('min_stock')
                ->whereColumn('quantity', '<=', 'min_stock');
        }

        if (!empty($filters['expired'])) {
            $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', Carbon::today());
        }

        return $query->paginate($perPage);
    }

    public function create(int $institutionId, array $data): UksMedicine
    {
        return UksMedicine::create([
            'institution_id' => $institutionId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'quantity' => (int) ($data['quantity'] ?? 0),
            'min_stock' => isset($data['min_stock']) ? (int) $data['min_stock'] : null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(UksMedicine $medicine, array $data): UksMedicine
    {
        $medicine->fill(collect($data)->only([
            'name', 'code', 'unit', 'min_stock', 'expiry_date', 'description', 'is_active',
        ])->all());

        // Stok hanya diubah lewat transaksi (kecuali create awal).
        $medicine->save();

        return $medicine->fresh();
    }

    public function delete(UksMedicine $medicine): void
    {
        if ($medicine->transactions()->exists()) {
            $medicine->update(['is_active' => false]);

            return;
        }

        $medicine->delete();
    }

    public function recordTransaction(int $institutionId, array $data, int $userId): UksMedicineTransaction
    {
        return DB::transaction(function () use ($institutionId, $data, $userId) {
            $medicine = UksMedicine::forInstitution($institutionId)
                ->where('id', $data['uks_medicine_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $type = $data['type'];
            $qty = (int) $data['quantity'];

            if ($qty < 0) {
                throw new InvalidArgumentException('Jumlah tidak boleh negatif.');
            }

            if (!empty($data['uks_visit_id'])) {
                UksVisit::forInstitution($institutionId)
                    ->where('id', $data['uks_visit_id'])
                    ->firstOrFail();
            }

            if ($type === 'masuk') {
                $medicine->increment('quantity', $qty);
            } elseif ($type === 'keluar') {
                if ($medicine->quantity < $qty) {
                    throw new InvalidArgumentException('Stok obat tidak mencukupi.');
                }
                $medicine->decrement('quantity', $qty);
            } elseif ($type === 'penyesuaian') {
                $medicine->update(['quantity' => $qty]);
            } else {
                throw new InvalidArgumentException('Jenis transaksi tidak valid.');
            }

            return UksMedicineTransaction::create([
                'institution_id' => $institutionId,
                'uks_medicine_id' => $medicine->id,
                'type' => $type,
                'quantity' => $qty,
                'transaction_date' => $data['transaction_date'] ?? Carbon::today()->format('Y-m-d'),
                'notes' => $data['notes'] ?? null,
                'uks_visit_id' => $data['uks_visit_id'] ?? null,
                'created_by' => $userId,
            ])->load(['medicine', 'creator:id,name', 'visit:id,visit_date,student_id']);
        });
    }

    public function listTransactions(int $institutionId, array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = UksMedicineTransaction::with([
            'medicine:id,name,code,unit',
            'creator:id,name',
            'visit:id,visit_date,student_id',
        ])
            ->forInstitution($institutionId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if (!empty($filters['uks_medicine_id'])) {
            $query->where('uks_medicine_id', $filters['uks_medicine_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return array{total_items:int,active_items:int,low_stock:int,expired:int,total_quantity:int}
     */
    public function getStockSummary(int $institutionId): array
    {
        $base = UksMedicine::forInstitution($institutionId);

        return [
            'total_items' => (int) (clone $base)->count(),
            'active_items' => (int) (clone $base)->where('is_active', true)->count(),
            'low_stock' => (int) (clone $base)->where('is_active', true)
                ->whereNotNull('min_stock')
                ->whereColumn('quantity', '<=', 'min_stock')
                ->count(),
            'expired' => (int) (clone $base)->where('is_active', true)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', Carbon::today())
                ->count(),
            'total_quantity' => (int) (clone $base)->where('is_active', true)->sum('quantity'),
        ];
    }
}
