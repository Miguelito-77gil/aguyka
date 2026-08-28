<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService
    ) {}

    /**
     * Display a paginated list of students.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'course', 'status', 'year_level']);
        $perPage = (int) $request->input('per_page', 10);

        $students = $this->studentService->getStudents($filters, $perPage);

        return response()->json([
            'data' => StudentResource::collection($students),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page'    => $students->lastPage(),
                'per_page'     => $students->perPage(),
                'total'        => $students->total(),
            ],
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->createStudent($request->validated());

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a single student.
     */
    public function show(string $id): JsonResponse
    {
        $student = $this->studentService->getStudent($id);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        return (new StudentResource($student))->response();
    }

    /**
     * Update an existing student.
     */
    public function update(UpdateStudentRequest $request, string $id): JsonResponse
    {
        $student = $this->studentService->getStudent($id);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        $updated = $this->studentService->updateStudent($student, $request->validated());

        return (new StudentResource($updated))->response();
    }

    /**
     * Delete a student.
     */
    public function destroy(string $id): JsonResponse
    {
        $student = $this->studentService->getStudent($id);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        $this->studentService->deleteStudent($student);

        return response()->json([
            'message' => 'Student deleted successfully.',
        ], 200);
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restore(string $id): JsonResponse
    {
        $student = $this->studentService->restoreStudent($id);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        return (new StudentResource($student))->response();
    }

    /**
     * Permanently delete a soft-deleted student.
     */
    public function forceDestroy(string $id): JsonResponse
    {
        $deleted = $this->studentService->forceDeleteStudent($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Student permanently deleted.',
        ], 200);
    }

    /**
     * Return student status statistics (bonus).
     */
    public function statistics(): JsonResponse
    {
        return response()->json($this->studentService->getStatistics());
    }
}