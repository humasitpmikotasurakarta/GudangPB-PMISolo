-- ============================================================================
-- SISTEM INFORMASI MANAJEMEN STOK GUDANG - PMI KOTA SURAKARTA
-- File: database.sql
-- Engine: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.4+ (XAMPP / Laragon / cPanel)
-- Deskripsi: Skema 10 Tabel Utama + Tabel Sesi Autentikasi + Data Awal (Seed)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `pmi_surakarta_warehouse`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pmi_surakarta_warehouse`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. TABEL USERS (Manajemen Akun & Role: ADMIN, PETUGAS GUDANG, PIMPINAN)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` VARCHAR(40) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `password_hash` VARCHAR(128) NOT NULL,
  `nama_lengkap` VARCHAR(120) NOT NULL,
  `role` ENUM('ADMIN', 'PETUGAS GUDANG', 'PIMPINAN') NOT NULL DEFAULT 'PETUGAS GUDANG',
  `jabatan` VARCHAR(120) DEFAULT '-',
  `email` VARCHAR(120) DEFAULT '',
  `no_hp` VARCHAR(40) DEFAULT '',
  `status_aktif` ENUM('AKTIF', 'NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 1B. TABEL USER_SESSIONS (Token Sesi Login Server-Side)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `token` VARCHAR(100) NOT NULL,
  `id_user` VARCHAR(40) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  KEY `idx_sessions_user` (`id_user`),
  KEY `idx_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. TABEL MASTER_BARANG (3 Kategori: FOOD ITEMS, NON FOOD, OBAT)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `master_barang` (
  `id_barang` VARCHAR(40) NOT NULL,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(200) NOT NULL,
  `kategori` ENUM('FOOD ITEMS', 'NON FOOD', 'OBAT') NOT NULL DEFAULT 'NON FOOD',
  `subkategori` VARCHAR(120) DEFAULT '-',
  `satuan` VARCHAR(50) NOT NULL DEFAULT 'Unit',
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sumber_donor` VARCHAR(120) DEFAULT '-',
  `stok_minimum` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `lokasi_rak` VARCHAR(80) DEFAULT '-',
  `has_expiry` ENUM('YA', 'TIDAK') NOT NULL DEFAULT 'TIDAK',
  `keterangan` TEXT DEFAULT NULL,
  `status_aktif` ENUM('AKTIF', 'NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` VARCHAR(60) DEFAULT 'admin',
  PRIMARY KEY (`id_barang`),
  UNIQUE KEY `uk_master_kode_barang` (`kode_barang`),
  KEY `idx_master_kategori` (`kategori`),
  KEY `idx_master_status` (`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. TABEL TRANSAKSI_MASUK (Goods Received Note / GRN)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi_masuk` (
  `id_masuk` VARCHAR(40) NOT NULL,
  `no_transaksi` VARCHAR(50) NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_barang` VARCHAR(40) NOT NULL,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `jumlah_masuk` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(50) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `sumber_donor` VARCHAR(120) DEFAULT '-',
  `no_grn` VARCHAR(100) DEFAULT '-',
  `no_request` VARCHAR(100) DEFAULT '-',
  `expired_date` DATE DEFAULT NULL,
  `catatan` TEXT DEFAULT NULL,
  `dokumen_url` VARCHAR(255) DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(60) DEFAULT 'admin',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(60) DEFAULT 'admin',
  PRIMARY KEY (`id_masuk`),
  KEY `idx_masuk_no_trx` (`no_transaksi`),
  KEY `idx_masuk_tanggal` (`tanggal`),
  KEY `idx_masuk_barang` (`id_barang`),
  KEY `idx_masuk_expired` (`expired_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. TABEL TRANSAKSI_KELUAR (Goods Delivery Note / GDN)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi_keluar` (
  `id_keluar` VARCHAR(40) NOT NULL,
  `no_transaksi` VARCHAR(50) NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_barang` VARCHAR(40) NOT NULL,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `jumlah_keluar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(50) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `tujuan_distribusi` VARCHAR(200) NOT NULL,
  `nama_penerima` VARCHAR(150) NOT NULL,
  `no_gdn` VARCHAR(100) DEFAULT '-',
  `no_request` VARCHAR(100) DEFAULT '-',
  `penanggung_jawab` VARCHAR(120) NOT NULL,
  `catatan` TEXT DEFAULT NULL,
  `dokumen_url` VARCHAR(255) DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(60) DEFAULT 'petugas',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(60) DEFAULT 'petugas',
  PRIMARY KEY (`id_keluar`),
  KEY `idx_keluar_no_trx` (`no_transaksi`),
  KEY `idx_keluar_tanggal` (`tanggal`),
  KEY `idx_keluar_barang` (`id_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. TABEL STOCK_BALANCE (Rekapitulasi Saldo Stok & Valuasi Real-Time)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_balance` (
  `id_balance` VARCHAR(50) NOT NULL,
  `id_barang` VARCHAR(40) NOT NULL,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `satuan` VARCHAR(50) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sumber_donor` VARCHAR(120) DEFAULT '-',
  `stok_awal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_masuk` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_keluar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `penyesuaian_opname` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `saldo_stok` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `nearest_expired_date` DATE DEFAULT NULL,
  `status_stok` VARCHAR(30) NOT NULL DEFAULT 'TERSEDIA',
  `status_expired` VARCHAR(40) NOT NULL DEFAULT 'TANPA EXPIRED',
  `lokasi_rak` VARCHAR(80) DEFAULT '-',
  `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_balance`),
  UNIQUE KEY `uk_balance_barang` (`id_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. TABEL STOCK_OPNAME (Pemeriksaan Fisik & Approval Administrator)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_opname` (
  `id_opname` VARCHAR(40) NOT NULL,
  `no_opname` VARCHAR(50) NOT NULL,
  `tanggal_jadwal` DATE NOT NULL,
  `tanggal_periksa` DATE NOT NULL,
  `id_barang` VARCHAR(40) NOT NULL,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `satuan` VARCHAR(50) NOT NULL,
  `harga_unit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `stok_sistem` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `stok_fisik` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `selisih` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `nilai_selisih` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `alasan_selisih` VARCHAR(255) DEFAULT '-',
  `petugas_pemeriksa` VARCHAR(120) NOT NULL,
  `status_persetujuan` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
  `disetujui_oleh` VARCHAR(120) DEFAULT '-',
  `tanggal_persetujuan` DATETIME DEFAULT NULL,
  `catatan_admin` VARCHAR(255) DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(60) DEFAULT 'petugas',
  PRIMARY KEY (`id_opname`),
  KEY `idx_opname_barang` (`id_barang`),
  KEY `idx_opname_status` (`status_persetujuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. TABEL DISTRIBUSI (Pelacakan Penyaluran Bantuan Kemanusiaan)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `distribusi` (
  `id_distribusi` VARCHAR(40) NOT NULL,
  `no_distribusi` VARCHAR(50) NOT NULL,
  `id_keluar` VARCHAR(40) NOT NULL,
  `no_transaksi_keluar` VARCHAR(50) NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_barang` VARCHAR(40) NOT NULL,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `jumlah` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(50) NOT NULL,
  `total_nilai` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `tujuan_distribusi` VARCHAR(200) NOT NULL,
  `nama_penerima` VARCHAR(150) NOT NULL,
  `no_gdn` VARCHAR(100) DEFAULT '-',
  `no_request` VARCHAR(100) DEFAULT '-',
  `penanggung_jawab` VARCHAR(120) NOT NULL,
  `status_distribusi` VARCHAR(40) NOT NULL DEFAULT 'TERDISTRIBUSI',
  `catatan` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(60) DEFAULT 'petugas',
  PRIMARY KEY (`id_distribusi`),
  KEY `idx_dist_keluar` (`id_keluar`),
  KEY `idx_dist_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. TABEL DONOR_SUMBER (Master Sumber Barang)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donor_sumber` (
  `id_donor` VARCHAR(40) NOT NULL,
  `kode_donor` VARCHAR(50) NOT NULL,
  `nama_donor` VARCHAR(180) NOT NULL,
  `jenis_donor` VARCHAR(80) DEFAULT 'Mitra Sumber',
  `kontak` VARCHAR(100) DEFAULT '-',
  `alamat` VARCHAR(255) DEFAULT '-',
  `keterangan` VARCHAR(255) DEFAULT '',
  `status_aktif` ENUM('AKTIF', 'NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_donor`),
  UNIQUE KEY `uk_kode_sumber` (`kode_donor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. TABEL LOG_AKTIVITAS (Audit Trail Seluruh Operasi Pengguna)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `log_aktivitas` (
  `id_log` VARCHAR(40) NOT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` VARCHAR(40) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `nama_user` VARCHAR(120) NOT NULL,
  `role` VARCHAR(40) NOT NULL,
  `modul` VARCHAR(60) NOT NULL,
  `aksi` VARCHAR(60) NOT NULL,
  `referensi_id` VARCHAR(80) DEFAULT '-',
  `detail_aktivitas` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. TABEL SETTINGS (Identitas Instansi & Pejabat Penandatangan Laporan)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `deskripsi` VARCHAR(255) DEFAULT '',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(60) DEFAULT 'SYSTEM',
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATA AWAL (SEEDING) - PMI KOTA SURAKARTA
-- Password Hash menggunakan SHA2(CONCAT(password, '::PMI_KOTA_SURAKARTA_WAREHOUSE_SALT_2026'), 256)
-- ============================================================================

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `deskripsi`, `updated_by`) VALUES
('NAMA_INSTANSI', 'PALANG MERAH INDONESIA KOTA SURAKARTA', 'Nama Resmi Instansi pada Kop Laporan', 'SYSTEM'),
('ALAMAT_INSTANSI', 'Jl. Kolonel Sutarto No. 58, Jebres, Kota Surakarta, Jawa Tengah 57126', 'Alamat Markas & Gudang PMI Kota Surakarta', 'SYSTEM'),
('TELEPON_INSTANSI', '(0271) 646505 | Email: kota_surakarta@pmi.or.id', 'Kontak Resmi Markas PMI Kota Surakarta', 'SYSTEM'),
('NAMA_KEPALA_MARKAS', 'Wanto, S.KM.', 'Nama Pejabat Penanggung Jawab pada Kolom Tanda Tangan Laporan', 'SYSTEM'),
('NAMA_KOORDINATOR_GUDANG', 'Indro Sri Widodo', 'Nama Koordinator Gudang PB pada Kolom Tanda Tangan', 'SYSTEM');

INSERT IGNORE INTO `users` (`id_user`, `username`, `password_hash`, `nama_lengkap`, `role`, `jabatan`, `email`, `no_hp`, `status_aktif`) VALUES
('USR-ADMIN-001', 'admin', SHA2('admin123::PMI_KOTA_SURAKARTA_WAREHOUSE_SALT_2026', 256), 'Administrator Logistik PMI', 'ADMIN', 'Kasubbid Logistik & Gudang PB', 'logistik@pmisurakarta.or.id', '0271-646505', 'AKTIF'),
('USR-PETUGAS-001', 'petugas', SHA2('petugas123::PMI_KOTA_SURAKARTA_WAREHOUSE_SALT_2026', 256), 'Indro Sri Widodo', 'PETUGAS GUDANG', 'KaSie Kesiapsiagaan dan Respon / Petugas Gudang PB', 'gudang@pmisurakarta.or.id', '081234567890', 'AKTIF'),
('USR-PIMPINAN-001', 'pimpinan', SHA2('pimpinan123::PMI_KOTA_SURAKARTA_WAREHOUSE_SALT_2026', 256), 'Wanto, S.KM.', 'PIMPINAN', 'KaBag PB & Relawan / Pimpinan PMI Kota Surakarta', 'markas@pmisurakarta.or.id', '0271-646505', 'AKTIF');

INSERT IGNORE INTO `donor_sumber` (`id_donor`, `kode_donor`, `nama_donor`, `jenis_donor`, `kontak`, `alamat`, `keterangan`, `status_aktif`) VALUES
('DNR-001', 'PMI-PST', 'PMI Pusat (Markas Pusat)', 'Internal PMI', '021-7992325', 'Jl. Jend. Gatot Subroto Kav. 96 Jakarta', 'Bantuan Logistik Nasional PMI Pusat', 'AKTIF'),
('DNR-002', 'PMI-JTG', 'PMI Provinsi Jawa Tengah', 'Internal PMI', '024-3515050', 'Semarang, Jawa Tengah', 'Dropping Logistik Regional Jawa Tengah', 'AKTIF'),
('DNR-003', 'PMI-SKA', 'Pengadaan Mandiri PMI Kota Surakarta', 'Pengadaan Internal', '0271-646505', 'Jl. Kolonel Sutarto No. 58 Surakarta', 'Pembelian Anggaran Rutin & Tanggap Darurat', 'AKTIF'),
('DNR-004', 'IFRC', 'IFRC / ICRC (Federasi Palang Merah Internasional)', 'Mitra Internasional', '-', 'Jakarta / Geneva', 'Hibah Relief Item & Shelter Kit', 'AKTIF'),
('DNR-005', 'HVC', 'HVC / Mitra Kemanusiaan', 'Mitra Sumber', '-', 'Surakarta', 'Sumber Paket Pangan & Bantuan Darurat (Referensi Gudang PB)', 'AKTIF'),
('DNR-006', 'CSR-DONATUR', 'CSR & Masyarakat Kota Surakarta', 'Mitra Umum', '-', 'Kota Surakarta', 'Dukungan Bantuan Kemanusiaan & Bencana', 'AKTIF');

INSERT IGNORE INTO `master_barang` (`id_barang`, `kode_barang`, `nama_barang`, `kategori`, `subkategori`, `satuan`, `harga_unit`, `sumber_donor`, `stok_minimum`, `lokasi_rak`, `has_expiry`, `keterangan`, `status_aktif`, `created_by`) VALUES
('BRG-FD-0001', '06-000174', 'FOOD PACKAGE (Paket Pangan Darurat)', 'FOOD ITEMS', 'Paket Pangan Bencana', 'karung', 200000.00, 'HVC', 25, 'RAK-A1', 'YA', 'Paket bantuan pangan standar Gudang PB', 'AKTIF', 'admin'),
('BRG-FD-0002', 'FD-0002', 'Beras Premium Kemasan 5 Kg', 'FOOD ITEMS', 'Makanan Pokok', 'Sak', 72500.00, 'CSR-DONATUR', 25, 'RAK-A2', 'YA', 'Beras bantuan logistik bencana', 'AKTIF', 'admin'),
('BRG-FD-0003', 'FD-0003', 'Mie Instan Kuah (Karton 40 Bungkus)', 'FOOD ITEMS', 'Makanan Siap Saji', 'Dus', 112000.00, 'PMI-JTG', 20, 'RAK-A3', 'YA', 'Logistik dapur umum & paket sembako', 'AKTIF', 'admin'),
('BRG-FD-0004', 'FD-0004', 'Sarden Kaleng 425gr', 'FOOD ITEMS', 'Lauk Siap Saji', 'Kaleng', 24000.00, 'PMI-PST', 40, 'RAK-A4', 'YA', 'Lauk pauk paket bantuan darurat', 'AKTIF', 'admin'),
('BRG-FD-0005', 'FD-0005', 'Biskuit Energi Darurat / PMT', 'FOOD ITEMS', 'Makanan Tambahan', 'Pak', 15000.00, 'PMI-SKA', 20, 'RAK-A5', 'YA', 'Makanan tambahan balita & lansia pengungsian', 'AKTIF', 'admin'),
('BRG-NF-0001', 'NF-0001', 'Hygiene Kit', 'NON FOOD', 'Paket Bantuan', 'Paket', 210000.00, 'PMI-JTG', 20, 'RAK-B1', 'YA', 'Komponen: Sabun Mandi, Pasta Gigi, Shampo', 'AKTIF', 'admin'),
('BRG-NF-0002', 'NF-0002', 'Kitchen Set', 'NON FOOD', 'Paket Bantuan', 'Paket', 325000.00, 'PMI-PST', 15, 'RAK-B2', 'TIDAK', 'Perlengkapan memasak keluarga terdampak bencana', 'AKTIF', 'admin'),
('BRG-NF-0003', 'NF-0003', 'Tapaulin (Terpal Shelter PMI)', 'NON FOOD', 'Perlengkapan Shelter', 'Pcs', 245000.00, 'IFRC', 15, 'RAK-B3', 'TIDAK', 'Terpal pelindung hunian darurat standar PMI', 'AKTIF', 'admin'),
('BRG-NF-0004', 'NF-0004', 'Matras', 'NON FOOD', 'Perlengkapan Shelter', 'Pcs', 65000.00, 'PMI-JTG', 20, 'RAK-B4', 'TIDAK', 'Alas tidur pengungsian', 'AKTIF', 'admin'),
('BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 'Paket Bantuan', 'Paket', 385000.00, 'PMI-JTG', 10, 'RAK-B5', 'TIDAK', 'Paket bantuan keluarga terdampak banjir/kebakaran', 'AKTIF', 'admin'),
('BRG-NF-0006', 'NF-0006', 'Baby Kit', 'NON FOOD', 'Paket Bantuan', 'Paket', 240000.00, 'PMI-JTG', 10, 'RAK-B6', 'TIDAK', 'Paket perlengkapan bayi darurat', 'AKTIF', 'admin'),
('BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 'Sanitasi & Kebersihan', 'Pcs', 4500.00, 'PMI-SKA', 100, 'RAK-B7', 'YA', 'Sabun mandi bantuan sanitasi darurat', 'AKTIF', 'admin'),
('BRG-NF-0008', 'NF-0008', 'Sikat Gigi', 'NON FOOD', 'Sanitasi & Kebersihan', 'Pcs', 5500.00, 'PMI-SKA', 50, 'RAK-B7', 'TIDAK', 'Sikat gigi komponen kebersihan individu', 'AKTIF', 'admin'),
('BRG-NF-0009', 'NF-0009', 'Kacamata Google (APD)', 'NON FOOD', 'APD & Keselamatan', 'Pcs', 45000.00, 'PMI-SKA', 30, 'RAK-B8', 'TIDAK', 'Pelindung mata operasi erupsi Gunung Merapi / APG', 'AKTIF', 'admin'),
('BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 'Alat Kesehatan & APD Medis', 'Box', 35000.00, 'PMI-SKA', 25, 'RAK-C1', 'YA', 'Masker medis respon APG Merapi & pelayanan kesehatan', 'AKTIF', 'admin'),
('BRG-OB-0002', 'OB-0002', 'Paracetamol 500mg (Box 100 Kaplet)', 'OBAT', 'Analgesik & Antipiretik', 'Box', 38000.00, 'PMI-SKA', 15, 'RAK-C2', 'YA', 'Obat penurun demam pos kesehatan PMI', 'AKTIF', 'admin'),
('BRG-OB-0003', 'OB-0003', 'Povidone Iodine 10% 300ml (Betadine)', 'OBAT', 'Antiseptik & P3K', 'Botol', 62000.00, 'PMI-SKA', 10, 'RAK-C3', 'YA', 'Cairan antiseptik pertolongan pertama', 'AKTIF', 'admin'),
('BRG-OB-0004', 'OB-0004', 'Kasa Steril Hidrofil 16x16cm (Box 16 Lembar)', 'OBAT', 'Perbekalan Kesehatan P3K', 'Box', 14500.00, 'PMI-JTG', 30, 'RAK-C4', 'YA', 'Penutup luka steril pelayanan ambulans & posko', 'AKTIF', 'admin');

INSERT IGNORE INTO `transaksi_masuk` (`id_masuk`, `no_transaksi`, `tanggal`, `id_barang`, `kode_barang`, `nama_barang`, `kategori`, `jumlah_masuk`, `satuan`, `harga_unit`, `total_nilai`, `sumber_donor`, `no_grn`, `no_request`, `expired_date`, `catatan`, `created_by`, `updated_by`) VALUES
('IN-SEED-0001', 'BM-202611-0001', CURDATE(), 'BRG-FD-0001', '06-000174', 'FOOD PACKAGE (Paket Pangan Darurat)', 'FOOD ITEMS', 500, 'karung', 200000.00, 100000000.00, 'HVC', 'GRN/PMI-SKA/PB/001', '789', DATE_ADD(CURDATE(), INTERVAL 155 DAY), 'Penerimaan Food Package Gudang PB', 'admin', 'admin'),
('IN-SEED-0002', 'BM-202611-0002', CURDATE(), 'BRG-FD-0002', 'FD-0002', 'Beras Premium Kemasan 5 Kg', 'FOOD ITEMS', 120, 'Sak', 72500.00, 8700000.00, 'CSR-DONATUR', 'GRN/PMI-SKA/PB/002', 'REQ-PB-002', DATE_ADD(CURDATE(), INTERVAL 350 DAY), 'Penerimaan logistik beras bencana', 'admin', 'admin'),
('IN-SEED-0003', 'BM-202611-0003', CURDATE(), 'BRG-FD-0004', 'FD-0004', 'Sarden Kaleng 425gr', 'FOOD ITEMS', 28, 'Kaleng', 24000.00, 672000.00, 'PMI-PST', 'GRN/PMI-SKA/PB/003', 'REQ-PB-003', DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'Stok lauk siap saji (Mendekati Expired)', 'admin', 'admin'),
('IN-SEED-0004', 'BM-202611-0004', CURDATE(), 'BRG-FD-0005', 'FD-0005', 'Biskuit Energi Darurat / PMT', 'FOOD ITEMS', 12, 'Pak', 15000.00, 180000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/004', 'REQ-PB-004', DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Batch lama perlu pemisahan karantina expired', 'admin', 'admin'),
('IN-SEED-0005', 'BM-202611-0005', CURDATE(), 'BRG-NF-0001', 'NF-0001', 'Hygiene Kit', 'NON FOOD', 65, 'Paket', 210000.00, 13650000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/005', 'REQ-PB-005', DATE_ADD(CURDATE(), INTERVAL 59 DAY), 'Sabun Mandi, Pasta Gigi, Shampo', 'admin', 'admin'),
('IN-SEED-0006', 'BM-202611-0006', CURDATE(), 'BRG-NF-0002', 'NF-0002', 'Kitchen Set', 'NON FOOD', 50, 'Paket', 325000.00, 16250000.00, 'PMI-PST', 'GRN/PMI-SKA/PB/006', 'REQ-PB-006', NULL, 'Saldo Gudang PB', 'admin', 'admin'),
('IN-SEED-0007', 'BM-202611-0007', CURDATE(), 'BRG-NF-0003', 'NF-0003', 'Tapaulin (Terpal Shelter PMI)', 'NON FOOD', 10, 'Pcs', 245000.00, 2450000.00, 'IFRC', 'GRN/PMI-SKA/PB/007', 'REQ-PB-007', NULL, 'Stok Tapaulin Gudang PB (Batas Minimum)', 'admin', 'admin'),
('IN-SEED-0008', 'BM-202611-0008', CURDATE(), 'BRG-NF-0004', 'NF-0004', 'Matras', 'NON FOOD', 35, 'Pcs', 65000.00, 2275000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/008', 'REQ-PB-008', NULL, 'Dropping PMI Jateng & Stok Gudang PB', 'admin', 'admin'),
('IN-SEED-0009', 'BM-202611-0009', CURDATE(), 'BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 15, 'Paket', 385000.00, 5775000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/009', 'REQ-PB-009', NULL, 'Dropping PMI Jateng', 'admin', 'admin'),
('IN-SEED-0010', 'BM-202611-0010', CURDATE(), 'BRG-NF-0006', 'NF-0006', 'Baby Kit', 'NON FOOD', 15, 'Paket', 240000.00, 3600000.00, 'PMI-JTG', 'GRN/PMI-SKA/PB/010', 'REQ-PB-010', NULL, 'Dropping PMI Jateng', 'admin', 'admin'),
('IN-SEED-0011', 'BM-202611-0011', CURDATE(), 'BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 1800, 'Pcs', 4500.00, 8100000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/011', 'REQ-PB-011', DATE_ADD(CURDATE(), INTERVAL 155 DAY), 'Stok Awal Sabun Mandi Gudang PB', 'admin', 'admin'),
('IN-SEED-0012', 'BM-202611-0012', CURDATE(), 'BRG-NF-0008', 'NF-0008', 'Sikat Gigi', 'NON FOOD', 347, 'Pcs', 5500.00, 1908500.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/012', 'REQ-PB-012', NULL, 'Stok Awal Sikat Gigi Gudang PB', 'admin', 'admin'),
('IN-SEED-0013', 'BM-202611-0013', CURDATE(), 'BRG-NF-0009', 'NF-0009', 'Kacamata Google (APD)', 'NON FOOD', 169, 'Pcs', 45000.00, 7605000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/013', 'REQ-PB-013', NULL, 'Stok Awal Kacamata Google APD', 'admin', 'admin'),
('IN-SEED-0014', 'BM-202611-0014', CURDATE(), 'BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 78, 'Box', 35000.00, 2730000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/014', 'REQ-PB-014', DATE_ADD(CURDATE(), INTERVAL 350 DAY), 'Stok Awal Masker Gudang PB', 'admin', 'admin'),
('IN-SEED-0015', 'BM-202611-0015', CURDATE(), 'BRG-OB-0002', 'OB-0002', 'Paracetamol 500mg (Box 100 Kaplet)', 'OBAT', 50, 'Box', 38000.00, 1900000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/015', 'REQ-PB-015', DATE_ADD(CURDATE(), INTERVAL 350 DAY), 'Stok Obat Pelayanan Kesehatan PMI', 'admin', 'admin'),
('IN-SEED-0016', 'BM-202611-0016', CURDATE(), 'BRG-OB-0003', 'OB-0003', 'Povidone Iodine 10% 300ml (Betadine)', 'OBAT', 32, 'Botol', 62000.00, 1984000.00, 'PMI-SKA', 'GRN/PMI-SKA/PB/016', 'REQ-PB-016', DATE_ADD(CURDATE(), INTERVAL 59 DAY), 'Antiseptik Pertolongan Pertama', 'admin', 'admin');

INSERT IGNORE INTO `transaksi_keluar` (`id_keluar`, `no_transaksi`, `tanggal`, `id_barang`, `kode_barang`, `nama_barang`, `kategori`, `jumlah_keluar`, `satuan`, `harga_unit`, `total_nilai`, `tujuan_distribusi`, `nama_penerima`, `no_gdn`, `no_request`, `penanggung_jawab`, `catatan`, `created_by`, `updated_by`) VALUES
('OUT-SEED-0001', 'BK-202611-0001', CURDATE(), 'BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 72, 'Pcs', 4500.00, 324000.00, 'Kel. Nusukan (Kebakaran)', 'Warga Terdampak Kebakaran Kel. Nusukan', 'GDN/PMI-SKA/PB/001', 'REQ-PB-OUT-01', 'Indro Sri Widodo', 'Distribusi bantuan darurat kebakaran Kel. Nusukan', 'petugas', 'petugas'),
('OUT-SEED-0002', 'BK-202611-0002', CURDATE(), 'BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 150, 'Pcs', 4500.00, 675000.00, 'Kel. Joyosuran', 'Posko Bantuan Kel. Joyosuran', 'GDN/PMI-SKA/PB/002', 'REQ-PB-OUT-02', 'Indro Sri Widodo', 'Distribusi bantuan warga Kel. Joyosuran', 'petugas', 'petugas'),
('OUT-SEED-0003', 'BK-202611-0003', CURDATE(), 'BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 8, 'Paket', 385000.00, 3080000.00, 'Kel. Gandekan & Kel. Manahan', 'Karyawan & Warga Terdampak Banjir', 'GDN/PMI-SKA/PB/003', 'REQ-PB-OUT-03', 'Indro Sri Widodo', 'Distribusi Family Kit', 'petugas', 'petugas'),
('OUT-SEED-0004', 'BK-202611-0004', CURDATE(), 'BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 21, 'Box', 35000.00, 735000.00, 'APG Merapi', 'Tim Respon Erupsi APG Merapi', 'GDN/PMI-SKA/PB/004', 'REQ-PB-OUT-04', 'Indro Sri Widodo', 'Dukungan logistik kesehatan respon APG Merapi', 'petugas', 'petugas');

INSERT IGNORE INTO `distribusi` (`id_distribusi`, `no_distribusi`, `id_keluar`, `no_transaksi_keluar`, `tanggal`, `id_barang`, `kode_barang`, `nama_barang`, `kategori`, `jumlah`, `satuan`, `total_nilai`, `tujuan_distribusi`, `nama_penerima`, `no_gdn`, `no_request`, `penanggung_jawab`, `status_distribusi`, `catatan`, `created_by`) VALUES
('DST-SEED-0001', 'DST-202611-0001', 'OUT-SEED-0001', 'BK-202611-0001', CURDATE(), 'BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 72, 'Pcs', 324000.00, 'Kel. Nusukan (Kebakaran)', 'Warga Terdampak Kebakaran Kel. Nusukan', 'GDN/PMI-SKA/PB/001', 'REQ-PB-OUT-01', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi bantuan darurat kebakaran Kel. Nusukan', 'petugas'),
('DST-SEED-0002', 'DST-202611-0002', 'OUT-SEED-0002', 'BK-202611-0002', CURDATE(), 'BRG-NF-0007', 'NF-0007', 'Sabun Mandi', 'NON FOOD', 150, 'Pcs', 675000.00, 'Kel. Joyosuran', 'Posko Bantuan Kel. Joyosuran', 'GDN/PMI-SKA/PB/002', 'REQ-PB-OUT-02', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi bantuan warga Kel. Joyosuran', 'petugas'),
('DST-SEED-0003', 'DST-202611-0003', 'OUT-SEED-0003', 'BK-202611-0003', CURDATE(), 'BRG-NF-0005', 'NF-0005', 'Family Kit', 'NON FOOD', 8, 'Paket', 3080000.00, 'Kel. Gandekan & Kel. Manahan', 'Karyawan & Warga Terdampak Banjir', 'GDN/PMI-SKA/PB/003', 'REQ-PB-OUT-03', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Distribusi Family Kit', 'petugas'),
('DST-SEED-0004', 'DST-202611-0004', 'OUT-SEED-0004', 'BK-202611-0004', CURDATE(), 'BRG-OB-0001', 'OB-0001', 'Masker Medis Pelindung Pernapasan', 'OBAT', 21, 'Box', 735000.00, 'APG Merapi', 'Tim Respon Erupsi APG Merapi', 'GDN/PMI-SKA/PB/004', 'REQ-PB-OUT-04', 'Indro Sri Widodo', 'TERDISTRIBUSI', 'Dukungan logistik kesehatan respon APG Merapi', 'petugas');

SET FOREIGN_KEY_CHECKS = 1;
