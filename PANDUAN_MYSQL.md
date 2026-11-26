# Panduan Instalasi Versi HTML5 + PHP + MySQL
## Sistem Informasi Manajemen Stok Gudang - PMI Kota Surakarta

Versi ini dirancang untuk berjalan di server lokal (**XAMPP**, **Laragon**, **WAMP**) maupun **Web Hosting / VPS / cPanel** menggunakan database **MySQL / MariaDB**.

---

## 1. Struktur File (`mysql-version/`)

- **[index.html](file:///c:/Users/Humas%20IT/.gemini/antigravity/scratch/pmi-surakarta-warehouse/mysql-version/index.html)** — Aplikasi Frontend HTML5 lengkap (Bootstrap 5.3, Chart.js 4, SheetJS, Custom PMI Popup Modal, Layout Header/Footer/Sidebar *Stay Freeze*, dan Instant Cache).
- **[api.php](file:///c:/Users/Humas%20IT/.gemini/antigravity/scratch/pmi-surakarta-warehouse/mysql-version/api.php)** — Backend REST API JSON berbasis PHP PDO MySQL dengan dukungan transaksi ACID, validasi stok server-side, dan RBAC.
- **[config.php](file:///c:/Users/Humas%20IT/.gemini/antigravity/scratch/pmi-surakarta-warehouse/mysql-version/config.php)** — Konfigurasi koneksi database MySQL disertai fitur **Auto-Installer** (otomatis membuat database & tabel dari `database.sql` saat pertama kali diakses).
- **[database.sql](file:///c:/Users/Humas%20IT/.gemini/antigravity/scratch/pmi-surakarta-warehouse/mysql-version/database.sql)** — Skema SQL 10 tabel utama + tabel sesi + data awal (*seeding*) Gudang PB PMI Kota Surakarta.

---

## 2. Cara Menjalankan di XAMPP / Laragon

1. **Salin Folder**:
   - Untuk **XAMPP**: Salin isi folder `mysql-version` ke dalam `C:\xampp\htdocs\gudang-pmi\`.
   - Untuk **Laragon**: Salin isi folder `mysql-version` ke dalam `C:\laragon\www\gudang-pmi\`.
2. **Hidupkan Apache & MySQL** melalui Control Panel XAMPP atau Laragon.
3. **Konfigurasi Koneksi Database** (Opsional):
   - Buka `config.php`. Secara default sudah diatur untuk XAMPP/Laragon:
     - `DB_HOST`: `127.0.0.1`
     - `DB_PORT`: `3306`
     - `DB_NAME`: `pmi_surakarta_warehouse`
     - `DB_USER`: `root`
     - `DB_PASS`: `""` (kosong)
4. **Import Database (Otomatis atau Manual)**:
   - **Otomatis**: Saat Anda membuka `http://localhost/gudang-pmi/` dan login pertama kali, `config.php` akan otomatis membuat database `pmi_surakarta_warehouse` beserta seluruh 10 tabel dan data awalnya.
   - **Manual via phpMyAdmin**: Buka `http://localhost/phpmyadmin`, lalu import file `database.sql`.
5. **Buka Aplikasi**:
   - Akses `http://localhost/gudang-pmi/` di browser.

---

## 3. Akun Default Login

| Role / Level Akses | Username | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **ADMIN** | `admin` | `admin123` | Akses penuh: Master Barang, Sumber, GRN, GDN, Approval Stock Opname, Import Excel, Pengguna & Pengaturan |
| **PETUGAS GUDANG** | `petugas` | `petugas123` | Operasional harian: Input GRN, Input GDN, Kartu Stok, Input Stock Opname, Monitoring Expired & Laporan |
| **PIMPINAN** | `pimpinan` | `pimpinan123` | Monitoring eksekutif: Dashboard Real-Time, Kartu Stok, Monitoring Expired & Cetak/Export 12 Laporan Resmi |
