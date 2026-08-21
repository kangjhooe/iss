<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\Student;
use App\Support\StudentIdentity;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentMutationPullRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['admin', 'institution_admin'], true) && $user->institution_id;
    }

    /**
     * Get the validation rules that apply to the request.
     * User = admin sekolah TARGET (penarik). Body: origin_npsn (sekolah asal), nik (siswa di sekolah asal).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $external = $this->boolean('external');
        $targetInstitutionId = $this->user()?->institution_id;

        return [
            'external' => 'sometimes|boolean',
            'origin_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) use ($external) {
                    if ($external) {
                        return;
                    }
                    $targetInstitution = Institution::find($this->user()->institution_id);
                    if (!$targetInstitution) {
                        $fail('Sekolah Anda tidak ditemukan.');
                        return;
                    }
                    $origin = Institution::where('npsn', $value)->where('is_active', true)->first();
                    if (!$origin) {
                        $fail('Sekolah asal dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
                        return;
                    }
                    if ((int) $origin->id === (int) $targetInstitution->id) {
                        $fail('Sekolah asal harus berbeda dengan sekolah Anda.');
                        return;
                    }
                    if (!$targetInstitution->canMutateWith($origin)) {
                        $fail('Mutasi hanya dapat dilakukan antar jenjang yang sama (SD-MI, SMP-MTs, SMA-MA-SMK-MAK, PAUD-TK).');
                    }
                },
            ],
            'origin_school_name' => 'required_if:external,true|nullable|string|max:255',
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                function ($attribute, $value, $fail) use ($external, $targetInstitutionId) {
                    if ($external) {
                        $this->failIfIdentityTaken('nik', $value, $targetInstitutionId, $fail);

                        return;
                    }
                    $originNpsn = $this->input('origin_npsn');
                    $origin = Institution::where('npsn', $originNpsn)->first();
                    if (!$origin) {
                        return;
                    }
                    try {
                        $student = app(\App\Services\StudentMutationService::class)
                            ->findPullableStudentByNik($value, $origin->id);
                    } catch (\InvalidArgumentException $e) {
                        $fail($e->getMessage());
                        return;
                    }
                    $pending = \App\Models\StudentMutation::where('student_id', $student->id)
                        ->where('target_institution_id', $targetInstitutionId)
                        ->where('origin_institution_id', $origin->id)
                        ->pending()
                        ->exists();
                    if ($pending) {
                        $fail('Sudah ada permohonan tarik siswa ini yang belum diproses.');
                    }
                },
            ],
            'nisn' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
                function ($attribute, $value, $fail) use ($external, $targetInstitutionId) {
                    if (! $external || $value === null || trim((string) $value) === '') {
                        return;
                    }
                    $this->failIfIdentityTaken('nisn', $value, $targetInstitutionId, $fail);
                },
            ],
            'student_name' => 'required_if:external,true|nullable|string|max:255',
            'student_gender' => 'required_if:external,true|nullable|string|in:L,P,Laki-laki,Perempuan,Laki,Perempuan',
            'student_grade' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * @param  callable(string): void  $fail
     */
    protected function failIfIdentityTaken(string $field, mixed $value, ?int $targetInstitutionId, callable $fail): void
    {
        $label = $field === 'nik' ? 'NIK' : 'NISN';
        $value = trim((string) $value);

        $sameSchool = Student::withTrashed()
            ->where('institution_id', $targetInstitutionId)
            ->where($field, $value)
            ->first();
        if ($sameSchool) {
            $fail($label.' tersebut sudah digunakan oleh siswa lain di sekolah Anda.');

            return;
        }

        $occupant = StudentIdentity::activeOccupant($field, $value);
        if (! $occupant) {
            return;
        }

        $occupant->loadMissing('institution:id,name');
        $where = $occupant->institution?->name ?: 'sekolah lain';
        $trash = $occupant->trashed() ? ' (kotak sampah)' : '';
        $fail($label." tersebut sudah terdaftar di {$where}{$trash}. Gunakan tarik siswa, jangan input manual.");
    }

    public function messages(): array
    {
        return [
            'origin_npsn.required' => 'NPSN sekolah asal wajib diisi.',
            'origin_npsn.size' => 'NPSN harus 8 digit.',
            'origin_npsn.regex' => 'NPSN harus berupa 8 digit angka.',
            'origin_school_name.required_if' => 'Nama sekolah asal wajib diisi untuk mutasi masuk dari sekolah luar sistem.',
            'nik.required' => 'NIK siswa wajib diisi.',
            'nik.size' => 'NIK harus 16 digit.',
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'nisn.size' => 'NISN harus 10 digit.',
            'nisn.regex' => 'NISN harus berupa 10 digit angka.',
            'student_name.required_if' => 'Nama siswa wajib diisi untuk mutasi masuk dari sekolah luar.',
            'student_gender.required_if' => 'Jenis kelamin siswa wajib diisi.',
        ];
    }
}
