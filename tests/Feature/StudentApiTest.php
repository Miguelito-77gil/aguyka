<?php

use App\Models\Student;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(), 'sanctum');
});

function validStudentPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'John',
        'last_name'  => 'Doe',
        'email'      => 'john@example.com',
        'age'        => 20,
        'course'     => 'Computer Science',
        'year_level' => 2,
        'status'     => 'active',
    ], $overrides);
}

// ─── CREATE ───────────────────────────────────────────────

test('successfully creates a student', function () {
    $response = $this->postJson('/api/students', validStudentPayload());

    $response->assertStatus(201)
        ->assertJsonPath('data.first_name', 'John')
        ->assertJsonPath('data.email', 'john@example.com');

    $this->assertDatabaseHas('students', [
        'email' => 'john@example.com',
    ]);
});

test('rejects invalid data when creating a student', function () {
    $response = $this->postJson('/api/students', [
        'first_name' => 'John',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['last_name', 'email', 'age', 'course', 'year_level', 'status']);
});

test('rejects duplicate email addresses', function () {
    Student::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/students', validStudentPayload());

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('rejects a student under the minimum age', function () {
    $response = $this->postJson('/api/students', validStudentPayload(['age' => 10]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['age']);
});

// ─── READ ─────────────────────────────────────────────────

test('returns a paginated student list', function () {
    Student::factory()->count(15)->create();

    $response = $this->getJson('/api/students?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 15);
});

test('returns a specific student', function () {
    $student = Student::factory()->create();

    $response = $this->getJson("/api/students/{$student->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $student->id);
});

test('returns 404 for a non-existent student', function () {
    $response = $this->getJson('/api/students/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
});

// ─── UPDATE ───────────────────────────────────────────────

test('successfully updates a student', function () {
    $student = Student::factory()->create();

    $response = $this->putJson("/api/students/{$student->id}", [
        'first_name' => 'Updated',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.first_name', 'Updated');

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'first_name' => 'Updated',
    ]);
});

test('validates invalid update data', function () {
    $student = Student::factory()->create();

    $response = $this->putJson("/api/students/{$student->id}", [
        'age' => 5,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['age']);
});

test('allows a student to retain their existing email on update', function () {
    $student = Student::factory()->create(['email' => 'keep@example.com']);

    $response = $this->putJson("/api/students/{$student->id}", [
        'email' => 'keep@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.email', 'keep@example.com');
});

// ─── DELETE ───────────────────────────────────────────────

test('successfully deletes a student', function () {
    $student = Student::factory()->create();

    $response = $this->deleteJson("/api/students/{$student->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('students', [
        'id' => $student->id,
    ]);
});

test('handles deleting a non-existent student appropriately', function () {
    $response = $this->deleteJson('/api/students/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
});

// ─── ARCHITECTURE TEST ────────────────────────────────────

test('StudentRepositoryInterface resolves to StudentRepository via the container', function () {
    $repository = app(\App\Repositories\Contracts\StudentRepositoryInterface::class);

    expect($repository)->toBeInstanceOf(\App\Repositories\StudentRepository::class);
});