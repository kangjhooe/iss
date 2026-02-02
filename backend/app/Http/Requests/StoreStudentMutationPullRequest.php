<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\Student;
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
     * User = admin sekolah TARGET (penarik). Body: origin_npsn (sekolah asal), nisn (siswa di sekolah asal).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) {
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
            'nisn' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $targetInstitutionId = $this->user()->institution_id;
                    $originNpsn = $this->input('origin_npsn');
                    $origin = Institution::where('npsn', $originNpsn)->first();
                    if (!$origin) {
                        return;
                    }
                    $student = Student::where('nisn', $value)
                        ->where('institution_id', $origin->id)
                        ->where('status', 'Aktif')
                        ->first();
                    if (!$student) {
                        $fail('Siswa dengan NISN tersebut tidak ditemukan di sekolah asal atau status tidak aktif.');
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
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_npsn.required' => 'NPSN sekolah asal wajib diisi.',
            'origin_npsn.size' => 'NPSN harus 8 digit.',
            'origin_npsn.regex' => 'NPSN harus berupa 8 digit angka.',
            'nisn.required' => 'NISN siswa wajib diisi.',
        ];
    }
}
