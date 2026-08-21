-- Status PPDB: calon yang sudah jadi siswa di sekolah lain.
-- Jalankan di phpMyAdmin jika php artisan migrate belum dijalankan.

ALTER TABLE `ppdb_applicants`
    MODIFY COLUMN `status` ENUM(
        'draft',
        'submitted',
        'verification',
        'verified',
        'rejected',
        'passed',
        'reserve',
        'failed',
        're_registration',
        'converted',
        'cancelled',
        'accepted_elsewhere'
    ) DEFAULT 'draft';
