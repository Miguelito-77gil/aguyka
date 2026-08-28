<?php
namespace App\Repositories\Contracts;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
interface StudentRepositoryInterface
{
    /**
     * Get a paginated list of students, with optional filters.
     */
    public function all(array $filters = [], int $perPage = 10): LengthAwarePaginator;
    /**
     * Find a student by ID.
     */
    public function find(string $id): ?Student;
    /**
     * Create a new student.
     */
    public function create(array $data): Student;
    /**
     * Update an existing student.
     */
    public function update(Student $student, array $data): Student;
    /**
     * Delete a student.
     */
    public function delete(Student $student): bool;
    /**
     * Restore a soft-deleted student.
     */
    public function restore(string $id): ?Student;
    /**
     * Permanently delete a soft-deleted student.
     */
    public function forceDelete(string $id): bool;
    /**
     * Get student status statistics.
     */
    public function statistics(): array;
}