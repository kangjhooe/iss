# Perbaikan Arsitektur - Indonesia Smart School

Dokumen ini menjelaskan perbaikan arsitektur yang telah dilakukan pada sistem (Perbaikan 2.1 - 2.4).

## ✅ Perbaikan yang Telah Diimplementasikan

### 2.1 Custom Exception Handler ✅

**Status:** Selesai

Custom exception handler telah dibuat untuk menangani semua exception secara terpusat dengan format response yang konsisten.

**File yang dibuat:**
- `backend/app/Exceptions/Handler.php`

**File yang diupdate:**
- `backend/bootstrap/app.php` - Menggunakan custom exception handler

**Fitur:**
- ✅ Centralized error handling untuk semua API requests
- ✅ Format response konsisten dengan `success` dan `message`
- ✅ Handling untuk berbagai jenis exception:
  - ValidationException (422)
  - ModelNotFoundException (404)
  - NotFoundHttpException (404)
  - AuthenticationException (401)
  - AuthorizationException (403)
  - Generic exceptions (500)
- ✅ Error logging dengan context
- ✅ Debug mode check untuk error details
- ✅ Error messages dalam Bahasa Indonesia

**Format Response:**

```json
{
  "success": false,
  "message": "Error message dalam Bahasa Indonesia",
  "errors": {} // Untuk validation errors
}
```

**Keuntungan:**
- Error handling terpusat
- Format response konsisten
- Lebih mudah untuk maintenance
- Tidak perlu try-catch di setiap controller method

---

### 2.2 Service Layer ✅

**Status:** Selesai

Service layer telah diimplementasikan untuk memisahkan business logic dari controller.

**File yang dibuat:**
- `backend/app/Services/AuthService.php`
- `backend/app/Services/InstitutionService.php`
- `backend/app/Services/StudentService.php`
- `backend/app/Services/TeacherService.php`

**Fitur:**

#### AuthService
- `register()` - Register new institution and user
- `login()` - Login user dengan account lockout check
- `sendPasswordResetLink()` - Send password reset email
- `resetPassword()` - Reset password dengan token
- `verifyEmail()` - Verify email address
- `resendVerificationEmail()` - Resend verification email
- `refreshToken()` - Refresh access token

#### InstitutionService
- `list()` - Get list of institutions with filters
- `create()` - Create new institution
- `find()` - Get institution by ID
- `getUserInstitution()` - Get user's institution
- `update()` - Update institution
- `delete()` - Delete institution (soft delete)

#### StudentService
- `list()` - Get list of students with filters
- `create()` - Create new student
- `find()` - Get student by ID
- `update()` - Update student
- `delete()` - Delete student (soft delete)

#### TeacherService
- `list()` - Get list of teachers with filters
- `create()` - Create new teacher
- `find()` - Get teacher by ID
- `update()` - Update teacher
- `delete()` - Delete teacher (soft delete)

**Keuntungan:**
- Business logic terpisah dari controller
- Reusable di berbagai tempat
- Lebih mudah untuk testing
- Controller menjadi lebih clean dan focused pada HTTP concerns

**Contoh Penggunaan:**

```php
// Di Controller
public function login(LoginRequest $request)
{
    $result = $this->authService->login(
        $request->email,
        $request->password
    );
    
    return response()->json([
        'message' => 'Login berhasil',
        'user' => new UserResource($result['user']),
        'token' => $result['access_token'],
        'refresh_token' => $result['refresh_token'],
    ]);
}
```

---

### 2.3 Repository Pattern ✅

**Status:** Selesai

Repository pattern telah diimplementasikan untuk abstraksi database operations.

**File yang dibuat:**
- `backend/app/Repositories/Contracts/RepositoryInterface.php`
- `backend/app/Repositories/BaseRepository.php`
- `backend/app/Repositories/InstitutionRepository.php`
- `backend/app/Repositories/StudentRepository.php`
- `backend/app/Repositories/TeacherRepository.php`

**Struktur:**

#### RepositoryInterface
Interface yang mendefinisikan contract untuk semua repository:
- `all()` - Get all records
- `find()` - Find by ID
- `findOrFail()` - Find by ID or fail
- `create()` - Create new record
- `update()` - Update record
- `delete()` - Delete record
- `paginate()` - Get paginated records

#### BaseRepository
Abstract base class yang mengimplementasikan RepositoryInterface:
- Common CRUD operations
- Query builder access
- Model instantiation

#### Specific Repositories
- `InstitutionRepository` - Institution-specific queries
- `StudentRepository` - Student-specific queries
- `TeacherRepository` - Teacher-specific queries

**Fitur:**
- ✅ Abstraksi database operations
- ✅ Reusable query methods
- ✅ Easy to test dengan mock
- ✅ Consistent data access pattern
- ✅ Custom query methods per repository

**Contoh Penggunaan:**

```php
// Di Service
public function list(array $filters, int $perPage = 15)
{
    return $this->institutionRepository->list($filters, $perPage);
}
```

**Keuntungan:**
- Abstraksi database layer
- Mudah untuk switch database atau ORM
- Query logic terpusat
- Lebih mudah untuk testing dengan mock repository

---

### 2.4 API Versioning ✅

**Status:** Selesai

API versioning telah diimplementasikan untuk memungkinkan multiple API versions.

**File yang dibuat:**
- `backend/routes/api/v1.php`

**File yang diupdate:**
- `backend/bootstrap/app.php` - Register versioned routes
- `backend/routes/api.php` - Root API route dengan version info

**Struktur:**

```
/api                    -> Root API info
/api/v1/*               -> Version 1 endpoints
```

