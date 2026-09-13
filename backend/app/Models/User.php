<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user';

    /**
     * School personnel who may be assigned as a counseling counselor (not students/parents).
     */
    public const COUNSELOR_ROLES = ['teacher', 'staff'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'name',
        'email',
        'login_nik',
        'password',
        'must_change_password',
        'role',
        'is_active',
        'email_verified_at',
        // failed_login_attempts / locked_until are set only via forceFill / explicit assignment
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the institution that owns the user.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is institution admin.
     */
    public function isInstitutionAdmin(): bool
    {
        return $this->role === 'institution_admin';
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is admin or super admin.
     */
    public function isAdminOrSuperAdmin(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Permissions assigned to the user.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * Active institution for the current request (header/cookie), fallback to home.
     */
    public function currentInstitutionId(?\Illuminate\Http\Request $request = null): ?int
    {
        $request = $request ?? request();
        // Prefer nilai yang sudah di-set middleware institution.context (hindari resolve ulang).
        $attr = $request?->attributes->get('current_institution_id');
        if ($attr !== null && $attr !== '') {
            return (int) $attr;
        }

        return \App\Support\InstitutionContext::resolveActiveInstitutionId($this, $request);
    }

    /**
     * Check if user has access to a module (scoped to active institution).
     */
    public function hasModuleAccess(string $moduleKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $request = request();
        $cacheKey = 'user_has_module_'.$this->id.'_'.$moduleKey;
        if ($request->attributes->has($cacheKey)) {
            return (bool) $request->attributes->get($cacheKey);
        }

        $institutionId = $this->currentInstitutionId($request);
        if ($institutionId && \App\Support\InstitutionModuleVisibility::isHidden($institutionId, $moduleKey)) {
            $request->attributes->set($cacheKey, false);

            return false;
        }

        if ($this->isAdmin() || $this->isInstitutionAdmin()) {
            $request->attributes->set($cacheKey, true);

            return true;
        }

        if ($moduleKey === \App\Support\ReportAccess::MODULE_KEY) {
            $ok = \App\Support\ReportAccess::canAccess($this);
            $request->attributes->set($cacheKey, $ok);

            return $ok;
        }

        $ok = \App\Support\InstitutionContext::hasEffectivePermission($this, $moduleKey, $institutionId, $request);
        $request->attributes->set($cacheKey, $ok);

        return $ok;
    }

    /**
     * Get the change requests requested by this user.
     */
    public function changeRequests()
    {
        return $this->hasMany(InstitutionChangeRequest::class, 'requested_by');
    }

    /**
     * Get the change requests approved/rejected by this user.
     */
    public function approvedChangeRequests()
    {
        return $this->hasMany(InstitutionChangeRequest::class, 'approved_by');
    }

    /**
     * Get the student change requests requested by this user.
     */
    public function studentChangeRequestsRequested()
    {
        return $this->hasMany(StudentChangeRequest::class, 'requested_by');
    }

    /**
     * Get the student change requests approved/rejected by this user.
     */
    public function studentChangeRequestsApproved()
    {
        return $this->hasMany(StudentChangeRequest::class, 'approved_by');
    }

    /**
     * Get the teacher change requests requested by this user.
     */
    public function teacherChangeRequestsRequested()
    {
        return $this->hasMany(TeacherChangeRequest::class, 'requested_by');
    }

    /**
     * Get the teacher change requests approved/rejected by this user.
     */
    public function teacherChangeRequestsApproved()
    {
        return $this->hasMany(TeacherChangeRequest::class, 'approved_by');
    }

    /**
     * Get the violations reported by this user.
     */
    public function violationsReported()
    {
        return $this->hasMany(Violation::class, 'reported_by');
    }

    /**
     * Get the achievements given by this user.
     */
    public function achievementsGiven()
    {
        return $this->hasMany(Achievement::class, 'given_by');
    }

    /**
     * Get the student action logs recorded by this user.
     */
    public function studentActionLogsRecorded()
    {
        return $this->hasMany(StudentActionLog::class, 'recorded_by');
    }

    /**
     * Get the counseling sessions where this user is the counselor.
     */
    public function counselingSessionsAsCounselor()
    {
        return $this->hasMany(CounselingSession::class, 'counselor_id');
    }

    /**
     * Get the guest visits created by this user.
     */
    public function guestVisitsCreated()
    {
        return $this->hasMany(GuestVisit::class, 'created_by');
    }

    /**
     * Get the digital archives created by this user.
     */
    public function digitalArchivesCreated()
    {
        return $this->hasMany(DigitalArchive::class, 'created_by');
    }

    /**
     * Get the surat created by this user.
     */
    public function suratCreated()
    {
        return $this->hasMany(Surat::class, 'created_by');
    }

    /**
     * Get the announcements created by this user.
     */
    public function announcementsCreated()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    /**
     * Get the correspondence created by this user.
     */
    public function correspondencesCreated()
    {
        return $this->hasMany(Correspondence::class, 'created_by');
    }

    /**
     * Get the correspondence approved by this user.
     */
    public function correspondencesApproved()
    {
        return $this->hasMany(Correspondence::class, 'approved_by');
    }

    /**
     * Get the document pickups created by this user.
     */
    public function documentPickupsCreated()
    {
        return $this->hasMany(DocumentPickup::class, 'created_by');
    }

    /**
     * Get the academic calendar events created by this user.
     */
    public function academicCalendarEventsCreated()
    {
        return $this->hasMany(AcademicCalendarEvent::class, 'created_by');
    }

    /**
     * Get the library loans created by this user.
     */
    public function libraryLoansCreated()
    {
        return $this->hasMany(LibraryLoan::class, 'created_by');
    }

    /**
     * Get the correspondence dispositions given by this user.
     */
    public function dispositionsGiven()
    {
        return $this->hasMany(CorrespondenceDisposition::class, 'from_user_id');
    }

    /**
     * Get the correspondence dispositions received by this user.
     */
    public function dispositionsReceived()
    {
        return $this->hasMany(CorrespondenceDisposition::class, 'to_user_id');
    }

    /**
     * Get the correspondence history records by this user.
     */
    public function correspondenceHistories()
    {
        return $this->hasMany(CorrespondenceHistory::class);
    }

    /**
     * Get the audit logs by this user.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Exams created by this user.
     */
    public function createdExams()
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    /**
     * Bank soal created by this user.
     */
    public function createdBankSoal()
    {
        return $this->hasMany(BankSoal::class, 'created_by_user_id');
    }

    /**
     * Get the student profile associated with this user (by NIK login).
     */
    public function studentProfile()
    {
        return $this->hasOne(Student::class, 'nik', 'login_nik');
    }

    /**
     * Get the teacher profile associated with this user (by email).
     * Note: Using Employee model as teacher table has been renamed to employee
     */
    public function teacherProfile()
    {
        return $this->hasOne(Employee::class, 'email', 'email')->where('type', 'Guru');
    }

    /**
     * Get the employee profile associated with this user (by email).
     */
    public function employeeProfile()
    {
        return $this->hasOne(Employee::class, 'email', 'email');
    }

    /**
     * Whether the user may manage a lab room (admin or assigned penanggung jawab).
     */
    public function canManageLab(Room $room): bool
    {
        if ($this->isAdminOrSuperAdmin()) {
            return true;
        }

        if (! \App\Support\InstitutionContext::canAccessInstitution($this, (int) $room->institution_id)) {
            return false;
        }

        if ($this->isInstitutionAdmin()) {
            return true;
        }

        if ($room->type !== 'Laboratorium') {
            return true;
        }

        $employee = \App\Support\InstitutionContext::employeeForInstitution($this, (int) $room->institution_id);

        return $employee && (int) $room->responsible_employee_id === (int) $employee->id;
    }

    /**
     * Whether the user is penanggung jawab for at least one room (any type).
     * When $institutionId is set, only rooms at that school count.
     */
    public function isRoomResponsible(?int $institutionId = null): bool
    {
        if ($this->isAdminOrSuperAdmin() || $this->isInstitutionAdmin()) {
            return false;
        }

        $request = request();
        $resolvedInstitutionId = $institutionId ?? $this->currentInstitutionId($request);
        $cacheKey = 'user_is_room_resp_'.$this->id.'_'.($resolvedInstitutionId ?: 0);
        if ($request->attributes->has($cacheKey)) {
            return (bool) $request->attributes->get($cacheKey);
        }

        $employee = \App\Support\InstitutionContext::employeeForInstitution($this, $resolvedInstitutionId, $request);
        if (! $employee) {
            $request->attributes->set($cacheKey, false);

            return false;
        }

        $query = Room::query()
            ->where('responsible_employee_id', $employee->id);

        if ($resolvedInstitutionId) {
            $query->where('institution_id', $resolvedInstitutionId);
        }

        $ok = $query->exists();
        $request->attributes->set($cacheKey, $ok);

        return $ok;
    }

    /**
     * Whether the user is penanggung jawab (Kepala Lab) for at least one laboratorium.
     * When $institutionId is set, only labs at that school count.
     */
    public function isLabResponsible(?int $institutionId = null): bool
    {
        if ($this->isAdminOrSuperAdmin() || $this->isInstitutionAdmin()) {
            return false;
        }

        $request = request();
        $resolvedInstitutionId = $institutionId ?? $this->currentInstitutionId($request);
        $cacheKey = 'user_is_lab_resp_'.$this->id.'_'.($resolvedInstitutionId ?: 0);
        if ($request->attributes->has($cacheKey)) {
            return (bool) $request->attributes->get($cacheKey);
        }

        $employee = \App\Support\InstitutionContext::employeeForInstitution($this, $resolvedInstitutionId, $request);
        if (! $employee) {
            $request->attributes->set($cacheKey, false);

            return false;
        }

        $query = Room::query()
            ->where('type', 'Laboratorium')
            ->where('responsible_employee_id', $employee->id);

        if ($resolvedInstitutionId) {
            $query->where('institution_id', $resolvedInstitutionId);
        }

        $ok = $query->exists();
        $request->attributes->set($cacheKey, $ok);

        return $ok;
    }

    /**
     * Whether the user is pembina of at least one ekstrakurikuler.
     * When $institutionId is set, only clubs at that school count.
     */
    public function isExtracurricularSupervisor(?int $institutionId = null): bool
    {
        return \App\Support\ExtracurricularAccess::isSupervisor($this, $institutionId);
    }

    /**
     * Room IDs of labs this user is responsible for.
     *
     * @return array<int, int>
     */
    public function managedLabRoomIds(?int $institutionId = null): array
    {
        $employee = \App\Support\InstitutionContext::employeeForInstitution($this, $institutionId);
        if (! $employee) {
            return [];
        }

        $resolvedInstitutionId = $institutionId ?? \App\Support\InstitutionContext::resolveActiveInstitutionId($this);

        $query = Room::query()
            ->where('type', 'Laboratorium')
            ->where('responsible_employee_id', $employee->id);

        if ($resolvedInstitutionId) {
            $query->where('institution_id', $resolvedInstitutionId);
        }

        return $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Room IDs this user is penanggung jawab for (all room types).
     *
     * @return list<int>
     */
    public function managedRoomIds(?int $institutionId = null): array
    {
        $employee = \App\Support\InstitutionContext::employeeForInstitution($this, $institutionId);
        if (! $employee) {
            return [];
        }

        $resolvedInstitutionId = $institutionId ?? \App\Support\InstitutionContext::resolveActiveInstitutionId($this);

        $query = Room::query()
            ->where('responsible_employee_id', $employee->id);

        if ($resolvedInstitutionId) {
            $query->where('institution_id', $resolvedInstitutionId);
        }

        return $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Whether the user may view a lab room in an accessible institution.
     */
    public function canViewLab(Room $room): bool
    {
        if ($this->isAdminOrSuperAdmin()) {
            return true;
        }

        return \App\Support\InstitutionContext::canAccessInstitution($this, (int) $room->institution_id);
    }

    /**
     * Check if user account is locked.
     * Expired locks are cleared so the user gets a fresh attempt window.
     */
    public function isLocked(): bool
    {
        if (! $this->locked_until) {
            return false;
        }

        if ($this->locked_until->isFuture()) {
            return true;
        }

        // Lock window expired — reset counter so one wrong password
        // does not immediately re-lock the account.
        $this->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return false;
    }

    /**
     * Minutes remaining until lock expires (at least 1 while locked).
     */
    public function lockedMinutesRemaining(): int
    {
        if (! $this->locked_until || ! $this->locked_until->isFuture()) {
            return 0;
        }

        $seconds = $this->locked_until->getTimestamp() - now()->getTimestamp();

        return (int) max(1, (int) ceil($seconds / 60));
    }

    /**
     * Increment failed login attempts and lock account if threshold reached.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');

        // Lock account after 5 failed attempts for 30 minutes
        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(30);
            $this->save();
        }
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Check if email is verified.
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check if user is teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if user is staff (non-teacher employee).
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user is teacher or staff (employee with login).
     */
    public function isTeacherOrStaff(): bool
    {
        return $this->isTeacher() || $this->isStaff();
    }

    public function isEligibleCounselor(): bool
    {
        return in_array($this->role, self::COUNSELOR_ROLES, true);
    }

    public function scopeEligibleCounselors($query)
    {
        return $query->whereIn('role', self::COUNSELOR_ROLES);
    }

    /**
     * Check if user is student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if user is parent / wali murid.
     */
    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    /**
     * School staff (admin/guru/staf) may submit bug reports and feature requests.
     */
    public function canSubmitFeedback(): bool
    {
        if (! $this->isInstitutionAdmin() && ! $this->isAdmin() && ! $this->isTeacherOrStaff()) {
            return false;
        }

        return (bool) ($this->institution_id || $this->currentInstitutionId());
    }

    /**
     * Super admin inbox, or school staff who can file tickets.
     */
    public function canAccessFeedback(): bool
    {
        return $this->isSuperAdmin() || $this->canSubmitFeedback();
    }

    /**
     * Explicit parent↔student links.
     */
    public function parentLinks()
    {
        return $this->hasMany(ParentLink::class);
    }

    /**
     * Students linked via parent_links pivot.
     */
    public function linkedStudents()
    {
        return $this->belongsToMany(Student::class, 'parent_links')
            ->withPivot('institution_id', 'relation')
            ->withTimestamps();
    }

    /**
     * Scope a query to filter by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, ?int $institutionId)
    {
        if ($institutionId === null) {
            return $query->whereNull('institution_id');
        }

        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to only include active users (not locked).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('locked_until')
                ->orWhere('locked_until', '<=', now());
        });
    }
}
