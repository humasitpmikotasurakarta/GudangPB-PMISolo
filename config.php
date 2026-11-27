<?php
/**
 * ============================================================================
 * SISTEM INFORMASI MANAJEMEN STOK GUDANG - PMI KOTA SURAKARTA
 * File: config.php (Folder: GudangPBPMISolo)
 * Deskripsi: Konfigurasi Koneksi Database MySQL / MariaDB (PDO)
 * ============================================================================
 */

declare(strict_types=1);

// Set Zona Waktu resmi WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// ----------------------------------------------------------------------------
// KONFIGURASI KONEKSI DATABASE MYSQL
// Sesuaikan dengan pengaturan XAMPP / Laragon / cPanel / Hosting Anda
// ----------------------------------------------------------------------------
define('DB_HOST', getenv('PMI_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('PMI_DB_PORT') ?: '3306');
define('DB_NAME', getenv('PMI_DB_NAME') ?: 'pmi_surakarta_warehouse');
define('DB_USER', getenv('PMI_DB_USER') ?: 'root');
define('DB_PASS', getenv('PMI_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Masa berlaku token sesi login (12 Jam = 43200 detik)
define('SESSION_DURATION_SECONDS', 43200);

// Threshold peringatan kedaluwarsa (Expired Date)
define('EXPIRY_SEGERA_DAYS', 30);
define('EXPIRY_PERHATIAN_DAYS', 90);

/**
 * Membuka koneksi PDO ke MySQL.
 * Jika database belum di-import, secara otomatis mengeksekusi database.sql.
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
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Jika database belum ada, buat database otomatis dan import database.sql
        $dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
        $pdoInit = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
        $pdoInit->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdoInit->exec('USE `' . str_replace('`', '', DB_NAME) . '`');

        $sqlFile = __DIR__ . '/database.sql';
        if (is_file($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            if ($sqlContent !== false) {
                $pdoInit->exec($sqlContent);
            }
        }
        $pdo = $pdoInit;
    }

    // Pastikan jika tabel users belum ada, jalankan database.sql otomatis
    try {
        $pdo->query('SELECT 1 FROM `users` LIMIT 1');
    } catch (PDOException $e) {
        $sqlFile = __DIR__ . '/database.sql';
        if (is_file($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            if ($sqlContent !== false) {
                $pdo->exec($sqlContent);
            }
        }
    }

    return $pdo;
}
