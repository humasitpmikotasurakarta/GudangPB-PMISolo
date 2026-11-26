<?php
/**
 * ============================================================================
 * SISTEM INFORMASI MANAJEMEN STOK GUDANG - PMI KOTA SURAKARTA
 * File: config.php
 * Deskripsi: Konfigurasi Koneksi Database MySQL (PDO), Auto-Installer Skema
 *            Otomatis, Matriks Hak Akses (RBAC), dan Fungsi Utilitas Server.
 * ============================================================================
 */

date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Koneksi MySQL (Sesuaikan dengan XAMPP / Laragon / Hosting Anda)
define('DB_HOST', getenv('PMI_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('PMI_DB_PORT') ?: '3306');
define('DB_NAME', getenv('PMI_DB_NAME') ?: 'pmi_surakarta_warehouse');
define('DB_USER', getenv('PMI_DB_USER') ?: 'root');
define('DB_PASS', getenv('PMI_DB_PASS') !== false ? getenv('PMI_DB_PASS') : '');

define('PASSWORD_SALT', 'PMI_KOTA_SURAKARTA_WAREHOUSE_SALT_2026');
define('SESSION_TTL_SECONDS', 21600); // 6 Jam

/**
 * Membuka koneksi PDO ke MySQL dan menjalankan auto-setup dari database.sql
 * jika database atau tabel belum dibuat.
 */
function getDbConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Jika database belum ada, buat database terlebih dahulu lalu jalankan database.sql
        $dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $pdoInit = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
        $pdoInit->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdoInit->exec('USE `' . DB_NAME . '`');
        $pdo = $pdoInit;
    }

    // Pastikan tabel utama sudah terinisialisasi
    $stmtCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmtCheck->rowCount() === 0) {
        $sqlFile = __DIR__ . '/database.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $pdo->exec($sqlContent);
            recalculateAllStockBalances($pdo);
        }
    }

    return $pdo;
}

/**
 * Menghasilkan ID unik berformat PREFIX-YYYYMMDDHHMMSS-RANDOM
 */
function generateUniqueId(string $prefix = 'ID'): string {
    $cleanPrefix = strtoupper(trim($prefix ?: 'ID'));
    $datePart = date('YmdHis');
    $randPart = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    return $cleanPrefix . '-' . $datePart . '-' . $randPart;
}

/**
 * Menghasilkan hash SHA-256 dengan salt aplikasi
 */
function hashPassword(string $plainPassword): string {
    return hash('sha256', $plainPassword . '::' . PASSWORD_SALT);
}

/**
 * Matriks Hak Akses (Role-Based Access Control)
 */
function getRolePermissions(string $role): array {
    $cleanRole = strtoupper(trim($role));
    $matrix = [
        'ADMIN' => [
            'canViewDashboard'      => true,
            'canViewStok'           => true,
            'canManageMasterBarang' => true,
            'canImportExcel'        => true,
            'canInputTransaksiMasuk'=> true,
            'canEditTransaksiMasuk' => true,
            'canInputTransaksiKeluar'=> true,
            'canEditTransaksiKeluar'=> true,
            'canViewKartuStok'      => true,
            'canInputStockOpname'   => true,
            'canApproveStockOpname' => true,
            'canViewExpired'        => true,
            'canViewLaporan'        => true,
            'canManageUsers'        => true,
            'canViewLogs'           => true,
            'canManageSettings'     => true,
        ],
        'PETUGAS GUDANG' => [
            'canViewDashboard'      => true,
            'canViewStok'           => true,
            'canManageMasterBarang' => false,
            'canImportExcel'        => false,
            'canInputTransaksiMasuk'=> true,
            'canEditTransaksiMasuk' => false,
            'canInputTransaksiKeluar'=> true,
            'canEditTransaksiKeluar'=> false,
            'canViewKartuStok'      => true,
            'canInputStockOpname'   => true,
            'canApproveStockOpname' => false,
            'canViewExpired'        => true,
            'canViewLaporan'        => true,
            'canManageUsers'        => false,
            'canViewLogs'           => false,
            'canManageSettings'     => false,
        ],
        'PIMPINAN' => [
            'canViewDashboard'      => true,
            'canViewStok'           => true,
            'canManageMasterBarang' => false,
            'canImportExcel'        => false,
            'canInputTransaksiMasuk'=> false,
            'canEditTransaksiMasuk' => false,
            'canInputTransaksiKeluar'=> false,
            'canEditTransaksiKeluar'=> false,
            'canViewKartuStok'      => true,
            'canInputStockOpname'   => false,
            'canApproveStockOpname' => false,
            'canViewExpired'        => true,
            'canViewLaporan'        => true,
            'canManageUsers'        => false,
            'canViewLogs'           => false,
            'canManageSettings'     => false,
        ],
    ];

    return $matrix[$cleanRole] ?? $matrix['PIMPINAN'];
}

/**
 * Menormalkan string tanggal ke format YYYY-MM-DD
 */
function normalizeDateString($val): string {
    if ($val === null || $val === '' || $val === '-') {
        return '';
    }
    $str = trim((string)$val);
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
    }
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }
    $ts = strtotime($str);
    return $ts !== false ? date('Y-m-d', $ts) : '';
}

/**
 * Evaluasi status kedaluwarsa berdasarkan tanggal hari ini
 */
function evaluateExpiryStatus(?string $expiredDateStr): array {
    $normalized = normalizeDateString($expiredDateStr);
    if (!$normalized) {
        return [
            'hasExpiry'     => false,
            'expiredDate'   => '',
            'daysRemaining' => null,
            'status'        => 'TANPA EXPIRED',
            'badgeClass'    => 'secondary',
        ];
    }

    $todayTs = strtotime(date('Y-m-d'));
    $expTs   = strtotime($normalized);
    $diffDays = (int)floor(($expTs - $todayTs) / 86400);

    if ($diffDays <= 0) {
        $status = 'KEDALUWARSA';
        $badge  = 'danger';
    } elseif ($diffDays <= 30) {
        $status = 'SEGERA KEDALUWARSA';
        $badge  = 'orange';
    } elseif ($diffDays <= 90) {
        $status = 'PERHATIAN';
        $badge  = 'warning';
    } else {
        $status = 'AMAN';
        $badge  = 'success';
    }

    return [
        'hasExpiry'     => true,
        'expiredDate'   => $normalized,
        'daysRemaining' => $diffDays,
        'status'        => $status,
        'badgeClass'    => $badge,
    ];
}

/**
 * Mencatat aktivitas pengguna ke tabel log_aktivitas
 */
function logUserActivity(PDO $pdo, array $user, string $modul, string $aksi, string $refId, string $detail): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO log_aktivitas (id_log, timestamp, id_user, username, nama_user, role, modul, aksi, referensi_id, detail_aktivitas)
             VALUES (:id_log, NOW(), :id_user, :username, :nama_user, :role, :modul, :aksi, :ref_id, :detail)'
        );
        $stmt->execute([
            ':id_log'    => generateUniqueId('LOG'),
            ':id_user'   => $user['id_user'] ?? 'SYSTEM',
            ':username'  => $user['username'] ?? 'system',
            ':nama_user' => $user['nama_lengkap'] ?? 'Sistem',
            ':role'      => $user['role'] ?? 'SYSTEM',
            ':modul'     => $modul,
            ':aksi'      => $aksi,
            ':ref_id'    => $refId ?: '-',
            ':detail'    => $detail,
        ]);
    } catch (Throwable $e) {
        // Jangan gagalkan transaksi utama bila gagal mencatat log
    }
}
