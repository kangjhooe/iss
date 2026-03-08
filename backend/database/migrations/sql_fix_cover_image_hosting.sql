-- Perbaikan cepat: tambah kolom cover_image ke tabel institution (untuk upload hero profil instansi).
-- Jalankan di phpMyAdmin / MySQL client pada database hosting (mis. u7699491_servrin).
-- Jika kolom cover_image sudah ada, akan error "Duplicate column" — abaikan.

ALTER TABLE `institution` ADD COLUMN `cover_image` VARCHAR(255) NULL COMMENT 'Gambar cover/hero halaman publik';
