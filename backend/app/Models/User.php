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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'name',
        'email',
        'password',
        'role',
        'failed_login_attempts',
        'locked_until',
        'email_verified_at',
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
            'locked_until' => 'datetime',
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
     * Check if user has access to a module.
     */
    public function hasModuleAccess(string $moduleKey): bool
    {
        if ($this->isAdminOrSuperAdmin() || $this->isInstitutionAdmin()) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('key', $moduleKey);
        }

        return $this->permissions()->where('key', $moduleKey)->exists();
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
     * Get the student profile associated with this user (by email).
     */
    public function studentProfile()
    {
        return $this->hasOne(Student::class, 'email', 'email');
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
     * Check if user account is locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
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
     * Check if user is student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
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
