-- NIK/NISN unik per sekolah + persetujuan destinasi alumni otomatis
-- Jalankan di phpMyAdmin / MySQL client jika artisan migrate belum dijalankan.

ALTER TABLE `student` DROP INDEX `student_nik_unique`;
ALTER TABLE `student` DROP INDEX `student_nisn_unique`;
ALTER TABLE `student` ADD UNIQUE `student_institution_nik_unique` (`institution_id`, `nik`);
ALTER TABLE `student` ADD UNIQUE `student_institution_nisn_unique` (`institution_id`, `nisn`);

ALTER TABLE `alumni_destinations`
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER `notes`,
    ADD COLUMN `source` VARCHAR(32) NOT NULL DEFAULT 'manual' AFTER `status`,
    ADD COLUMN `related_student_id` BIGINT UNSIGNED NULL AFTER `source`,
    ADD COLUMN `related_institution_id` BIGINT UNSIGNED NULL AFTER `related_student_id`,
    ADD COLUMN `reviewed_by` BIGINT UNSIGNED NULL AFTER `related_institution_id`,
    ADD COLUMN `reviewed_at` TIMESTAMP NULL AFTER `reviewed_by`;

ALTER TABLE `alumni_destinations`
    ADD CONSTRAINT `alumni_destinations_related_student_id_foreign` FOREIGN KEY (`related_student_id`) REFERENCES `student` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `alumni_destinations_related_institution_id_foreign` FOREIGN KEY (`related_institution_id`) REFERENCES `institution` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `alumni_destinations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL;
