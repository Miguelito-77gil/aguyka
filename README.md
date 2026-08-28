# Student Management API

A RESTful API built with Laravel using **Layered Architecture**, allowing administrators to manage student records.

## Architecture

---

## Installation

### 1. Install PHP dependencies
```bash
composer install
```

### 2. Configure environment
```bash
copy .env.example .env
php artisan key:generate
```

Ensure your `.env` has:

### 3. Create the database
```bash
type nul > database\database.sqlite
```

### 4. Run migrations
```bash
php artisan migrate
```

### 5. Run tests
```bash
php artisan test
```

### 6. Start the application
```bash
composer run dev
```

The API will be available at `http://127.0.0.1:8000`.

---

## API Documentation

### Create Student
- **Method:** `POST`
- **URL:** `/api/students`
- **Purpose:** Create a new student record

**Request body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "age": 20,
    "course": "Computer Science",
    "year_level": 2,
    "status": "active"
}
```

**Validation rules:**
| Field | Rules |
|---|---|
| first_name | required, string, max:100 |
| last_name | required, string, max:100 |
| email | required, valid email, unique |
| age | required, integer, min:15 |
| course | required, string, max:100 |
| year_level | required, integer, between 1–4 |
| status | required, `active` or `inactive` |

**Success response (201):**
```json
{
    "data": {
        "id": "uuid",
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "age": 20,
        "course": "Computer Science",
        "year_level": 2,
        "status": "active",
        "created_at": "...",
        "updated_at": "..."
    }
}
```

**Possible errors:**
- `422` — validation failed (missing fields, duplicate email, age under 15)

---

### List Students
- **Method:** `GET`
- **URL:** `/api/students`
- **Purpose:** Retrieve a paginated list of students

**Query parameters:**
| Param | Description |
|---|---|
| per_page | Number of results per page (default: 10) |
| search | Matches first_name, last_name, or email |
| course | Filter by course |
| status | Filter by `active`/`inactive` |
| year_level | Filter by year level |

**Example:**


**Success response (200):**
```json
{
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 10,
        "total": 25
    }
}
```

---

### Show Student
- **Method:** `GET`
- **URL:** `/api/students/{id}`
- **Purpose:** Retrieve a single student

**Success response (200):** Student object wrapped in `data`.

**Possible errors:**
- `404` — student not found

---

### Update Student
- **Method:** `PUT`
- **URL:** `/api/students/{id}`
- **Purpose:** Update an existing student

**Request body:** Any subset of the create fields.

**Validation rules:** Same as create, but fields are optional (`sometimes`). Email uniqueness check excludes the student's own current record, so keeping the same email does not trigger a validation error.

**Success response (200):** Updated student object.

**Possible errors:**
- `404` — student not found
- `422` — validation failed

---

### Delete Student
- **Method:** `DELETE`
- **URL:** `/api/students/{id}`
- **Purpose:** Delete a student

**Success response (200):**
```json
{
    "message": "Student deleted successfully."
}
```

**Possible errors:**
- `404` — student not found

---

### Student Statistics (Bonus)
- **Method:** `GET`
- **URL:** `/api/students/statistics`
- **Purpose:** Return aggregate counts of students by status

**Success response (200):**
```json
{
    "total_students": 100,
    "active_students": 85,
    "inactive_students": 15
}
```

---

## Architecture Explanation

- **Controller** — receives the HTTP request and returns the HTTP response. Contains no database queries, no business logic, and no manual validation. It only calls the service and formats the result.

- **Form Request** — handles validation before the controller method runs. `StoreStudentRequest` and `UpdateStudentRequest` each define their own rules, keeping validation logic out of the controller and reusable.

- **Service (`StudentService`)** — coordinates application/business logic. It owns the rule that a student must be at least 15 years old, and applies it consistently on both create and update, so the rule is never duplicated.

- **Repository Interface (`StudentRepositoryInterface`)** — describes the data operations the application needs (`all`, `find`, `create`, `update`, `delete`, `statistics`) without exposing any Eloquent implementation detail. This lets the service depend on an abstraction, not a concrete class.

- **Repository (`StudentRepository`)** — the only layer that talks to Eloquent directly. It builds queries, applies filters, and performs inserts/updates/deletes.

- **Model (`Student`)** — represents the `students` table, defines fillable fields, UUID behavior, and attribute casting. It does not contain any application workflow logic.

- **API Resource (`StudentResource`)** — defines the public JSON shape returned to clients, decoupling the database structure from the API's response format.

---

## Data Flow — `POST /api/students`

1. The HTTP request hits the `POST /api/students` route, which is bound to `StudentController@store`.
2. Laravel resolves `StoreStudentRequest` before the controller method runs, validating the incoming data against the defined rules. If validation fails, a `422` response is returned automatically and the controller is never reached.
3. The controller calls `$this->studentService->createStudent($request->validated())`, passing only the validated data.
4. `StudentService` checks the business rule (age ≥ 15). If it fails, a `ValidationException` is thrown, which Laravel converts into a `422` response.
5. If the rule passes, the service calls `$this->studentRepository->create($data)`.
6. `StudentRepository` performs the actual `Student::create($data)` Eloquent call, inserting the record into the SQLite database with an auto-generated UUID.
7. The created `Student` model instance is returned back up through the repository and service to the controller.
8. The controller wraps the model in a `StudentResource`, which formats the final JSON shape.
9. The response is returned to the client with HTTP status `201 Created`.

---

## Why Separate Controller, Service, Repository, and Model?

**1. Separation of responsibilities** — each layer has exactly one job. The controller only handles HTTP input/output, the service only handles business rules, the repository only handles data access, and the model only represents data. No single class carries multiple unrelated responsibilities.

**2. Maintainability** — when a business rule changes (e.g. the minimum age), there is exactly one place to update it (`StudentService`), instead of hunting through multiple controllers or duplicated `if` statements.

**3. Testability** — because `StudentService` depends on `StudentRepositoryInterface` rather than a concrete class, it's possible to substitute a fake/mock repository in tests without touching a real database, making unit tests fast and isolated. It also allows targeted feature tests against the actual HTTP layer without worrying about business logic bleeding into the request/response cycle.

**4. Reusability** — the age-validation business rule lives in one method and can be reused by any future entry point (a console command, a queued job, a second controller) without being copy-pasted.

**5. Dependency management** — using an interface (`StudentRepositoryInterface`) instead of a concrete `StudentRepository` means the service depends on a contract, not an implementation. The concrete implementation can be swapped (e.g. for a caching repository, or a different database driver) by changing a single line in `AppServiceProvider`, with zero changes required in `StudentService` itself.