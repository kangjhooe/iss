-- Paket soal ujian: salinan soal saat dipasang, agar edit di bank tidak mengubah ujian.
-- Jalankan di phpMyAdmin / MySQL client jika artisan migrate belum dijalankan.
-- Lebih disarankan di server: php artisan migrate

ALTER TABLE `exam_questions`
    ADD COLUMN `snapshot` JSON NULL AFTER `sort_order`;
