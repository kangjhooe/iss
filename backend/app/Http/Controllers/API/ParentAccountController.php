<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Models\ParentLink;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParentAccountController extends Controller
{
    use ResolvesActiveInstitution;

    protected function authorizeManage(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin() || $user->hasModuleAccess('student')) {
            return null;
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    protected function requireInstitutionId(Request $request): int|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 422);
        }

        return $institutionId;
    }

    /**
     * List parent accounts linked to students in the active institution.
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $search = trim((string) $request->get('search', ''));

        $parentIds = ParentLink::query()
            ->where('institution_id', $institutionId)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        $query = User::query()
            ->where('role', 'parent')
            ->where(function ($q) use ($parentIds, $institutionId) {
                if (! empty($parentIds)) {
                    $q->whereIn('id', $parentIds);
                }
                $q->orWhere('institution_id', $institutionId);
            })
            ->with(['parentLinks' => function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                    ->with(['student:id,name,nis,class_id,institution_id', 'student.schoolClass:id,name']);
            }]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $parents = $query
            ->orderBy('name')
            ->paginate(min((int) $request->get('per_page', 20), 100));

        $parents->getCollection()->transform(function (User $parent) use ($institutionId) {
            return $this->serializeParent($parent, $institutionId, true);
        });

        return response()->json($parents);
    }

    /**
     * Students in institution that can be linked (optional search).
     */
    public function candidates(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $search = trim((string) $request->get('search', ''));
        $excludeLinkedTo = $request->integer('exclude_parent_id') ?: null;

        $query = Student::query()
            ->with(['schoolClass:id,name'])
            ->where('institution_id', $institutionId)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', Student::STATUS_ACTIVE);
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('guardian_phone', 'like', "%{$search}%")
                    ->orWhere('guardian_name', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->limit(40)->get()->map(function (Student $s) use ($excludeLinkedTo) {
            $alreadyLinked = false;
            if ($excludeLinkedTo) {
                $alreadyLinked = ParentLink::query()
                    ->where('user_id', $excludeLinkedTo)
                    ->where('student_id', $s->id)
                    ->exists();
            }

            return [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'class_name' => $s->schoolClass?->name,
                'guardian_name' => $s->guardian_name,
                'guardian_phone' => $s->guardian_phone,
                'already_linked' => $alreadyLinked,
            ];
        });

        return response()->json(['data' => $students]);
    }

    /**
     * Create parent account and optionally link students.
     * If email already belongs to a parent, reuse that account and link students (multi-sekolah).
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'relation' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $email = trim($validated['email']);
        $studentIds = array_values(array_unique(array_map('intval', $validated['student_ids'] ?? [])));
        $relation = $validated['relation'] ?? 'wali';

        if (! empty($studentIds)) {
            $this->assertStudentsInInstitution($studentIds, $institutionId);
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing && $existing->role !== 'parent') {
            throw ValidationException::withMessages([
                'email' => ['Email/HP ini sudah dipakai akun non-orang-tua.'],
            ]);
        }

        try {
            $plainPassword = null;
            $created = false;

            $parent = DB::transaction(function () use ($validated, $email, $studentIds, $relation, $institutionId, $existing, &$plainPassword, &$created) {
                if ($existing) {
                    $parent = $existing;
                    if (! empty($validated['name']) && $validated['name'] !== $parent->name) {
                        $parent->name = $validated['name'];
                        $parent->save();
                    }
                } else {
                    $plainPassword = $validated['password'] ?? $this->generateTempPassword();
                    $parent = User::create([
                        'institution_id' => $institutionId,
                        'name' => $validated['name'],
                        'email' => $email,
                        'password' => $plainPassword,
                        'role' => 'parent',
                        'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
                        'must_change_password' => true,
                        'email_verified_at' => now(),
                    ]);
                    $created = true;
                }

                foreach ($studentIds as $studentId) {
                    ParentLink::updateOrCreate(
                        [
                            'user_id' => $parent->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'institution_id' => $institutionId,
                            'relation' => $relation,
                        ]
                    );
                }

                return $parent;
            });

            Log::info($created ? 'Parent account created' : 'Parent account reused & linked', [
                'parent_id' => $parent->id,
                'institution_id' => $institutionId,
                'by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => $created
                    ? 'Akun orang tua berhasil dibuat.'
                    : 'Akun orang tua sudah ada — siswa ditautkan ke akun tersebut.',
                'data' => $this->serializeParent($parent->fresh(), $institutionId),
                'temporary_password' => $plainPassword,
                'reused_existing' => ! $created,
                'login_hint' => [
                    'login' => $parent->email,
                    'must_change_password' => true,
                ],
            ], $created ? 201 : 200);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create parent account', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membuat akun orang tua.'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $parent = $this->findParentForInstitution($id, $institutionId);
        if (! $parent) {
            return response()->json(['message' => 'Akun orang tua tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $this->serializeParent($parent, $institutionId)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $parent = $this->findParentForInstitution($id, $institutionId);
        if (! $parent) {
            return response()->json(['message' => 'Akun orang tua tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('user', 'email')->ignore($parent->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['email'])) {
            $validated['email'] = trim($validated['email']);
        }

        $parent->fill($validated);
        $parent->save();

        return response()->json([
            'message' => 'Akun orang tua diperbarui.',
            'data' => $this->serializeParent($parent->fresh(), $institutionId),
        ]);
    }

    /**
     * Link one or more students to an existing parent.
     */
    public function link(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $parent = User::query()->where('role', 'parent')->find($id);
        if (! $parent) {
            return response()->json(['message' => 'Akun orang tua tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'relation' => ['nullable', 'string', 'max:40'],
        ]);

        $studentIds = array_values(array_unique(array_map('intval', $validated['student_ids'])));
        $this->assertStudentsInInstitution($studentIds, $institutionId);
        $relation = $validated['relation'] ?? 'wali';

        foreach ($studentIds as $studentId) {
            ParentLink::updateOrCreate(
                [
                    'user_id' => $parent->id,
                    'student_id' => $studentId,
                ],
                [
                    'institution_id' => $institutionId,
                    'relation' => $relation,
                ]
            );
        }

        // Keep home institution if empty; otherwise leave as-is (may already be another school).
        if (! $parent->institution_id) {
            $parent->update(['institution_id' => $institutionId]);
        }

        return response()->json([
            'message' => 'Siswa berhasil ditautkan.',
            'data' => $this->serializeParent($parent->fresh(), $institutionId),
        ]);
    }

    public function unlink(Request $request, int $id, int $studentId): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $parent = $this->findParentForInstitution($id, $institutionId);
        if (! $parent) {
            return response()->json(['message' => 'Akun orang tua tidak ditemukan.'], 404);
        }

        $deleted = ParentLink::query()
            ->where('user_id', $parent->id)
            ->where('student_id', $studentId)
            ->where('institution_id', $institutionId)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Tautan siswa tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Tautan siswa dilepas.',
            'data' => $this->serializeParent($parent->fresh(), $institutionId),
        ]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->authorizeManage($request)) {
            return $deny;
        }
        $institutionId = $this->requireInstitutionId($request);
        if ($institutionId instanceof JsonResponse) {
            return $institutionId;
        }

        $parent = $this->findParentForInstitution($id, $institutionId);
        if (! $parent) {
            return response()->json(['message' => 'Akun orang tua tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
        ]);

        $plainPassword = $validated['password'] ?? $this->generateTempPassword();
        $parent->forceFill([
            'password' => $plainPassword,
            'must_change_password' => true,
        ])->save();

        return response()->json([
            'message' => 'Password berhasil direset. Berikan sandi sementara kepada orang tua.',
            'temporary_password' => $plainPassword,
            'login_hint' => [
                'login' => $parent->email,
                'must_change_password' => true,
            ],
        ]);
    }

    protected function generateTempPassword(): string
    {
        return Str::lower(Str::random(4)).Str::upper(Str::random(2)).(string) random_int(10, 99);
    }

    /**
     * @param  list<int>  $studentIds
     */
    protected function assertStudentsInInstitution(array $studentIds, int $institutionId): void
    {
        $count = Student::query()
            ->where('institution_id', $institutionId)
            ->whereIn('id', $studentIds)
            ->count();

        if ($count !== count($studentIds)) {
            throw ValidationException::withMessages([
                'student_ids' => ['Satu atau lebih siswa tidak valid untuk institusi ini.'],
            ]);
        }
    }

    protected function findParentForInstitution(int $parentId, int $institutionId): ?User
    {
        $parent = User::query()->where('role', 'parent')->find($parentId);
        if (! $parent) {
            return null;
        }

        $hasLink = ParentLink::query()
            ->where('user_id', $parent->id)
            ->where('institution_id', $institutionId)
            ->exists();

        if ($hasLink || (int) $parent->institution_id === $institutionId) {
            return $parent;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeParent(User $parent, int $institutionId, bool $preferLoaded = false): array
    {
        if ($preferLoaded && $parent->relationLoaded('parentLinks')) {
            $linkModels = $parent->parentLinks;
        } else {
            $linkModels = ParentLink::query()
                ->with(['student:id,name,nis,class_id,institution_id', 'student.schoolClass:id,name'])
                ->where('user_id', $parent->id)
                ->where('institution_id', $institutionId)
                ->get();
        }

        $links = $linkModels->map(fn (ParentLink $link) => [
            'id' => $link->id,
            'student_id' => $link->student_id,
            'relation' => $link->relation,
            'student_name' => $link->student?->name,
            'student_nis' => $link->student?->nis,
            'class_name' => $link->student?->schoolClass?->name,
        ])->values();

        return [
            'id' => $parent->id,
            'name' => $parent->name,
            'email' => $parent->email,
            'is_active' => $parent->is_active,
            'institution_id' => $parent->institution_id,
            'must_change_password' => (bool) $parent->must_change_password,
            'children_count' => $links->count(),
            'children' => $links,
            'created_at' => $parent->created_at?->toISOString(),
        ];
    }
}
