<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SubjectService
{
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator|Collection
    {
        $query = Subject::forInstitution($institutionId)
            ->orderBy('code');

        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->active();
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['per_page']) && $filters['per_page'] === 'all') {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    public function create(int $institutionId, array $data): Subject
    {
        $data['institution_id'] = $institutionId;
        $data['is_active'] = $data['is_active'] ?? true;
        return Subject::create($data);
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($data);
        return $subject->fresh();
    }
}
