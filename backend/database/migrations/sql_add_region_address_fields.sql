-- Alamat berantai (Provinsi → Kabupaten/Kota → Kecamatan → Desa/Kelurahan/Pekon)
-- Jalankan di phpMyAdmin / MySQL client jika artisan migrate belum dijalankan.
-- Jika kolom sudah ada, baris ALTER akan error "Duplicate column" — lewati.

ALTER TABLE `institution`
    ADD COLUMN `wilayah_province_code` VARCHAR(8) NULL AFTER `postal_code`,
    ADD COLUMN `wilayah_regency_code` VARCHAR(16) NULL AFTER `wilayah_province_code`,
    ADD COLUMN `wilayah_district_code` VARCHAR(16) NULL AFTER `wilayah_regency_code`,
    ADD COLUMN `wilayah_village_code` VARCHAR(20) NULL AFTER `wilayah_district_code`;

ALTER TABLE `student`
    ADD COLUMN `village` VARCHAR(255) NULL AFTER `address`,
    ADD COLUMN `sub_district` VARCHAR(255) NULL AFTER `village`,
    ADD COLUMN `district` VARCHAR(255) NULL AFTER `sub_district`,
    ADD COLUMN `province` VARCHAR(255) NULL AFTER `district`,
    ADD COLUMN `postal_code` VARCHAR(10) NULL AFTER `province`,
    ADD COLUMN `wilayah_province_code` VARCHAR(8) NULL AFTER `postal_code`,
    ADD COLUMN `wilayah_regency_code` VARCHAR(16) NULL AFTER `wilayah_province_code`,
    ADD COLUMN `wilayah_district_code` VARCHAR(16) NULL AFTER `wilayah_regency_code`,
    ADD COLUMN `wilayah_village_code` VARCHAR(20) NULL AFTER `wilayah_district_code`;

ALTER TABLE `employee`
    ADD COLUMN `village` VARCHAR(255) NULL AFTER `address`,
    ADD COLUMN `sub_district` VARCHAR(255) NULL AFTER `village`,
    ADD COLUMN `district` VARCHAR(255) NULL AFTER `sub_district`,
    ADD COLUMN `province` VARCHAR(255) NULL AFTER `district`,
    ADD COLUMN `postal_code` VARCHAR(10) NULL AFTER `province`,
    ADD COLUMN `wilayah_province_code` VARCHAR(8) NULL AFTER `postal_code`,
    ADD COLUMN `wilayah_regency_code` VARCHAR(16) NULL AFTER `wilayah_province_code`,
    ADD COLUMN `wilayah_district_code` VARCHAR(16) NULL AFTER `wilayah_regency_code`,
    ADD COLUMN `wilayah_village_code` VARCHAR(20) NULL AFTER `wilayah_district_code`;
