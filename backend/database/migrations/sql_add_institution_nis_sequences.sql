-- Perbaikan error PPDB "Jadikan Siswa":
-- SQLSTATE[42S02] Table '....institution_nis_sequences' doesn't exist
--
-- Jalankan di phpMyAdmin / MySQL client pada database hosting (mis. u7699491_servrin).
-- Lebih disarankan di server: php artisan migrate
--
-- Jika kolom nis_numbering sudah ada, baris ALTER akan error "Duplicate column" — lewati.
-- CREATE TABLE memakai IF NOT EXISTS, aman dijalankan ulang.

ALTER TABLE `institution`
    ADD COLUMN `nis_numbering` JSON NULL AFTER `admission_label`;

CREATE TABLE IF NOT EXISTS `institution_nis_sequences` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `period_key` VARCHAR(16) NOT NULL,
    `last_seq` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `institution_nis_sequences_unique` (`institution_id`, `period_key`),
    CONSTRAINT `institution_nis_sequences_institution_id_foreign`
        FOREIGN KEY (`institution_id`) REFERENCES `institution` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
