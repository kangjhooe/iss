-- Tambah kolom yang hilang (jika migrasi belum dijalankan di hosting).
-- Jalankan via phpMyAdmin / MySQL client pada database Anda.
-- Jika muncul "Duplicate column name", kolom itu sudah ada — lewati baris itu.
--
-- Lebih disarankan: di server hosting jalankan: php artisan migrate
--
-- Kasus serupa (tempat lain yang pakai kolom ini dan bisa error "Unknown column" jika migrasi belum jalan):
-- - institution: update profil, upload cover → cover_image, province_code, district_code, vision, mission
-- - exam_participants: generate/update nomor urut peserta → participant_order

-- ========== 1. Institution: visi, misi, cover (migration: add_vision_mission_cover_to_institution_table) ==========
ALTER TABLE `institution` ADD COLUMN `vision` TEXT NULL AFTER `description`;
ALTER TABLE `institution` ADD COLUMN `mission` TEXT NULL AFTER `vision`;
ALTER TABLE `institution` ADD COLUMN `cover_image` VARCHAR(255) NULL COMMENT 'Gambar cover/hero halaman publik' AFTER `logo`;

-- ========== 2. Institution: kode provinsi/kabupaten (migration: add_exam_participant_number_fields) ==========
ALTER TABLE `institution` ADD COLUMN `province_code` VARCHAR(2) NULL AFTER `province`;
ALTER TABLE `institution` ADD COLUMN `district_code` VARCHAR(2) NULL AFTER `district`;

-- ========== 3. Exam participants: nomor urut (migration: add_exam_participant_number_fields) ==========
ALTER TABLE `exam_participants` ADD COLUMN `participant_order` INT UNSIGNED NULL AFTER `student_id`;