**Fitur:**
- ✅ Versioned routes (`/api/v1/*`)
- ✅ Backward compatibility dengan legacy routes
- ✅ Easy to add new versions (v2, v3, etc.)
- ✅ Version info di root API endpoint

**Endpoint Structure:**

```
GET  /api              -> API info dengan version list
GET  /api/v1           -> Version 1 API info
POST /api/v1/login     -> Login endpoint (v1)
POST /api/v1/register  -> Register endpoint (v1)
...
```

**Cara Menambah Version Baru:**

1. Buat file `backend/routes/api/v2.php`
2. Update `bootstrap/app.php`:
```php
api: [
    __DIR__.'/../routes/api.php',
    __DIR__.'/../routes/api/v1.php' => 'v1',
    __DIR__.'/../routes/api/v2.php' => 'v2',
],
```

**Keuntungan:**
- Backward compatibility
- Easy to deprecate old versions
- Multiple versions bisa coexist
- Clear versioning strategy

---

## 📋 Struktur File Baru

```
backend/
├── app/
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   └── RepositoryInterface.php
│   │   ├── BaseRepository.php
│   │   ├── InstitutionRepository.php
│   │   ├── StudentRepository.php
│   │   └── TeacherRepository.php
│   └── Services/
│       ├── AuthService.php
│       ├── InstitutionService.php
│       ├── StudentService.php
│       └── TeacherService.php
└── routes/
    └── api/
        └── v1.php
```

---

## 🔄 Migration Path

### Untuk Controller yang Sudah Ada

Controller yang sudah ada masih bisa digunakan seperti biasa. Untuk menggunakan Service Layer dan Repository Pattern, update controller seperti ini:

**Sebelum:**
```php
public function index(Request $request)
{
    $institutions = Institution::query()
        ->where('name', 'like', '%' . $request->search . '%')
        ->paginate(15);
    
    return InstitutionResource::collection($institutions);
}
```

**Sesudah (dengan Service):**
```php
public function index(Request $request, InstitutionService $service)
{
    $filters = $request->only(['search', 'level', 'type', 'is_active']);
    $institutions = $service->list($filters, $request->get('per_page', 15));
    
    return InstitutionResource::collection($institutions);
}
```

**Sesudah (dengan Repository):**
```php
public function index(Request $request, InstitutionRepository $repository)
{
    $filters = $request->only(['search', 'level', 'type', 'is_active']);
    $institutions = $repository->list($filters, $request->get('per_page', 15));
    
    return InstitutionResource::collection($institutions);
}
```

---

## 🧪 Testing

### Testing Exception Handler

```php
// Test validation exception
$response = $this->postJson('/api/v1/login', []);
$response->assertStatus(422);
$response->assertJson([
    'success' => false,
    'message' => 'Validasi gagal',
]);

// Test model not found
$response = $this->getJson('/api/v1/institution/99999');
$response->assertStatus(404);
$response->assertJson([
    'success' => false,
    'message' => 'Data Institution tidak ditemukan.',
]);
```

### Testing Service Layer

```php
public function test_login_success()
{
    $user = User::factory()->create();
    
    $service = new AuthService();
    $result = $service->login($user->email, 'password');
    
    $this->assertArrayHasKey('user', $result);
    $this->assertArrayHasKey('access_token', $result);
    $this->assertArrayHasKey('refresh_token', $result);
}
```

### Testing Repository

```php
public function test_repository_find()
{
    $institution = Institution::factory()->create();
    
    $repository = new InstitutionRepository();
    $found = $repository->find($institution->id);
    
    $this->assertEquals($institution->id, $found->id);
}
```

---

## 📝 Best Practices

### 1. Exception Handling
- ✅ Gunakan custom exception handler untuk semua API errors
- ✅ Jangan catch exception di controller kecuali ada business logic khusus
- ✅ Biarkan exception handler menangani semua errors

### 2. Service Layer
- ✅ Semua business logic di Service
- ✅ Controller hanya handle HTTP concerns (request/response)
- ✅ Service bisa digunakan di berbagai tempat (controller, command, queue, etc.)

### 3. Repository Pattern
- ✅ Semua database queries di Repository
- ✅ Service menggunakan Repository untuk data access
- ✅ Custom query methods di specific repository

### 4. API Versioning
- ✅ Selalu gunakan versioned endpoints (`/api/v1/*`)
- ✅ Jangan breaking changes di version yang sama
- ✅ Buat version baru untuk breaking changes

---

## 🔐 Security Considerations

1. **Exception Handler:**
   - Tidak expose sensitive information di production
   - Debug mode check untuk error details
   - Proper logging untuk security events

2. **Service Layer:**
   - Authorization checks tetap di controller atau middleware
   - Service focus pada business logic, bukan security

3. **Repository Pattern:**
   - Input validation tetap di Form Request
   - Repository tidak handle authorization

---

## 🚀 Next Steps

1. **Update Controllers:**
   - Refactor controllers untuk menggunakan Service Layer
   - Remove try-catch blocks (biarkan exception handler handle)
   - Inject services via dependency injection

2. **Add More Services:**
   - InstitutionChangeRequestService
   - ReportService
   - NotificationService

3. **Add More Repositories:**
   - UserRepository
   - InstitutionChangeRequestRepository

4. **API Versioning:**
   - Monitor usage of v1 endpoints
   - Plan for v2 if needed
   - Document deprecation policy

---

## 📚 Referensi

- [Laravel Exception Handling](https://laravel.com/docs/errors)
- [Service Layer Pattern](https://martinfowler.com/eaaCatalog/serviceLayer.html)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
- [API Versioning Best Practices](https://restfulapi.net/versioning/)

---

**Terakhir diupdate:** 9 Januari 2026
