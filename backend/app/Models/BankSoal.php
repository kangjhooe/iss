<?php

namespace App\Models;

use App\Support\TeacherMenuContext;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankSoal extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'bank_soal';

    protected $fillable = [
        'institution_id',
        'created_by_user_id',
        'code',
        'name',
        'subject_id',
        'grade',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Users with whom this bank is shared (collaborators: view, use, edit, copy).
     */
    public function sharedWithUsers()
    {
        return $this->belongsToMany(User::class, 'bank_soal_shares', 'bank_soal_id', 'user_id')
            ->withTimestamps();
    }

    public function shares()
    {
        return $this->hasMany(BankSoalShare::class, 'bank_soal_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Stimuli (teks/bacaan) belonging to this bank.
     */
    public function stimuli()
    {
        return $this->hasMany(QuestionStimulus::class, 'bank_soal_id');
    }

    public function questions()
    {
        return $this->hasMany(QuestionBank::class, 'bank_soal_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Banks the user can access: same institution (guru: mapel yang diampu), or shared with user.
     */
    public function scopeAccessibleBy($query, \Illuminate\Http\Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $query->whereRaw('0 = 1');
        }
        $institutionId = $user->isSuperAdmin() && $request->filled('institution_id')
            ? (int) $request->get('institution_id')
            : ($request->attributes->get('current_institution_id')
                ? (int) $request->attributes->get('current_institution_id')
                : $user->institution_id);
        $taughtSubjectIds = TeacherMenuContext::taughtSubjectIds($user, $request);

        return $query->where(function ($q) use ($institutionId, $user, $taughtSubjectIds) {
            $q->where(function ($inner) use ($institutionId, $taughtSubjectIds) {
                $inner->where('institution_id', $institutionId);
                if (is_array($taughtSubjectIds)) {
                    if ($taughtSubjectIds === []) {
                        $inner->whereRaw('0 = 1');
                    } else {
                        $inner->whereIn('subject_id', $taughtSubjectIds);
                    }
                }
            })->orWhereHas('sharedWithUsers', fn ($q2) => $q2->where('user.id', $user->id));
        });
    }
}
