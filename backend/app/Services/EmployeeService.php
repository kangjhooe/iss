<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public function __construct(
        protected ProfilePhotoService $profilePhotoService
    ) {}
    /**
     * Get list of employees with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::query();

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nip', 'like', '%' . $search . '%')
                  ->orWhere('nuptk', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        $perPage = min($perPage, 100); // Max 100 per page

        return $query->select(['id', 'institution_id', 'type', 'nip', 'nuptk', 'name', 'gender', 'status', 'employment_status', 'created_at'])
            ->with('institution:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new employee.
     */
    public function create(array $data): Employee
    {
        $employee = Employee::create($data);

        Log::info('Employee created', [
            'employee_id' => $employee->id,
            'institution_id' => $employee->institution_id,
            'type' => $employee->type,
        ]);

        return $employee;
    }

    /**
     * Get employee by ID.
     */
    public function find(int $id): Employee
    {
        return Employee::with('institution')->findOrFail($id);
    }

    /**
     * Update employee.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        Log::info('Employee updated', [
            'employee_id' => $employee->id,
        ]);

        return $employee->fresh(['institution']);
    }

    /**
     * Delete employee (soft delete).
     */
    public function delete(Employee $employee): bool
    {
        $employeeId = $employee->id;
        $result = $employee->delete();

        Log::info('Employee deleted', [
            'employee_id' => $employeeId,
        ]);

        return $result;
    }

    public function storePhoto(Employee $employee, UploadedFile $file): Employee
    {
        $this->deletePhotoFile($employee);

        $path = $this->profilePhotoService->store($file, 'employee_photos/'.$employee->id);
        $employee->update(['photo_path' => $path]);

        Log::info('Employee photo uploaded', ['employee_id' => $employee->id]);

        return $employee->fresh(['institution', 'userAccount']);
    }

    public function deletePhoto(Employee $employee): Employee
    {
        $this->deletePhotoFile($employee);
        $employee->update(['photo_path' => null]);

        Log::info('Employee photo deleted', ['employee_id' => $employee->id]);

        return $employee->fresh(['institution', 'userAccount']);
    }

    public function deletePhotoFile(Employee $employee): void
    {
        $this->profilePhotoService->deletePath($employee->photo_path);
        $this->profilePhotoService->deleteDirectory('employee_photos/'.$employee->id);
    }
}
