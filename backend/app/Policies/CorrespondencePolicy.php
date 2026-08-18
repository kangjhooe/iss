<?php

namespace App\Policies;

use App\Models\Correspondence;
use App\Models\CorrespondenceDisposition;
use App\Models\User;
use App\Support\InstitutionContext;

class CorrespondencePolicy
{
    /**
     * Determine if the user can view any correspondences.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('correspondence');
    }

    /**
     * Determine if the user can view the correspondence.
     */
    public function view(User $user, Correspondence $correspondence): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if ($this->isDispositionParty($user, $correspondence)) {
            return true;
        }

        if (!$user->hasModuleAccess('correspondence')) {
            return false;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can create correspondences.
     */
    public function create(User $user): bool
    {
        return $user->hasModuleAccess('correspondence');
    }

    /**
     * Determine if the user can update the correspondence.
     */
    public function update(User $user, Correspondence $correspondence): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (!$user->hasModuleAccess('correspondence')) {
            return false;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can delete the correspondence.
     */
    public function delete(User $user, Correspondence $correspondence): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (!$user->hasModuleAccess('correspondence')) {
            return false;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can restore the correspondence.
     */
    public function restore(User $user, Correspondence $correspondence): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (!$user->hasModuleAccess('correspondence')) {
            return false;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can permanently delete the correspondence.
     */
    public function forceDelete(User $user, Correspondence $correspondence): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can approve the correspondence.
     */
    public function approve(User $user, Correspondence $correspondence): bool
    {
        if (!$user->isAdminOrSuperAdmin()) {
            return false;
        }

        if (!$user->isSuperAdmin() && !$this->canAccessCorrespondenceInstitution($user, $correspondence)) {
            return false;
        }

        return $correspondence->canBeApproved();
    }

    /**
     * Determine if the user can send the correspondence.
     */
    public function send(User $user, Correspondence $correspondence): bool
    {
        if (!$user->isAdminOrSuperAdmin()) {
            return false;
        }

        if (!$user->isSuperAdmin() && !$this->canAccessCorrespondenceInstitution($user, $correspondence)) {
            return false;
        }

        return $correspondence->canBeSent();
    }

    /**
     * Determine if the user can archive the correspondence.
     */
    public function archive(User $user, Correspondence $correspondence): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (!$user->hasModuleAccess('correspondence')) {
            return false;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * User may act on correspondence belonging to an institution they can access
     * (home or approved non-induk assignment), not only users.institution_id.
     */
    private function canAccessCorrespondenceInstitution(User $user, Correspondence $correspondence): bool
    {
        return InstitutionContext::canAccessInstitution($user, (int) $correspondence->institution_id);
    }

    /**
     * Penerima/pengirim disposisi boleh melihat detail surat terkait.
     */
    private function isDispositionParty(User $user, Correspondence $correspondence): bool
    {
        return CorrespondenceDisposition::query()
            ->where('correspondence_id', $correspondence->id)
            ->where(function ($q) use ($user) {
                $q->where('to_user_id', $user->id)
                    ->orWhere('from_user_id', $user->id);
            })
            ->exists();
    }
}
