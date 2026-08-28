<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StudentService
{
    /**
     * The minimum age required to register a student.
     */
    private const MINIMUM_AGE = 15;

    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository
    ) {}

    /**
     * Get a paginated list of students, with optional filters.
     */
    public function getStudents(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->studentRepository->all($filters, $perPage);
    }

    /**
     * Get a single student by ID.
     */
    public function getStudent(string $id): ?Student
    {
        return $this->studentRepository->find($id);
    }

    /**
     * Create a new student.
     */
    public function createStudent(array $data): Student
    {
        $this->assertMeetsMinimumAge($data['age']);

        return $this->studentRepository->create($data);
    }

    /**
     * Update an existing student.
     */
    public function updateStudent(Student $student, array $data): Student
    {
        if (array_key_exists('age', $data)) {
            $this->assertMeetsMinimumAge($data['age']);
        }

        return $this->studentRepository->update($student, $data);
    }

    /**
     * Delete a student.
     */
    public function deleteStudent(Student $student): bool
    {
        return $this->studentRepository->delete($student);
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restoreStudent(string $id): ?Student
    {
        return $this->studentRepository->restore($id);
    }

    /**
     * Permanently delete a soft-deleted student.
     */
    public function forceDeleteStudent(string $id): bool
    {
        return $this->studentRepository->forceDelete($id);
    }

    /**
     * Get student status statistics.
     */
    public function getStatistics(): array
    {
        return $this->studentRepository->statistics();
    }

    /**
     * Business rule: a student must be at least MINIMUM_AGE years old.
     */
    private function assertMeetsMinimumAge(int $age): void
    {
        if ($age < self::MINIMUM_AGE) {
            throw ValidationException::withMessages([
                'age' => 'A student must be at least ' . self::MINIMUM_AGE . ' years old to be registered.',
            ]);
        }
    }
}