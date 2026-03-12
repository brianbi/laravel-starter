# AGENTS.md - Laravel Starter Project Guidelines

## Project Overview

This is a Laravel 12.x admin panel starter kit with JWT authentication, RBAC (Spatie Permission), workflow engine, and form designer modules.

**Tech Stack:**
- PHP 8.2+
- Laravel 12.x
- JWT Auth (php-open-source-saver/jwt-auth)
- Spatie Permission
- SQLite (testing), MySQL (production)
- Vite + TailwindCSS 4.x

---

## Build Commands

```bash
# Full project setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build

# Development (concurrent server + queue + vite)
composer run dev

# Run tests
composer test              # Run all tests
php artisan test          # Via artisan
php artisan test --filter=UserTest  # Run specific test class
php artisan test --filter=test_user_can_login  # Run specific test

# Code formatting (Pint)
./vendor/bin/pint         # Format all files
./vendor/bin/pint --test  # Dry-run (check without changes)

# Linting
./vendor/bin/pint -v      # Verbose output
```

---

## Code Style Guidelines

### General

- **Indent**: 4 spaces (no tabs)
- **Line endings**: LF (Unix)
- **Charset**: UTF-8
- **Final newline**: Yes
- **Trailing whitespace**: Trimmed

### PHP Conventions

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Controllers | PascalCase + Controller suffix | `UserController` |
| Models | PascalCase (singular) | `User`, `Department` |
| Services | PascalCase + Service suffix | `UserService` |
| Repositories | PascalCase + Repository suffix | `UserRepository` |
| Requests | Entity + Request suffix | `UserRequest` |
| Traits | camelCase | `apiResponse` |
| Methods | camelCase | `updateStatus()` |
| Variables | camelCase | `$userData` |
| Constants | UPPER_SNAKE_CASE | `STATUS_ENABLED` |
| Database columns | snake_case | `created_at` |
| Routes | kebab-case | `/admin/users` |

### Imports

- Use fully qualified class names in `use` statements
- Group imports by type (Laravel, Custom, Models, etc.)
- Sort alphabetically within groups
- Blank line between groups

### Type Safety

- **Always use `declare(strict_types=1);`**
- Return types required on all methods
- Parameter types required on all methods
- Never use `/** @var */` - use proper types instead

### Error Handling

- Use `ApiResponse` trait for consistent JSON responses
- Use `DB::transaction()` for atomic operations
- Throw `ModelNotFoundException` via `findOrFail()`
- Return `JsonResponse` on all controller actions

```php
// Controller pattern
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService) {}

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findOrFail($id);
        return $this->success($user->load(['department', 'roles']));
    }
}
```

### Service/Repository Pattern

```php
// Service (business logic)
class UserService extends BaseService
{
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = $this->repository->create($data);
            if (!empty($data['role_ids'])) {
                $user->syncRoles($data['role_ids']);
            }
            return $user;
        });
    }
}

// Repository (data access)
class UserRepository extends BaseRepository
{
    // Custom query methods
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        return $this->query()
            ->where('username', $username)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
```

### Model Conventions

```php
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasCreator, HasStatus, Exportable;

    protected $fillable = ['username', 'name', 'email', 'department_id', 'status'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['status' => 'integer', 'password' => 'hashed'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
```

### API Response Format

All responses follow this structure:

```json
{
    "code": 200,
    "message": "success",
    "data": { ... }
}
```

Use `ApiResponse` trait methods:
- `$this->success($data, $message)` - 200
- `$this->created($data, $message)` - 201
- `$this->noContent($message)` - 204
- `$this->error($message, $code)` - 400+
- `$this->unauthorized($message)` - 401
- `$this->forbidden($message)` - 403
- `$this->notFound($message)` - 404
- `$this->validationError($errors)` - 422

### Request Validation

```php
class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'username' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('users')->ignore($userId),
            ],
            // ...
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '请输入用户名',
            'username.unique' => '用户名已存在',
        ];
    }
}
```

### Route Definition

```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['auth:api', 'operation.log'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::put('users/{user}/status', [UserController::class, 'updateStatus']);
        // ...
    });
});
```

---

## Test Conventions

- **Location**: `tests/Unit/` and `tests/Feature/`
- **Naming**: `*Test.php` suffix
- **Methods**: `test_*` prefix or `test*()` pattern
- **Base**: Extend `Tests\TestCase`

```php
class UserControllerTest extends TestCase
{
    public function test_user_can_be_created(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
    }
}
```

---

## Key File Locations

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/` | Admin API controllers |
| `app/Models/` | Eloquent models |
| `app/Services/` | Business logic layer |
| `app/Repositories/` | Data access layer |
| `app/Http/Requests/` | Form request validation |
| `routes/admin.php` | Admin API routes |
| `config/` | Configuration files |
| `tests/Unit/` | Unit tests |
| `tests/Feature/` | Feature/integration tests |

---

## Common Patterns

### Controller Method Pattern

```php
/**
 * Method description in Chinese
 */
public function index(Request $request): JsonResponse
{
    $params = $request->only(['field1', 'field2', 'page', 'per_page']);
    $perPage = (int) ($params['per_page'] ?? 15);
    $data = $this->service->paginate($params, $perPage);
    $data->load(['relations']);
    return $this->success($data);
}
```

### Service Transaction Pattern

```php
public function update(int $id, array $data): Model
{
    return DB::transaction(function () use ($id, $data) {
        $model = $this->repository->update($id, $data);
        // Related operations
        return $model;
    });
}
```

### Route Resource Methods

| HTTP | Controller Method | Route Name |
|------|------------------|------------|
| GET | index | users.index |
| POST | store | users.store |
| GET | show | users.show |
| PUT/PATCH | update | users.update |
| DELETE | destroy | users.destroy |
