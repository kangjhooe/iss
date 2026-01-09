<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    /**
     * Get all records.
     */
    public function all(): Collection;

    /**
     * Find record by ID.
     */
    public function find(int $id): ?Model;

    /**
     * Find record by ID or fail.
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update a record.
     */
    public function update(Model $model, array $data): Model;

    /**
     * Delete a record.
     */
    public function delete(Model $model): bool;

    /**
     * Get paginated records.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
