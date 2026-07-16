<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Student;
use Carbon\Carbon;

class PlaceholderEngine
{
    /**
     * Daftar placeholder yang didukung.
     */
    public static function availablePlaceholders(): array
    {
        return [
            'nomor_surat' => 'Nomor surat',
            'nama' => 'Nama subjek (siswa/pegawai)',
            'nis' => 'NIS siswa',
            'nisn' => 'NISN siswa',
            'kelas' => 'Kelas siswa',
            'alamat' => 'Alamat subjek',
            'ttl' => 'Tempat, tanggal lahir',
            'agama' => 'Agama',
            'ayah' => 'Nama ayah (siswa)',
            'ibu' => 'Nama ibu (siswa)',
            'nip_pegawai' => 'NIP guru/pegawai',
            'nuptk' => 'NUPTK guru/pegawai',
            'jabatan' => 'Jabatan guru/pegawai',
            'mata_pelajaran' => 'Mata pelajaran (guru)',
            'status_kepegawaian' => 'Status kepegawaian',
            'tipe_pegawai' => 'Tipe (Guru/Pegawai)',
            'kepala_madrasah' => 'Nama kepala madrasah/sekolah',
            'nip' => 'NIP kepala madrasah/sekolah',
            'tanggal' => 'Tanggal (angka)',
            'bulan' => 'Bulan (nama)',
            'tahun' => 'Tahun',
            'tanggal_lengkap' => 'Tanggal lengkap (Indonesia)',
            'nama_institusi' => 'Nama institusi',
            'npsn' => 'NPSN institusi',
            'alamat_institusi' => 'Alamat institusi',
            'kota' => 'Kabupaten/Kota institusi (untuk tanggal surat)',
            'kabupaten_kota' => 'Kabupaten/Kota institusi',
            'kecamatan' => 'Kecamatan institusi',
            'desa' => 'Desa/Kelurahan institusi',
            'provinsi' => 'Provinsi institusi',
        ];
    }

    /**
     * Bangun map nilai placeholder dari siswa/pegawai + institusi + nomor surat.
     */
    public function buildData(
        ?Student $student = null,
        ?Institution $institution = null,
        ?string $nomorSurat = null,
        ?Carbon $date = null,
        ?Employee $employee = null
    ): array {
        $date = $date ?? Carbon::now();
        $bulanId = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $kelas = '';
        if ($student) {
            $student->loadMissing('class');
            $kelas = $student->class?->name
                ?? $student->getAttribute('class')
                ?? '';
        }

        $person = $student ?? $employee;

        $ttl = '';
        if ($person) {
            $ttlParts = array_filter([
                $person->birth_place,
                $person->birth_date ? $person->birth_date->format('d-m-Y') : null,
            ]);
            $ttl = implode(', ', $ttlParts);
        }

        $jabatan = '';
        if ($employee) {
            $jabatan = $this->resolveJabatan($employee);
        }

        $kabupatenKota = trim((string) ($institution?->district ?? ''));

        return [
            'nomor_surat' => $nomorSurat ?? '',
            'nama' => $person?->name ?? '',
            'nis' => $student?->nis ?? '',
            'nisn' => $student?->nisn ?? '',
            'kelas' => $kelas,
            'alamat' => $person?->address ?? '',
            'ttl' => $ttl,
            'agama' => $person?->religion ?? '',
            'ayah' => $student?->father_name ?? '',
            'ibu' => $student?->mother_name ?? '',
            'nip_pegawai' => $employee?->nip ?? '',
            'nuptk' => $employee?->nuptk ?? '',
            'jabatan' => $jabatan,
            'mata_pelajaran' => $employee?->subject ?? '',
            'status_kepegawaian' => $employee?->employment_status ?? '',
            'tipe_pegawai' => $employee?->type ?? '',
            'kepala_madrasah' => $institution?->principal_name ?? '',
            'nip' => $institution?->principal_nip ?? '',
            'tanggal' => $date->format('d'),
            'bulan' => $bulanId[(int) $date->format('n')] ?? $date->format('F'),
            'tahun' => $date->format('Y'),
            'tanggal_lengkap' => $date->format('d') . ' ' . ($bulanId[(int) $date->format('n')] ?? '') . ' ' . $date->format('Y'),
            'nama_institusi' => $institution?->name ?? '',
            'npsn' => $institution?->npsn ?? '',
            'alamat_institusi' => $institution?->address ?? '',
            'kota' => $kabupatenKota,
            'kabupaten_kota' => $kabupatenKota,
            'kecamatan' => trim((string) ($institution?->sub_district ?? '')),
            'desa' => trim((string) ($institution?->village ?? '')),
            'provinsi' => trim((string) ($institution?->province ?? '')),
        ];
    }

    private function resolveJabatan(Employee $employee): string
    {
        $type = trim((string) ($employee->type ?? ''));
        $subject = trim((string) ($employee->subject ?? ''));

        if ($type === 'Guru' && $subject !== '') {
            return 'Guru ' . $subject;
        }

        if ($type !== '') {
            return $type;
        }

        return $employee->employment_status ?? '';
    }

    /**
     * Ganti semua {{placeholder}} di HTML dengan nilai aktual.
     */
    public function replace(string $html, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return array_key_exists($key, $data) ? (string) $data[$key] : $matches[0];
        }, $html);
    }

    /**
     * Ganti placeholder dari siswa/pegawai/institusi sekaligus.
     *
     * @param  bool  $keepNomorPlaceholder  Jika true, {{nomor_surat}} tidak diganti (untuk draft).
     */
    public function replaceFromModels(
        string $html,
        ?Student $student = null,
        ?Institution $institution = null,
        ?string $nomorSurat = null,
        ?Carbon $date = null,
        array $extra = [],
        bool $keepNomorPlaceholder = false,
        ?Employee $employee = null
    ): string {
        $data = array_merge(
            $this->buildData($student, $institution, $nomorSurat, $date, $employee),
            $extra
        );

        if ($keepNomorPlaceholder) {
            unset($data['nomor_surat']);
        }

        return $this->replace($html, $data);
    }

    /**
     * Sisipkan nomor resmi ke HTML (mengganti {{nomor_surat}} yang tersisa).
     */
    public function applyNomor(string $html, string $nomor): string
    {
        return preg_replace('/\{\{\s*nomor_surat\s*\}\}/', $nomor, $html) ?? $html;
    }
}
