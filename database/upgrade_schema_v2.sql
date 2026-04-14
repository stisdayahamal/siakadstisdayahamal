-- upgrade_schema_v2_refined.sql
-- Penyelarasan skema database SIAKAD dengan standar PDDikti (Refined)

-- 1. Update kode_prodi (Column already exists)
UPDATE `prodi` SET `kode_prodi` = '01' WHERE `id_prodi` = 1; -- Hukum Keluarga Islam (Ahwal Syakhsiyyah)
UPDATE `prodi` SET `kode_prodi` = '02' WHERE `id_prodi` = 2; -- Hukum Pidana Islam (Jinayah)

-- 2. Penambahan field missing pada tabel mahasiswa
ALTER TABLE `mahasiswa`
ADD COLUMN `tempat_lahir` VARCHAR(100) AFTER `nama`,
ADD COLUMN `tgl_lahir` DATE AFTER `tempat_lahir`,
ADD COLUMN `jk` CHAR(1) AFTER `tgl_lahir`,
ADD COLUMN `agama` VARCHAR(50) AFTER `jk`,
ADD COLUMN `alamat` TEXT AFTER `agama`,
ADD COLUMN `rt` VARCHAR(5) AFTER `alamat`,
ADD COLUMN `rw` VARCHAR(5) AFTER `rt`,
ADD COLUMN `kelurahan` VARCHAR(100) AFTER `rw`,
ADD COLUMN `kecamatan` VARCHAR(100) AFTER `kelurahan`,
ADD COLUMN `kode_pos` VARCHAR(10) AFTER `kecamatan`,
ADD COLUMN `jenis_tinggal` VARCHAR(100) AFTER `kode_pos`,
ADD COLUMN `alat_transportasi` VARCHAR(100) AFTER `jenis_tinggal`,
ADD COLUMN `nama_ayah` VARCHAR(100) AFTER `nama_ibu`,
ADD COLUMN `penghasilan_ortu` VARCHAR(100) AFTER `pendidikan_ibu`,
ADD COLUMN `asal_sekolah` VARCHAR(100) AFTER `penghasilan_ortu`,
ADD COLUMN `tahun_lulus` YEAR AFTER `asal_sekolah`,
ADD COLUMN `email` VARCHAR(100) AFTER `tahun_masuk`,
ADD COLUMN `no_hp` VARCHAR(20) AFTER `email`;

-- 3. Cleanup/Update calon_mhs (Verified: mostly exists, but check types)
-- Ensure 'nama_ayah' exists if it was missing (based on check it was there)
-- Add any missing from the 40+ list if not found in check.
-- Checked: 'berkas_akte' was in list. 'no_pendaftaran' was in list.
