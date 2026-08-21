-- Berkas wajib per jalur PPDB + kunci jenis dokumen calon.
-- Jalankan di phpMyAdmin jika php artisan migrate belum dijalankan.
-- Duplicate column: lewati baris itu.

ALTER TABLE `ppdb_channels`
    ADD COLUMN `required_documents` JSON NULL AFTER `requirements`;

ALTER TABLE `ppdb_applicant_documents`
    ADD COLUMN `document_key` VARCHAR(64) NULL AFTER `name`;

ALTER TABLE `ppdb_applicant_documents`
    ADD INDEX `ppdb_docs_applicant_key_index` (`ppdb_applicant_id`, `document_key`);
