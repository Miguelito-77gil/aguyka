<?php

namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentRepository implements StudentRepositoryInterface
{
    /**
     * Get a paginated list of students, with optional filters.
     */
    public function all(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        // Deleted records live outside the normal query — start from onlyTrashed()
        if (($filters['status'] ?? null) === 'deleted') {
            $query = Student::onlyTrashed();
            unset($filters['status']);
        } else {
            $query = Student::query();
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['course'])) {
            $query->where('course', $filters['course']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['year_level'])) {
            $query->where('year_level', $filters['year_level']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a student by ID.
     */
    public function find(string $id): ?Student
    {
        return Student::find($id);
    }

    /**
     * Create a new student.
     */
    public function create(array $data): Student
    {
        return Student::create($data);
    }

    /**
     * Update an existing student.
     */
    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        return $student->fresh();
    }

    /**
     * Delete a student.
     */
    public function delete(Student $student): bool
    {
        return (bool) $student->delete();
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restore(string $id): ?Student
    {
        $student = Student::withTrashed()->find($id);

        if (!$student) {
            return null;
        }

        $student->restore();

        return $student->fresh();
    }

    /**
     * Permanently delete a soft-deleted student.
     */
    public function forceDelete(string $id): bool
    {
        $student = Student::withTrashed()->find($id);

        if (!$student) {
            return false;
        }

        return (bool) $student->forceDelete();
    }

    /**
     * Get student status statistics.
     */
    public function statistics(): array
    {
        return [
            'total_students'    => Student::count(),
            'active_students'   => Student::where('status', 'active')->count(),
            'inactive_students' => Student::where('status', 'inactive')->count(),
        ];
    }
}