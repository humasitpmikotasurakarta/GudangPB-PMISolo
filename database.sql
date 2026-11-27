-- ============================================================================
-- SISTEM INFORMASI MANAJEMEN STOK GUDANG - PMI KOTA SURAKARTA
-- File: database.sql (Folder: GudangPBPMISolo)
-- Database Engine: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+
-- Deskripsi: Skema Tabel Lengkap & Data Awal (Seed Data) Standar Gudang PB
--            Palang Merah Indonesia (PMI) Kota Surakarta
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `pmi_surakarta_warehouse`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pmi_surakarta_warehouse`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. TABEL USERS (Autentikasi & Multi-Level Role)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` VARCHAR(32) NOT NULL,
  `username` VARCHAR(64) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(128) NOT NULL,
  `role` ENUM('ADMIN', 'PETUGAS GUDANG', 'PIMPINAN') NOT NULL DEFAULT 'PETUGAS GUDANG',
  `jabatan` VARCHAR(128) DEFAULT '-',
  `email` VARCHAR(128) DEFAULT '-',
  `no_hp` VARCHAR(40) DEFAULT '-',
  `status_aktif` ENUM('AKTIF', 'NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password default (SHA-256 & Bcrypt didukung otomatis oleh api.php):
-- admin    -> admin123
-- petugas  -> petugas123
-- pimpinan -> pimpinan123
INSERT INTO `users` (`id_user`, `username`, `password_hash`, `nama_lengkap`, `role`, `jabatan`, `email`, `no_hp`, `status_aktif`, `last_login`, `created_at`) VALUES
('USR-ADMIN-001', 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Administrator Logistik PMI', 'ADMIN', 'KaSie Kesiapsiagaan dan Respon', 'logistik@pmisurakarta.or.id', '(0271) 646505', 'AKTIF', NOW(), NOW()),
('USR-PETUGAS-001', 'petugas', '838c84f89c007d084c602a8d3c9f23f1e5b27f9f5d5e1c3b7a9e0f2d4c6b8a10', 'Indro Sri Widodo', 'PETUGAS GUDANG', 'Koordinator Operasional Gudang PB', 'gudang@pmisurakarta.or.id', '081234567890', 'AKTIF', NOW(), NOW()),
('USR-PIMPINAN-001', 'pimpinan', 'd29fd3833f1b3f0e0e740b1812032ba3c123456789abcdef0123456789abcdef', 'Wanto, S.KM.', 'PIMPINAN', 'KaBag PB & Relawan PMI Kota Surakarta', 'markas@pmisurakarta.or.id', '(0271) 646505', 'AKTIF', NOW(), NOW());

-- ----------------------------------------------------------------------------
-- 2. TABEL SESSIONS (Token Sesi Login Aktif)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `token` VARCHAR(96) NOT NULL,
  `id_user` VARCHAR(32) NOT NULL,
  `username` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  KEY `idx_sessions_user` (`id_user`),
  KEY `idx_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. TABEL SETTINGS (Konfigurasi Instansi & Penandatangan Lembar Kerja ISO)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id_setting` VARCHAR(32) NOT NULL,
  `setting_key` VARCHAR(64) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_setting`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id_setting`, `setting_key`, `setting_value`, `keterangan`) VALUES
('SET-01', 'NAMA_INSTANSI', 'PALANG MERAH INDONESIA KOTA SURAKARTA', 'Nama resmi instansi pada kop laporan'),
('SET-02', 'UNIT_GUDANG', 'GUDANG LOGISTIK & PENANGGULANGAN BENCANA (PB)', 'Nama unit kerja gudang'),
('SET-03', 'ALAMAT_INSTANSI', 'Jl. Kolonel Sutarto No. 58, Jebres, Kota Surakarta, Jawa Tengah 57126', 'Alamat lengkap markas/gudang'),
('SET-04', 'TELEPON_INSTANSI', '(0271) 646505 | Email: kota_surakarta@pmi.or.id', 'Kontak resmi instansi'),
('SET-05', 'NAMA_KEPALA_MARKAS', 'Wanto, S.KM.', 'Pejabat penandatangan laporan (Mengetahui, KaBag PB & Relawan)'),
('SET-06', 'NAMA_KOORDINATOR_GUDANG', 'Indro Sri Widodo', 'Koordinator penandatangan laporan (Dibuat oleh : KaSie Kesiapsiagaan dan respon)'),
('SET-07', 'THRESHOLD_EXPIRED_SEGERA_HARI', '30', 'Batas hari status Segera Kedaluwarsa (1-30 hari)'),
('SET-08', 'THRESHOLD_EXPIRED_PERHATIAN_HARI', '90', 'Batas hari status Perhatian Kedaluwarsa (31-90 hari)');

-- ----------------------------------------------------------------------------
-- 4. TABEL MASTER_SUMBER (Master Sumber Perolehan Barang)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `master_sumber`;
CREATE TABLE `master_sumber` (
  `id_donor` VARCHAR(32) NOT NULL,
  `kode_donor` VARCHAR(32) NOT NULL,
  `nama_donor` VARCHAR(128) NOT NULL,
  `jenis_donor` VARCHAR(64) DEFAULT 'Internal PMI',
  `kontak` VARCHAR(128) DEFAULT '-',
  `alamat` VARCHAR(255) DEFAULT '-',
  `keterangan` VARCHAR(255) DEFAULT '-',
  `status_aktif` ENUM('AKTIF', 'NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_donor`),
  UNIQUE KEY `uk_sumber_kode` (`kode_donor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `master_sumber` (`id_donor`, `kode_donor`, `nama_donor`, `jenis_donor`, `kontak`, `alamat`, `keterangan`, `status_aktif`) VALUES
('DNR-001', 'PMI-PST', 'Palang Merah Indonesia (PMI) Pusat', 'Internal PMI', '(021) 7992325', 'Jl. Jend. Gatot Subroto Kav. 96, Jakarta', 'Bantuan Logistik Darurat PMI Pusat', 'AKTIF'),
('DNR-002', 'PMI-JTG', 'PMI Provinsi Jawa Tengah', 'Internal PMI', '(024) 76744989', 'Jl. Arumsari, Sambiroto, Semarang', 'Dropping Logistik Regional Jawa Tengah', 'AKTIF'),
('DNR-003', 'PMI-SKA', 'Pengadaan Mandiri PMI Kota Surakarta', 'Anggaran Mandiri', '(0271) 646505', 'Jl. Kolonel Sutarto No. 58, Surakarta', 'Pembelian Rutin & Tanggap Darurat', 'AKTIF'),
('DNR-004', 'IFRC', 'IFRC / ICRC (Federasi Palang Merah Internasional)', 'Mitra Internasional', '+62 21 79191841', 'Jakarta', 'Hibah Bantuan Kemanusiaan Internasional', 'AKTIF'),
('DNR-005', 'BNPB-BPBD', 'BNPB / BPBD Kota Surakarta', 'Pemerintah', '(0271) 7464727', 'Kota Surakarta', 'Dukungan Logistik Penanggulangan Bencana Daerah', 'AKTIF'),
('DNR-006', 'CSR-DONATUR', 'Donatur Masyarakat & Mitra CSR Perusahaan', 'Donatur Umum', 'Humas PMI Surakarta', 'Kota Surakarta', 'Sumbangan Kemanusiaan Masyarakat/Swasta', 'AKTIF'),
('DNR-007', 'HVC', 'HVC / Program Kesiapsiagaan Bencana PMI', 'Program Kemitraan', '(0271) 646505', 'Kota Surakarta', 'Dukungan Logistik Program HVC', 'AKTIF');

-- ----------------------------------------------------------------------------
-- 5. TABEL MASTER_BARANG (Master Data Barang Gudang PB)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `master_barang`;
CREATE TABLE `master_barang` (
  `id_barang` VARCHAR(32) NOT NULL,
  `kode_barang` VARCHAR(32) NOT NULL,
  `nama_barang` VARCHAR(180) NOT NULL,
  `kategori` ENUM('FOOD ITEMS', 'NON FOOD', 'OBAT') NOT NULL DEFAULT 'NON FOOD',
  `subkategori` VARCHAR(96) DEFAULT 'Logistik Umum',
  `satuan` VARCHAR(32) NOT NULL DEFAULT 'Unit',
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sumber_donor` VARCHAR(64) DEFAULT '-',
  `stok_minimum` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `lokasi_rak` VARCHAR(48) DEFAULT 'GUDANG-PB',
  `has_expiry` ENUM('YA', 'TIDAK') NOT NULL DEFAULT 'TIDAK',
  `keterangan` TEXT DEFAULT NULL,
  `status_aktif` ENUM('AKTIF', 'NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_barang`),
  UNIQUE KEY `uk_master_kode` (`kode_barang`),
  KEY `idx_master_kategori` (`kategori`),
  KEY `idx_master_nama` (`nama_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `master_barang` (`id_barang`, `kode_barang`, `nama_barang`, `kategori`, `subkategori`, `satuan`, `harga_unit`, `sumber_donor`, `stok_minimum`, `lokasi_rak`, `has_expiry`, `keterangan`, `status_aktif`) VALUES
('BRG-FD-0001', 'FD-0001', 'FOOD PACKAGE (Paket Pangan Darurat)', 'FOOD ITEMS', 'Paket Pangan Darurat', 'karung', 320000.00, 'HVC', 25.00, 'RAK-A1', 'YA', 'Paket pangan darurat standar PMI', 'AKTIF'),
('BRG-FD-0002', 'FD-0002', 'Beras Premium Kemasan 5 Kg', 'FOOD ITEMS', 'Makanan Pokok', 'Sak', 72500.00, 'CSR-DONATUR', 25.00, 'RAK-A1', 'YA', 'Beras bantuan logistik bencana', 'AKTIF'),
('BRG-FD-0003', 'FD-0003', 'Mie Instan Kuah (Karton 40 Bungkus)', 'FOOD ITEMS', 'Makanan Siap Saji', 'Dus', 112000.00, 'PMI-JTG', 15.00, 'RAK-A2', 'YA', 'Mie instan distribusi dapur umum', 'AKTIF'),
('BRG-FD-0004', 'FD-0004', 'Sarden Kaleng 425gr', 'FOOD ITEMS', 'Lauk Siap Saji', 'Kaleng', 24000.00, 'PMI-PST', 40.00, 'RAK-A4', 'YA', 'Sarden siap saji', 'AKTIF'),
('BRG-FD-0005', 'FD-0005', 'Biskuit Energi Darurat / PMT', 'FOOD ITEMS', 'Makanan Tambahan', 'Pak', 15000.00, 'PMI-SKA', 20.00, 'RAK-A5', 'YA', 'Biskuit PMT pengungsian', 'AKTIF'),
('BRG-NF-0001', 'NF-0001', 'Hygiene Kit', 'NON FOOD', 'Paket Sanitasi Keluarga', 'Paket', 210000.00, 'PMI-PST', 20.00, 'RAK-B1', 'TIDAK', 'Paket kebersihan keluarga terdampak bencana', 'AKTIF'),
('BRG-NF-0002', 'NF-0002', 'Kitchen Set', 'NON FOOD', 'Perlengkapan Dapur Keluarga', 'Paket', 275000.00, 'PMI-JTG', 15.00, 'RAK-B2', 'TIDAK', 'Paket peralatan masak darurat keluarga', 'AKTIF'),
('BRG-NF-0003', 'NF-0003', 'Trapaulin (Terpal Shelter PMI)', 'NON FOOD', 'Shelter & Tenda', 'Pcs', 245000.00, 'IFRC', 10.00, 'RAK-B3', 'TIDAK', 'Terpal pelindung hunian darurat standar PMI/IFRC', 'AKTIF'),
('BRG-NF-0004', 'NF-0004', 'Matras', 'NON FOOD', 'Perlengkapan Tidur', 'Pcs', 75000.00, 'PMI-PST', 15.00, 'RAK-B4', 'TIDAK', 'Matras alas tidur pengungsian', 'AKTIF'),
('BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 'Paket Bantuan Keluarga', 'Paket', 385000.00, 'PMI-JTG', 10.00, 'RAK-B1', 'TIDAK', 'Paket perlengkapan keluarga standar PMI', 'AKTIF'),
('BRG-NF-0006', 'NF-0006', 'Baby Kit', 'NON FOOD', 'Paket Perlengkapan Bayi', 'Paket', 195000.00, 'PMI-JTG', 10.00, 'RAK-B2', 'TIDAK', 'Paket kebutuhan bayi dan balita darurat', 'AKTIF'),
('BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 'Perlengkapan PHBS', 'Pcs', 4500.00, 'CSR-DONATUR', 100.00, 'RAK-B5', 'YA', 'Sabun mandi batang untuk paket PHBS', 'AKTIF'),
('BRG-NF-0008', 'NF-0008', 'Sikat Gigi', 'NON FOOD', 'Perlengkapan PHBS', 'Pcs', 5500.00, 'CSR-DONATUR', 50.00, 'RAK-B5', 'TIDAK', 'Sikat gigi dewasa/anak untuk paket PHBS', 'AKTIF'),
('BRG-NF-0009', 'NF-0009', 'Kacamata Google (APD)', 'NON FOOD', 'Alat Pelindung Diri', 'Pcs', 45000.00, 'PMI-PST', 20.00, 'RAK-B6', 'TIDAK', 'Kacamata pelindung medis dan respons bencana', 'AKTIF'),
('BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 'Alat Kesehatan & APD', 'Box', 35000.00, 'PMI-JTG', 15.00, 'RAK-C1', 'TIDAK', 'Masker pelindung operasi & tanggap darurat', 'AKTIF'),
('BRG-OB-0002', 'OB-0002', 'Paracetamol 500mg (Box 100 Kaplet)', 'OBAT', 'Analgesik & Antipiretik', 'Box', 38000.00, 'PMI-SKA', 15.00, 'RAK-C1', 'YA', 'Obat penurun demam dan pereda nyeri', 'AKTIF'),
('BRG-OB-0003', 'OB-0003', 'Povidone Iodine 10% 300ml (Betadine)', 'OBAT', 'Antiseptik Luka', 'Botol', 62000.00, 'PMI-SKA', 10.00, 'RAK-C2', 'YA', 'Cairan antiseptik pertolongan pertama', 'AKTIF'),
('BRG-OB-0004', 'OB-0004', 'Kasa Steril Hidrofil 16x16cm (Box 16 Lembar)', 'OBAT', 'Pembalut Luka', 'Box', 14500.00, 'PMI-JTG', 30.00, 'RAK-C3', 'YA', 'Kasa steril perawatan luka PP', 'AKTIF'),
('BRG-OB-0005', 'OB-0005', 'Oralit 200ml Sachet (Box 100 Sachet)', 'OBAT', 'Cairan Rehidrasi', 'Box', 48000.00, 'PMI-SKA', 12.00, 'RAK-C4', 'YA', 'Garam rehidrasi oral penanganan diare darurat', 'AKTIF');

-- ----------------------------------------------------------------------------
-- 6. TABEL TRANSAKSI_MASUK (Goods Received Note / GRN)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `transaksi_masuk`;
CREATE TABLE `transaksi_masuk` (
  `id_masuk` VARCHAR(32) NOT NULL,
  `no_transaksi` VARCHAR(48) NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_barang` VARCHAR(32) NOT NULL,
  `kode_barang` VARCHAR(32) NOT NULL,
  `nama_barang` VARCHAR(180) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL,
  `jumlah_masuk` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(32) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `sumber_donor` VARCHAR(96) DEFAULT '-',
  `no_grn` VARCHAR(64) DEFAULT '-',
  `no_request` VARCHAR(64) DEFAULT '-',
  `expired_date` DATE DEFAULT NULL,
  `dokumen_url` VARCHAR(255) DEFAULT '',
  `catatan` TEXT DEFAULT NULL,
  `created_by` VARCHAR(64) DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_masuk`),
  KEY `idx_masuk_barang` (`id_barang`),
  KEY `idx_masuk_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaksi_masuk` (`id_masuk`, `no_transaksi`, `tanggal`, `id_barang`, `kode_barang`, `nama_barang`, `kategori`, `jumlah_masuk`, `satuan`, `harga_unit`, `total_nilai`, `sumber_donor`, `no_grn`, `no_request`, `expired_date`, `dokumen_url`, `catatan`, `created_by`) VALUES
('IN-INIT-001', 'BM-202611-0001', '2026-11-01', 'BRG-FD-0001', 'FD-0001', 'FOOD PACKAGE (Paket Pangan Darurat)', 'FOOD ITEMS', 500.00, 'karung', 320000.00, 160000000.00, 'HVC', 'GRN/PMI-SKA/PB/001', 'REQ-PB-001', DATE_ADD(CURDATE(), INTERVAL 150 DAY), '', 'Saldo Awal Food Package Program HVC Gudang PB', 'admin'),
('IN-INIT-002', 'BM-202611-0002', '2026-11-02', 'BRG-FD-0002', 'FD-0002', 'Beras Premium Kemasan 5 Kg', 'FOOD ITEMS', 120.00, 'Sak', 72500.00, 8700000.00, 'CSR-DONATUR', 'GRN/PMI-SKA/PB/002', 'REQ-PB-002', DATE_ADD(CURDATE(), INTERVAL 210 DAY), '', 'Penerimaan Beras Donasi Logistik', 'admin'),
('IN-INIT-003', 'BM-202611-0003', '2026-11-03', 'BRG-FD-0003', 'FD-0003', 'Mie Instan Kuah (Karton 40 Bungkus)', 'FOOD ITEMS', 45.00, 'Dus', 112000.00, 5040000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/003', 'REQ-PB-003', DATE_ADD(CURDATE(), INTERVAL 65 DAY), '', 'Stok Mie Instan Dapur Umum', 'admin'),
('IN-INIT-004', 'BM-202611-0004', '2026-11-04', 'BRG-FD-0004', 'FD-0004', 'Sarden Kaleng 425gr', 'FOOD ITEMS', 28.00, 'Kaleng', 24000.00, 672000.00, 'PMI-PST', 'GRN/PMI-SKA/PB/004', 'REQ-PB-004', DATE_ADD(CURDATE(), INTERVAL 18 DAY), '', 'Batch Sarden Mendekati Expired (< 30 Hari)', 'admin'),
('IN-INIT-005', 'BM-202611-0005', '2026-11-05', 'BRG-FD-0005', 'FD-0005', 'Biskuit Energi Darurat / PMT', 'FOOD ITEMS', 12.00, 'Pak', 15000.00, 180000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/005', 'REQ-PB-005', DATE_SUB(CURDATE(), INTERVAL 12 DAY), '', 'Batch Biskuit Contoh Sudah Kedaluwarsa', 'admin'),
('IN-INIT-006', 'BM-202611-0006', '2026-11-06', 'BRG-NF-0001', 'NF-0001', 'Hygiene Kit', 'NON FOOD', 75.00, 'Paket', 210000.00, 15750000.00, 'PMI-PST', 'GRN/PMI-SKA/PB/006', 'REQ-PB-006', NULL, '', 'Saldo Hygiene Kit Gudang PB PMI Surakarta', 'admin'),
('IN-INIT-007', 'BM-202611-0007', '2026-11-07', 'BRG-NF-0002', 'NF-0002', 'Kitchen Set', 'NON FOOD', 50.00, 'Paket', 275000.00, 13750000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/007', 'REQ-PB-007', NULL, '', 'Saldo Kitchen Set Gudang PB', 'admin'),
('IN-INIT-008', 'BM-202611-0008', '2026-11-08', 'BRG-NF-0003', 'NF-0003', 'Trapaulin (Terpal Shelter PMI)', 'NON FOOD', 10.00, 'Pcs', 245000.00, 2450000.00, 'IFRC', 'GRN/PMI-SKA/PB/008', 'REQ-PB-008', NULL, '', 'Stok Trapaulin Gudang PB (Batas Minimum)', 'admin'),
('IN-INIT-009', 'BM-202611-0009', '2026-11-09', 'BRG-NF-0004', 'NF-0004', 'Matras', 'NON FOOD', 35.00, 'Pcs', 75000.00, 2625000.00, 'PMI-PST', 'GRN/PMI-SKA/PB/009', 'REQ-PB-009', NULL, '', 'Stok Matras Gudang PB', 'admin'),
('IN-INIT-010', 'BM-202611-0010', '2026-11-10', 'BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 15.00, 'Paket', 385000.00, 5775000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/010', 'REQ-PB-010', NULL, '', 'Dropping Family Kit dari PMI Provinsi Jawa Tengah', 'admin'),
('IN-INIT-011', 'BM-202611-0011', '2026-11-11', 'BRG-NF-0006', 'NF-0006', 'Baby Kit', 'NON FOOD', 15.00, 'Paket', 195000.00, 2925000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/011', 'REQ-PB-011', NULL, '', 'Dropping Baby Kit dari PMI Provinsi Jawa Tengah', 'admin'),
('IN-INIT-012', 'BM-202611-0012', '2026-11-12', 'BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 1578.00, 'Pcs', 4500.00, 7101000.00, 'CSR-DONATUR', 'GRN/PMI-SKA/PB/012', 'REQ-PB-012', DATE_ADD(CURDATE(), INTERVAL 75 DAY), '', 'Stok Sabun Mandi Gudang PB', 'admin'),
('IN-INIT-013', 'BM-202611-0013', '2026-11-13', 'BRG-NF-0008', 'NF-0008', 'Sikat Gigi', 'NON FOOD', 347.00, 'Pcs', 5500.00, 1908500.00, 'CSR-DONATUR', 'GRN/PMI-SKA/PB/013', 'REQ-PB-013', NULL, '', 'Stok Sikat Gigi Gudang PB', 'admin'),
('IN-INIT-014', 'BM-202611-0014', '2026-11-14', 'BRG-NF-0009', 'NF-0009', 'Kacamata Google (APD)', 'NON FOOD', 169.00, 'Pcs', 45000.00, 7605000.00, 'PMI-PST', 'GRN/PMI-SKA/PB/014', 'REQ-PB-014', NULL, '', 'Stok Kacamata Google APD Gudang PB', 'admin'),
('IN-INIT-015', 'BM-202611-0015', '2026-11-15', 'BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 78.00, 'Box', 35000.00, 2730000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/015', 'REQ-PB-015', NULL, '', 'Stok Masker Medis Gudang PB', 'admin'),
('IN-INIT-016', 'BM-202611-0016', '2026-11-16', 'BRG-OB-0002', 'OB-0002', 'Paracetamol 500mg (Box 100 Kaplet)', 'OBAT', 50.00, 'Box', 38000.00, 1900000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/016', 'REQ-PB-016', DATE_ADD(CURDATE(), INTERVAL 240 DAY), '', 'Stok Obat Pelayanan Kesehatan Darurat', 'admin'),
('IN-INIT-017', 'BM-202611-0017', '2026-11-17', 'BRG-OB-0003', 'OB-0003', 'Povidone Iodine 10% 300ml (Betadine)', 'OBAT', 32.00, 'Botol', 62000.00, 1984000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/017', 'REQ-PB-017', DATE_ADD(CURDATE(), INTERVAL 55 DAY), '', 'Antiseptik Luka Tim Pertolongan Pertama', 'admin'),
('IN-INIT-018', 'BM-202611-0018', '2026-11-18', 'BRG-OB-0004', 'OB-0004', 'Kasa Steril Hidrofil 16x16cm (Box 16 Lembar)', 'OBAT', 15.00, 'Box', 14500.00, 217500.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/018', 'REQ-PB-018', DATE_ADD(CURDATE(), INTERVAL 20 DAY), '', 'Stok Menipis & Segera Kedaluwarsa', 'admin'),
('IN-INIT-019', 'BM-202611-0019', '2026-11-19', 'BRG-OB-0005', 'OB-0005', 'Oralit 200ml Sachet (Box 100 Sachet)', 'OBAT', 40.00, 'Box', 48000.00, 1920000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/019', 'REQ-PB-019', DATE_ADD(CURDATE(), INTERVAL 300 DAY), '', 'Persediaan Medis Tanggap Darurat', 'admin');

-- ----------------------------------------------------------------------------
-- 7. TABEL TRANSAKSI_KELUAR (Goods Delivery Note / GDN)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `transaksi_keluar`;
CREATE TABLE `transaksi_keluar` (
  `id_keluar` VARCHAR(32) NOT NULL,
  `no_transaksi` VARCHAR(48) NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_barang` VARCHAR(32) NOT NULL,
  `kode_barang` VARCHAR(32) NOT NULL,
  `nama_barang` VARCHAR(180) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL,
  `jumlah_keluar` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(32) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `tujuan_distribusi` VARCHAR(160) NOT NULL,
  `nama_penerima` VARCHAR(128) NOT NULL,
  `no_gdn` VARCHAR(64) DEFAULT '-',
  `no_request` VARCHAR(64) DEFAULT '-',
  `penanggung_jawab` VARCHAR(128) DEFAULT '-',
  `dokumen_url` VARCHAR(255) DEFAULT '',
  `catatan` TEXT DEFAULT NULL,
  `created_by` VARCHAR(64) DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_keluar`),
  KEY `idx_keluar_barang` (`id_barang`),
  KEY `idx_keluar_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaksi_keluar` (`id_keluar`, `no_transaksi`, `tanggal`, `id_barang`, `kode_barang`, `nama_barang`, `kategori`, `jumlah_keluar`, `satuan`, `harga_unit`, `total_nilai`, `tujuan_distribusi`, `nama_penerima`, `no_gdn`, `no_request`, `penanggung_jawab`, `dokumen_url`, `catatan`, `created_by`) VALUES
('OUT-INIT-001', 'BK-202611-0001', '2026-11-20', 'BRG-FD-0001', 'FD-0001', 'FOOD PACKAGE (Paket Pangan Darurat)', 'FOOD ITEMS', 5.00, 'karung', 320000.00, 1600000.00, 'Bencana Kebakaran Kel. Nusukan, Banjarsari', 'Warga Terdampak Kebakaran Kel. Nusukan', 'GDN/PMI-SKA/PB/01', 'REQ/PB/01', 'Indro Sri Widodo', '', 'Distribusi Bantuan Logistik Kebakaran', 'petugas'),
('OUT-INIT-002', 'BK-202611-0002', '2026-11-21', 'BRG-NF-0001', 'NF-0001', 'Hygiene Kit', 'NON FOOD', 10.00, 'Paket', 210000.00, 2100000.00, 'Warga Terdampak Banjir Kel. Joyosuran & Sangkrah', 'Posko Bantuan Kel. Joyosuran', 'GDN/PMI-SKA/PB/02', 'REQ/PB/02', 'Indro Sri Widodo', '', 'Distribusi Hygiene Kit Tanggap Darurat Banjir', 'petugas'),
('OUT-INIT-003', 'BK-202611-0003', '2026-11-22', 'BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 8.00, 'Paket', 385000.00, 3080000.00, 'Distribusi Kel. Gandekan, Manahan & Pucangsawit', 'Karyawan & Warga Terdampak Banjir', 'GDN/PMI-SKA/PB/03', 'REQ/PB/03', 'Indro Sri Widodo', '', '4 Pkt Karyawan Terdampak, 3 Pkt Kel. Gandekan, 1 Pkt Kel. Manahan', 'petugas'),
('OUT-INIT-004', 'BK-202611-0004', '2026-11-23', 'BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 21.00, 'Box', 35000.00, 735000.00, 'Respons Erupsi APG Merapi & Kegiatan Kemanusiaan', 'Tim Respon Erupsi APG Merapi', 'GDN/PMI-SKA/PB/04', 'REQ/PB/04', 'Indro Sri Widodo', '', '15 Box Respon Erupsi APG Merapi, 6 Box Giat Kemanusiaan', 'petugas');

-- ----------------------------------------------------------------------------
-- 8. TABEL DISTRIBUSI (Log Realisasi Distribusi Bantuan Kemanusiaan)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `distribusi`;
CREATE TABLE `distribusi` (
  `id_distribusi` VARCHAR(32) NOT NULL,
  `no_distribusi` VARCHAR(48) NOT NULL,
  `id_keluar` VARCHAR(32) NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_barang` VARCHAR(32) NOT NULL,
  `kode_barang` VARCHAR(32) NOT NULL,
  `nama_barang` VARCHAR(180) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL,
  `jumlah` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(32) NOT NULL,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `tujuan_distribusi` VARCHAR(160) NOT NULL,
  `nama_penerima` VARCHAR(128) NOT NULL,
  `no_gdn` VARCHAR(64) DEFAULT '-',
  `no_request` VARCHAR(64) DEFAULT '-',
  `penanggung_jawab` VARCHAR(128) DEFAULT '-',
  `status_distribusi` VARCHAR(32) DEFAULT 'TERDISTRIBUSI',
  `catatan` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_distribusi`),
  KEY `idx_dist_keluar` (`id_keluar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `distribusi` (`id_distribusi`, `no_distribusi`, `id_keluar`, `tanggal`, `id_barang`, `kode_barang`, `nama_barang`, `kategori`, `jumlah`, `satuan`, `total_nilai`, `tujuan_distribusi`, `nama_penerima`, `no_gdn`, `no_request`, `penanggung_jawab`, `status_distribusi`, `catatan`) VALUES
('DST-INIT-001', 'DIST-202611-0001', 'OUT-INIT-001', '2026-11-20', 'BRG-FD-0001', 'FD-0001', 'FOOD PACKAGE (Paket Pangan Darurat)', 'FOOD ITEMS', 5.00, 'karung', 1600000.00, 'Bencana Kebakaran Kel. Nusukan, Banjarsari', 'Warga Terdampak Kebakaran Kel. Nusukan', 'GDN/PMI-SKA/PB/01', 'REQ/PB/01', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi Bantuan Logistik Kebakaran'),
('DST-INIT-002', 'DIST-202611-0002', 'OUT-INIT-002', '2026-11-21', 'BRG-NF-0001', 'NF-0001', 'Hygiene Kit', 'NON FOOD', 10.00, 'Paket', 2100000.00, 'Warga Terdampak Banjir Kel. Joyosuran & Sangkrah', 'Posko Bantuan Kel. Joyosuran', 'GDN/PMI-SKA/PB/02', 'REQ/PB/02', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi Hygiene Kit Tanggap Darurat Banjir'),
('DST-INIT-003', 'DIST-202611-0003', 'OUT-INIT-003', '2026-11-22', 'BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 8.00, 'Paket', 3080000.00, 'Distribusi Kel. Gandekan, Manahan & Pucangsawit', 'Karyawan & Warga Terdampak Banjir', 'GDN/PMI-SKA/PB/03', 'REQ/PB/03', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi Family Kit'),
('DST-INIT-004', 'DIST-202611-0004', 'OUT-INIT-004', '2026-11-23', 'BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 21.00, 'Box', 735000.00, 'Respons Erupsi APG Merapi & Kegiatan Kemanusiaan', 'Tim Respon Erupsi APG Merapi', 'GDN/PMI-SKA/PB/04', 'REQ/PB/04', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi Masker Medis');

-- ----------------------------------------------------------------------------
-- 9. TABEL STOCK_OPNAME (Pemeriksaan Fisik & Persetujuan Admin)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `stock_opname`;
CREATE TABLE `stock_opname` (
  `id_opname` VARCHAR(32) NOT NULL,
  `no_opname` VARCHAR(48) NOT NULL,
  `tanggal_jadwal` DATE NOT NULL,
  `tanggal_periksa` DATE NOT NULL,
  `id_barang` VARCHAR(32) NOT NULL,
  `kode_barang` VARCHAR(32) NOT NULL,
  `nama_barang` VARCHAR(180) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL,
  `satuan` VARCHAR(32) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `stok_sistem` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stok_fisik` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selisih` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `nilai_selisih` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alasan_selisih` TEXT DEFAULT NULL,
  `petugas_pemeriksa` VARCHAR(128) NOT NULL,
  `status_persetujuan` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
  `disetujui_oleh` VARCHAR(128) DEFAULT '-',
  `tanggal_persetujuan` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_opname`),
  KEY `idx_opname_barang` (`id_barang`),
  KEY `idx_opname_status` (`status_persetujuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. TABEL STOCK_BALANCE (Rekapitulasi Saldo Stok Real-Time)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `stock_balance`;
CREATE TABLE `stock_balance` (
  `id_balance` VARCHAR(40) NOT NULL,
  `id_barang` VARCHAR(32) NOT NULL,
  `kode_barang` VARCHAR(32) NOT NULL,
  `nama_barang` VARCHAR(180) NOT NULL,
  `kategori` VARCHAR(32) NOT NULL,
  `satuan` VARCHAR(32) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sumber_donor` VARCHAR(64) DEFAULT '-',
  `stok_awal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_masuk` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_keluar` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `penyesuaian_opname` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `saldo_stok` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `nearest_expired_date` DATE DEFAULT NULL,
  `status_stok` VARCHAR(32) DEFAULT 'TERSEDIA',
  `status_expired` VARCHAR(32) DEFAULT 'TANPA EXPIRED',
  `lokasi_rak` VARCHAR(48) DEFAULT 'GUDANG-PB',
  `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_balance`),
  UNIQUE KEY `uk_balance_barang` (`id_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. TABEL LOG_AKTIVITAS (Audit Trail Seluruh Aktivitas Pengguna)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
CREATE TABLE `log_aktivitas` (
  `id_log` VARCHAR(40) NOT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` VARCHAR(32) NOT NULL,
  `username` VARCHAR(64) NOT NULL,
  `nama_user` VARCHAR(128) NOT NULL,
  `role` VARCHAR(32) NOT NULL,
  `modul` VARCHAR(48) NOT NULL,
  `aksi` VARCHAR(48) NOT NULL,
  `referensi_id` VARCHAR(64) DEFAULT '-',
  `detail_aktivitas` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `log_aktivitas` (`id_log`, `timestamp`, `id_user`, `username`, `nama_user`, `role`, `modul`, `aksi`, `referensi_id`, `detail_aktivitas`) VALUES
('LOG-INIT-001', NOW(), 'USR-ADMIN-001', 'admin', 'Administrator Logistik PMI', 'ADMIN', 'DATABASE', 'INITIAL_SETUP', 'DB-MYSQL-V1', 'Inisialisasi awal database MySQL Gudang PB PMI Kota Surakarta beserta 19 item standar selesai.');

SET FOREIGN_KEY_CHECKS = 1;
