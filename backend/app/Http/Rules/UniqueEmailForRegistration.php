<?php

namespace App\Http\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Email unik hanya untuk user yang masih "aktif":
 * - super admin (institution_id null), atau
 * - institusi belum dihapus (deleted_at null).
 * User dari institusi yang sudah dihapus tidak memblokir email untuk daftar ulang.
 */
class UniqueEmailForRegistration implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $blocked = User::query()
            ->where('email', $value)
            ->where(function ($query) {
                $query->whereNull('institution_id')
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw(1)
                            ->from('institution')
                            ->whereColumn('institution.id', 'user.institution_id')
                            ->whereNull('institution.deleted_at');
                    });
            })
            ->exists();

        if ($blocked) {
            $fail('Email sudah terdaftar');
        }
    }
}
