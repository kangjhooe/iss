<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentMutationRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) {
                    $originInstitution = Institution::find($this->user()->institution_id);
                    if (!$originInstitution) {
                        $fail('Sekolah asal tidak ditemukan.');
                        return;
                    }
                    $target = Institution::where('npsn', $value)->where('is_active', true)->first();
                    if (!$target) {
                        $fail('Sekolah tujuan dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
                        return;
                    }
                    if ((int) $target->id === (int) $originInstitution->id) {
                        $fail('Sekolah tujuan harus berbeda dengan sekolah asal.');
                        return;
                    }
                    if (!$originInstitution->canMutateWith($target)) {
                        $fail('Mutasi hanya dapat dilakukan antar jenjang yang sama (SD-MI, SMP-MTs, SMA-MA-SMK-MAK, PAUD-TK).');
                    }
                },
            ],
            'nisn' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $institutionId = $this->user()->institution_id;
                    $student = Student::where('nisn', $value)
                        ->where('institution_id', $institutionId)
                        ->where('status', 'Aktif')
                        ->first();
                    if (!$student) {
                        $fail('Siswa dengan NISN tersebut tidak ditemukan di sekolah Anda atau status tidak aktif.');
                        return;
                    }
                    $targetNpsn = $this->input('target_npsn');
                    $target = Institution::where('npsn', $targetNpsn)->first();
                    if ($target) {
                        $pending = \App\Models\StudentMutation::where('student_id', $student->id)
                            ->where('target_institution_id', $target->id)
                            ->pending()
                            ->exists();
                        if ($pending) {
                            $fail('Siswa ini sudah memiliki permohonan mutasi pending ke sekolah tujuan tersebut.');
                        }
                    }
                },
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_npsn.required' => 'NPSN sekolah tujuan wajib diisi.',
            'target_npsn.size' => 'NPSN harus 8 digit.',
            'target_npsn.regex' => 'NPSN harus berupa 8 digit angka.',
            'nisn.required' => 'NISN siswa wajib diisi.',
        ];
    }
}
