<?php

namespace App\Policies;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\InstitutionContext;

class CorrespondencePolicy
{
    /**
     * Determine if the user can view any correspondences.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view correspondences
        return true;
    }

    /**
     * Determine if the user can view the correspondence.
     */
    public function view(User $user, Correspondence $correspondence): bool
    {
        // Super admin and admin can view all
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can create correspondences.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create correspondences
        return true;
    }

    /**
     * Determine if the user can update the correspondence.
     */
    public function update(User $user, Correspondence $correspondence): bool
    {
        // Super admin and admin can update all
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can delete the correspondence.
     */
    public function delete(User $user, Correspondence $correspondence): bool
    {
        // Super admin and admin can delete all
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can restore the correspondence.
     */
    public function restore(User $user, Correspondence $correspondence): bool
    {
        // Super admin and admin can restore all
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return $this->canAccessCorrespondenceInstitution($user, $correspondence);
    }

    /**
     * Determine if the user can permanently delete the correspondence.
     */
    public function forceDelete(User $user, Correspondence $correspondence): bool
    {
        // Only super admin can permanently delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can approve the correspondence.
     */
    public function approve(User $user, Correspondence $correspondence): bool
    {
        // Only admin and super admin can approve
        if (!$user->isAdminOrSuperAdmin()) {
            return false;
        }

        // Must be from an accessible institution (unless super admin)
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
        // Only admin and super admin can send
        if (!$user->isAdminOrSuperAdmin()) {
            return false;
        }

        // Must be from an accessible institution (unless super admin)
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
        // Super admin and admin can archive all
        if ($user->isAdminOrSuperAdmin()) {
            return true;
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
}
