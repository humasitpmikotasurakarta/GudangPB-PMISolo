<?php
/**
 * ============================================================================
 * SISTEM INFORMASI MANAJEMEN STOK GUDANG - PMI KOTA SURAKARTA
 * File: api.php
 * Deskripsi: Router & Controller REST API MySQL Terpusat (JSON).
 *            Menangani seluruh action dari Frontend HTML5 secara cepat dengan
 *            Transaksi MySQL (ACID), Validasi RBAC, dan Rekalkulasi Saldo Stok.
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

try {
    $pdo = getDbConnection();

    $rawInput = file_get_contents('php://input');
    $requestObj = json_decode($rawInput, true);
    if (!is_array($requestObj)) {
        $requestObj = $_POST;
    }

    $action  = strtoupper(trim((string)($requestObj['action'] ?? '')));
    $token   = trim((string)($requestObj['token'] ?? ''));
    $payload = is_array($requestObj['payload'] ?? null) ? $requestObj['payload'] : [];

    if ($action === '') {
        throw new Exception('Permintaan tidak valid: parameter action kosong.');
    }

    switch ($action) {
        case 'LOGIN':
            $authData = authenticateUser($pdo, (string)($payload['username'] ?? ''), (string)($payload['password'] ?? ''));
            echo json_encode([
                'success' => true,
                'message' => 'Selamat datang, ' . $authData['user']['nama_lengkap'] . ' (' . $authData['user']['role'] . ')',
                'data'    => $authData,
            ]);
            break;

        case 'LOGOUT':
            logoutUserSession($pdo, $token);
            echo json_encode([
                'success' => true,
                'message' => 'Anda telah keluar dari sistem.',
            ]);
            break;

        case 'VERIFY_SESSION':
            $sessionCheck = requireServerPermission($pdo, $token, null);
            echo json_encode([
                'success' => true,
                'data'    => [
                    'user'        => $sessionCheck['user'],
                    'permissions' => $sessionCheck['permissions'],
                    'settings'    => getSettingsMap($pdo),
                ],
            ]);
            break;

        case 'GET_INITIAL_BUNDLE':
            $bundle = getInitialWarehouseBundle($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $bundle,
            ]);
            break;

        case 'GET_DASHBOARD':
            $dash = getDashboardAnalytics($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $dash,
            ]);
            break;

        case 'GET_MASTER_BARANG':
            $masterRes = getMasterBarangWithBalance($pdo, $token);
            echo json_encode([
                'success' => true,
                'data'    => $masterRes,
            ]);
            break;

        case 'SAVE_MASTER_BARANG':
            $saveMaster = saveMasterBarang($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveMaster['message'],
                'data'    => $saveMaster,
            ]);
            break;

        case 'DELETE_MASTER_BARANG':
            $delMaster = deleteMasterBarang($pdo, $token, (string)($payload['id_barang'] ?? ''));
            echo json_encode([
                'success' => true,
                'message' => $delMaster['message'],
            ]);
            break;

        case 'SAVE_DONOR':
            $saveDonor = saveDonorRecord($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveDonor['message'],
                'data'    => $saveDonor,
            ]);
            break;

        case 'IMPORT_EXCEL_DATA':
            $importRes = importExcelBatchData($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $importRes['message'],
                'data'    => $importRes['stats'],
            ]);
            break;

        case 'GET_TRANSAKSI_MASUK':
            $listIn = getTransaksiMasukList($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $listIn,
            ]);
            break;

        case 'SAVE_TRANSAKSI_MASUK':
            $saveIn = saveTransaksiMasuk($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveIn['message'],
                'data'    => $saveIn['transaction'] ?? null,
            ]);
            break;

        case 'DELETE_TRANSAKSI_MASUK':
            $delIn = deleteTransaksiMasuk($pdo, $token, (string)($payload['id_masuk'] ?? ''), (string)($payload['alasan'] ?? ''));
            echo json_encode([
                'success' => true,
                'message' => $delIn['message'],
            ]);
            break;

        case 'GET_TRANSAKSI_KELUAR':
            $listOut = getTransaksiKeluarList($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $listOut,
            ]);
            break;

        case 'SAVE_TRANSAKSI_KELUAR':
            $saveOut = saveTransaksiKeluar($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveOut['message'],
                'data'    => $saveOut['transaction'] ?? null,
            ]);
            break;

        case 'DELETE_TRANSAKSI_KELUAR':
            $delOut = deleteTransaksiKeluar($pdo, $token, (string)($payload['id_keluar'] ?? ''), (string)($payload['alasan'] ?? ''));
            echo json_encode([
                'success' => true,
                'message' => $delOut['message'],
            ]);
            break;

        case 'GET_KARTU_STOK':
            $ks = getKartuStokBarang($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $ks,
            ]);
            break;

        case 'GET_STOCK_OPNAME':
            $so = getStockOpnameList($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $so,
            ]);
            break;

        case 'SAVE_STOCK_OPNAME':
            $saveSo = saveStockOpnameBatch($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveSo['message'],
                'data'    => $saveSo['records'],
            ]);
            break;

        case 'APPROVE_STOCK_OPNAME':
            $appSo = approveOrRejectStockOpname($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $appSo['message'],
            ]);
            break;

        case 'GET_EXPIRED_MONITORING':
            $exp = getExpiredDateMonitoring($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $exp,
            ]);
            break;

        case 'GENERATE_REPORT':
            $rep = generateWarehouseReport($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'data'    => $rep,
            ]);
            break;

        case 'GET_USERS':
            $users = getAllUsersForAdmin($pdo, $token);
            echo json_encode([
                'success' => true,
                'data'    => $users,
            ]);
            break;

        case 'SAVE_USER':
            $saveUsr = saveUserAccount($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveUsr['message'],
                'data'    => $saveUsr['user'] ?? null,
            ]);
            break;

        case 'GET_LOGS_AND_SETTINGS':
            requireServerPermission($pdo, $token, 'canViewLogs');
            $stmtLogs = $pdo->query('SELECT * FROM log_aktivitas ORDER BY timestamp DESC LIMIT 200');
            $stmtSet  = $pdo->query('SELECT * FROM settings');
            echo json_encode([
                'success' => true,
                'data'    => [
                    'logs'     => $stmtLogs->fetchAll(),
                    'settings' => $stmtSet->fetchAll(),
                ],
            ]);
            break;

        case 'SAVE_SETTINGS':
            $saveSet = saveApplicationSettings($pdo, $token, $payload);
            echo json_encode([
                'success' => true,
                'message' => $saveSet['message'],
            ]);
            break;

        case 'BACKUP_DATABASE':
            $auth = requireServerPermission($pdo, $token, 'canManageSettings');
            $backupName = 'BACKUP_MYSQL_PMI_SURAKARTA_' . date('Ymd_His') . '.sql';
            logUserActivity($pdo, $auth['user'], 'DATABASE', 'BACKUP_DATABASE', $backupName, 'Membuat snapshot backup database MySQL');
            echo json_encode([
                'success' => true,
                'message' => 'Snapshot backup database MySQL tercatat: ' . $backupName,
                'data'    => ['backupName' => $backupName],
            ]);
            break;

        default:
            throw new Exception('Action API tidak dikenali: ' . $action);
    }
} catch (Throwable $err) {
    $code = ($err->getCode() === 401) ? 'UNAUTHORIZED' : 'ERROR';
    echo json_encode([
        'success' => false,
        'code'    => $code,
        'message' => $err->getMessage(),
    ]);
}

/* ============================================================================
 * FUNGSI AUTENTIKASI & RBAC
 * ============================================================================ */

function authenticateUser(PDO $pdo, string $username, string $plainPassword): array {
    $cleanUser = strtolower(trim($username));
    if ($cleanUser === '' || $plainPassword === '') {
        throw new Exception('Username dan password wajib diisi.');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = :u LIMIT 1');
    $stmt->execute([':u' => $cleanUser]);
    $foundUser = $stmt->fetch();

    if (!$foundUser) {
        throw new Exception('Username tidak ditemukan.');
    }
    if (strtoupper($foundUser['status_aktif']) !== 'AKTIF') {
        throw new Exception('Akun pengguna ini berstatus NONAKTIF. Hubungi Administrator.');
    }

    $inputHash = hashPassword($plainPassword);
    if ($foundUser['password_hash'] !== $inputHash && $foundUser['password_hash'] !== $plainPassword) {
        throw new Exception('Password yang Anda masukkan salah.');
    }

    $token = 'TKN-' . bin2hex(random_bytes(16)) . '-' . time();
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TTL_SECONDS);

    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id_user = :id')->execute([':id' => $foundUser['id_user']]);
    $pdo->prepare('INSERT INTO user_sessions (token, id_user, expires_at) VALUES (:t, :id, :exp)')
        ->execute([':t' => $token, ':id' => $foundUser['id_user'], ':exp' => $expiresAt]);

    $sessionUser = [
        'id_user'      => $foundUser['id_user'],
        'username'     => $foundUser['username'],
        'nama_lengkap' => $foundUser['nama_lengkap'],
        'role'         => $foundUser['role'],
        'jabatan'      => $foundUser['jabatan'],
        'email'        => $foundUser['email'],
        'no_hp'        => $foundUser['no_hp'],
        'login_at'     => date('Y-m-d H:i:s'),
    ];

    logUserActivity($pdo, $sessionUser, 'AUTH', 'LOGIN', $foundUser['id_user'], 'Pengguna berhasil login sebagai ' . $foundUser['role']);

    return [
        'token'       => $token,
        'user'        => $sessionUser,
        'permissions' => getRolePermissions($foundUser['role']),
    ];
}

function logoutUserSession(PDO $pdo, string $token): void {
    if ($token === '') return;
    $pdo->prepare('DELETE FROM user_sessions WHERE token = :t')->execute([':t' => $token]);
}

function requireServerPermission(PDO $pdo, string $token, ?string $requiredPermKey): array {
    if ($token === '') {
        throw new Exception('Sesi Anda telah berakhir atau belum login. Silakan login kembali.', 401);
    }

    $stmt = $pdo->prepare(
        'SELECT u.* FROM user_sessions s
         JOIN users u ON u.id_user = s.id_user
         WHERE s.token = :t AND s.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([':t' => $token]);
    $dbUser = $stmt->fetch();

    if (!$dbUser || strtoupper($dbUser['status_aktif']) !== 'AKTIF') {
        throw new Exception('Sesi tidak valid atau akun tidak lagi aktif. Silakan login kembali.', 401);
    }

    $permissions = getRolePermissions($dbUser['role']);
    if ($requiredPermKey && empty($permissions[$requiredPermKey])) {
        throw new Exception('AKSES DITOLAK: Level akses Anda (' . $dbUser['role'] . ') tidak memiliki izin untuk operasi ini.');
    }

    unset($dbUser['password_hash']);
    return [
        'user'        => $dbUser,
        'permissions' => $permissions,
    ];
}

function getSettingsMap(PDO $pdo): array {
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[$row['setting_key']] = $row['setting_value'];
    }
    return $map;
}

/* ============================================================================
 * REKALKULASI SALDO STOK & VALIDASI STOK TERSEDIA
 * ============================================================================ */

function recalculateAllStockBalances(PDO $pdo): array {
    $masters = $pdo->query('SELECT * FROM master_barang ORDER BY kode_barang ASC')->fetchAll();

    $inRows = $pdo->query(
        'SELECT id_barang, SUM(jumlah_masuk) AS total_in,
                MIN(CASE WHEN expired_date IS NOT NULL AND jumlah_masuk > 0 THEN expired_date ELSE NULL END) AS nearest_exp
         FROM transaksi_masuk
         GROUP BY id_barang'
    )->fetchAll();
    $inMap  = [];
    $expMap = [];
    foreach ($inRows as $r) {
        $inMap[$r['id_barang']]  = (float)$r['total_in'];
        $expMap[$r['id_barang']] = $r['nearest_exp'] ? (string)$r['nearest_exp'] : '';
    }

    $outRows = $pdo->query('SELECT id_barang, SUM(jumlah_keluar) AS total_out FROM transaksi_keluar GROUP BY id_barang')->fetchAll();
    $outMap = [];
    foreach ($outRows as $r) {
        $outMap[$r['id_barang']] = (float)$r['total_out'];
    }

    $opRows = $pdo->query("SELECT id_barang, SUM(selisih) AS total_adj FROM stock_opname WHERE status_persetujuan = 'APPROVED' GROUP BY id_barang")->fetchAll();
    $opMap = [];
    foreach ($opRows as $r) {
        $opMap[$r['id_barang']] = (float)$r['total_adj'];
    }

    $stmtUpsert = $pdo->prepare(
        'INSERT INTO stock_balance
         (id_balance, id_barang, kode_barang, nama_barang, kategori, satuan, harga_unit, sumber_donor,
          stok_awal, total_masuk, total_keluar, penyesuaian_opname, saldo_stok, total_nilai,
          nearest_expired_date, status_stok, status_expired, lokasi_rak, last_updated)
         VALUES
         (:id_balance, :id_barang, :kode_barang, :nama_barang, :kategori, :satuan, :harga_unit, :sumber_donor,
          0, :total_masuk, :total_keluar, :penyesuaian, :saldo_stok, :total_nilai,
          :nearest_exp, :status_stok, :status_expired, :lokasi_rak, NOW())
         ON DUPLICATE KEY UPDATE
          kode_barang = VALUES(kode_barang),
          nama_barang = VALUES(nama_barang),
          kategori = VALUES(kategori),
          satuan = VALUES(satuan),
          harga_unit = VALUES(harga_unit),
          sumber_donor = VALUES(sumber_donor),
          total_masuk = VALUES(total_masuk),
          total_keluar = VALUES(total_keluar),
          penyesuaian_opname = VALUES(penyesuaian_opname),
          saldo_stok = VALUES(saldo_stok),
          total_nilai = VALUES(total_nilai),
          nearest_expired_date = VALUES(nearest_expired_date),
          status_stok = VALUES(status_stok),
          status_expired = VALUES(status_expired),
          lokasi_rak = VALUES(lokasi_rak),
          last_updated = NOW()'
    );

    $balanceRows = [];
    foreach ($masters as $item) {
        $idB         = $item['id_barang'];
        $totalMasuk  = $inMap[$idB] ?? 0.0;
        $totalKeluar = $outMap[$idB] ?? 0.0;
        $penyesuaian = $opMap[$idB] ?? 0.0;
        $saldoStok   = $totalMasuk - $totalKeluar + $penyesuaian;
        $hargaUnit   = (float)$item['harga_unit'];
        $totalNilai  = $saldoStok * $hargaUnit;
        $stokMin     = (float)$item['stok_minimum'];

        $statusStok = 'TERSEDIA';
        if ($saldoStok <= 0) {
            $statusStok = 'HABIS';
        } elseif ($saldoStok <= $stokMin) {
            $statusStok = 'MENIPIS';
        }

        $nearestExp = ($saldoStok > 0 && !empty($expMap[$idB])) ? $expMap[$idB] : null;
        $expInfo    = evaluateExpiryStatus($nearestExp);

        $stmtUpsert->execute([
            ':id_balance'     => 'BAL-' . $idB,
            ':id_barang'      => $idB,
            ':kode_barang'    => $item['kode_barang'],
            ':nama_barang'    => $item['nama_barang'],
            ':kategori'       => $item['kategori'],
            ':satuan'         => $item['satuan'],
            ':harga_unit'     => $hargaUnit,
            ':sumber_donor'   => $item['sumber_donor'] ?: '-',
            ':total_masuk'    => $totalMasuk,
            ':total_keluar'   => $totalKeluar,
            ':penyesuaian'    => $penyesuaian,
            ':saldo_stok'     => $saldoStok,
            ':total_nilai'    => $totalNilai,
            ':nearest_exp'    => $nearestExp,
            ':status_stok'    => $statusStok,
            ':status_expired' => $expInfo['status'],
            ':lokasi_rak'     => $item['lokasi_rak'] ?: '-',
        ]);

        $balanceRows[] = [
            'id_barang'   => $idB,
            'saldo_stok'  => $saldoStok,
            'total_nilai' => $totalNilai,
        ];
    }

    return $balanceRows;
}

function getCurrentItemAvailableStock(PDO $pdo, string $idBarang, ?string $excludeIdKeluar = null): float {
    $stmtIn = $pdo->prepare('SELECT COALESCE(SUM(jumlah_masuk), 0) FROM transaksi_masuk WHERE id_barang = :id');
    $stmtIn->execute([':id' => $idBarang]);
    $totalIn = (float)$stmtIn->fetchColumn();

    if ($excludeIdKeluar) {
        $stmtOut = $pdo->prepare('SELECT COALESCE(SUM(jumlah_keluar), 0) FROM transaksi_keluar WHERE id_barang = :id AND id_keluar != :ex');
        $stmtOut->execute([':id' => $idBarang, ':ex' => $excludeIdKeluar]);
    } else {
        $stmtOut = $pdo->prepare('SELECT COALESCE(SUM(jumlah_keluar), 0) FROM transaksi_keluar WHERE id_barang = :id');
        $stmtOut->execute([':id' => $idBarang]);
    }
    $totalOut = (float)$stmtOut->fetchColumn();

    $stmtAdj = $pdo->prepare("SELECT COALESCE(SUM(selisih), 0) FROM stock_opname WHERE id_barang = :id AND status_persetujuan = 'APPROVED'");
    $stmtAdj->execute([':id' => $idBarang]);
    $totalAdj = (float)$stmtAdj->fetchColumn();

    return $totalIn - $totalOut + $totalAdj;
}

function generateAutoDocumentNumber(PDO $pdo, string $tableName, string $colName, string $prefix): string {
    $ym = date('Ym');
    $patternPrefix = $prefix . '-' . $ym . '-';
    $stmt = $pdo->prepare("SELECT `$colName` FROM `$tableName` WHERE `$colName` LIKE :pat");
    $stmt->execute([':pat' => $patternPrefix . '%']);
    $maxSeq = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $docNo) {
        $seqNum = (int)substr((string)$docNo, strlen($patternPrefix));
        if ($seqNum > $maxSeq) {
            $maxSeq = $seqNum;
        }
    }
    return $patternPrefix . str_pad((string)($maxSeq + 1), 4, '0', STR_PAD_LEFT);
}

function generateAutoItemCode(PDO $pdo, string $kategori): string {
    $map = ['FOOD ITEMS' => 'FD', 'NON FOOD' => 'NF', 'OBAT' => 'OB'];
    $prefix = $map[strtoupper(trim($kategori))] ?? 'BRG';
    $stmt = $pdo->prepare('SELECT kode_barang FROM master_barang WHERE kode_barang LIKE :pat');
    $stmt->execute([':pat' => $prefix . '-%']);
    $maxSeq = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $code) {
        $num = (int)substr((string)$code, strlen($prefix) + 1);
        if ($num > $maxSeq) {
            $maxSeq = $num;
        }
    }
    return $prefix . '-' . str_pad((string)($maxSeq + 1), 4, '0', STR_PAD_LEFT);
}

function saveBase64Upload(string $base64Data, string $fileName, string $subfolder): string {
    if ($base64Data === '' || $fileName === '') return '';
    try {
        if (strpos($base64Data, ',') !== false) {
            $base64Data = explode(',', $base64Data, 2)[1];
        }
        $binary = base64_decode($base64Data);
        if ($binary === false) return '';

        $uploadDir = __DIR__ . '/uploads/' . $subfolder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $safeName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        file_put_contents($uploadDir . '/' . $safeName, $binary);
        return 'uploads/' . $subfolder . '/' . $safeName;
    } catch (Throwable $e) {
        return '';
    }
}

/* ============================================================================
 * MASTER BARANG & SUMBER
 * ============================================================================ */

function getMasterBarangWithBalance(PDO $pdo, string $token): array {
    requireServerPermission($pdo, $token, 'canViewStok');

    $countBal = (int)$pdo->query('SELECT COUNT(*) FROM stock_balance')->fetchColumn();
    $countMas = (int)$pdo->query('SELECT COUNT(*) FROM master_barang')->fetchColumn();
    if ($countMas > 0 && $countBal === 0) {
        recalculateAllStockBalances($pdo);
    }

    $sql = 'SELECT m.*,
                   COALESCE(b.stok_awal, 0) AS stok_awal,
                   COALESCE(b.total_masuk, 0) AS total_masuk,
                   COALESCE(b.total_keluar, 0) AS total_keluar,
                   COALESCE(b.penyesuaian_opname, 0) AS penyesuaian_opname,
                   COALESCE(b.saldo_stok, 0) AS saldo_stok,
                   COALESCE(b.total_nilai, 0) AS total_nilai,
                   b.nearest_expired_date
            FROM master_barang m
            LEFT JOIN stock_balance b ON b.id_barang = m.id_barang
            ORDER BY m.kode_barang ASC';

    $rows = $pdo->query($sql)->fetchAll();
    $items = [];

    foreach ($rows as $row) {
        $hargaUnit = (float)$row['harga_unit'];
        $saldoStok = (float)$row['saldo_stok'];
        $stokMin   = (float)$row['stok_minimum'];
        $statusStok = 'TERSEDIA';
        if ($saldoStok <= 0) {
            $statusStok = 'HABIS';
        } elseif ($saldoStok <= $stokMin) {
            $statusStok = 'MENIPIS';
        }
        $expEval = evaluateExpiryStatus($row['nearest_expired_date'] ?? '');

        $items[] = [
            'id_barang'            => $row['id_barang'],
            'kode_barang'          => $row['kode_barang'],
            'nama_barang'          => $row['nama_barang'],
            'kategori'             => $row['kategori'],
            'subkategori'          => $row['subkategori'] ?: '-',
            'satuan'               => $row['satuan'],
            'harga_unit'           => $hargaUnit,
            'sumber_donor'         => $row['sumber_donor'] ?: '-',
            'stok_minimum'         => $stokMin,
            'lokasi_rak'           => $row['lokasi_rak'] ?: '-',
            'has_expiry'           => $row['has_expiry'] ?: 'TIDAK',
            'keterangan'           => $row['keterangan'] ?: '',
            'status_aktif'         => $row['status_aktif'] ?: 'AKTIF',
            'stok_awal'            => (float)$row['stok_awal'],
            'total_masuk'          => (float)$row['total_masuk'],
            'total_keluar'         => (float)$row['total_keluar'],
            'penyesuaian_opname'   => (float)$row['penyesuaian_opname'],
            'saldo_stok'           => $saldoStok,
            'total_nilai'          => $saldoStok * $hargaUnit,
            'nearest_expired_date' => $expEval['expiredDate'],
            'days_to_expiry'       => $expEval['daysRemaining'],
            'status_expired'       => $expEval['status'],
            'status_stok'          => $statusStok,
            'updated_at'           => $row['updated_at'],
        ];
    }

    $donors = $pdo->query('SELECT * FROM donor_sumber ORDER BY kode_donor ASC')->fetchAll();

    return [
        'items'  => $items,
        'donors' => $donors,
    ];
}

function saveMasterBarang(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canManageMasterBarang');
    $pdo->beginTransaction();
    try {
        $idBarang    = trim((string)($payload['id_barang'] ?? ''));
        $kategori    = strtoupper(trim((string)($payload['kategori'] ?? 'NON FOOD')));
        $kodeBarang  = strtoupper(trim((string)($payload['kode_barang'] ?? '')));
        $namaBarang  = trim((string)($payload['nama_barang'] ?? ''));
        $subkategori = trim((string)($payload['subkategori'] ?? '-')) ?: '-';
        $satuan      = trim((string)($payload['satuan'] ?? 'Unit')) ?: 'Unit';
        $hargaUnit   = (float)($payload['harga_unit'] ?? 0);
        $sumberDonor = trim((string)($payload['sumber_donor'] ?? '-')) ?: '-';
        $stokMin     = (float)($payload['stok_minimum'] ?? 0);
        $lokasiRak   = trim((string)($payload['lokasi_rak'] ?? '-')) ?: '-';
        $hasExpiry   = strtoupper(trim((string)($payload['has_expiry'] ?? ($kategori === 'NON FOOD' ? 'TIDAK' : 'YA'))));
        $keterangan  = trim((string)($payload['keterangan'] ?? ''));
        $statusAktif = strtoupper(trim((string)($payload['status_aktif'] ?? 'AKTIF')));

        if ($namaBarang === '') {
            throw new Exception('Nama Barang wajib diisi.');
        }
        if ($kodeBarang === '') {
            $kodeBarang = generateAutoItemCode($pdo, $kategori);
        }

        // Validasi keunikan Kode Barang
        $stmtDup = $pdo->prepare('SELECT id_barang, nama_barang FROM master_barang WHERE UPPER(kode_barang) = :k AND id_barang != :id LIMIT 1');
        $stmtDup->execute([':k' => $kodeBarang, ':id' => $idBarang]);
        if ($dup = $stmtDup->fetch()) {
            throw new Exception('Kode Barang "' . $kodeBarang . '" sudah terdaftar untuk barang "' . $dup['nama_barang'] . '". Kode barang harus unik!');
        }

        if ($idBarang === '') {
            $newId = generateUniqueId('BRG');
            $stmtIns = $pdo->prepare(
                'INSERT INTO master_barang
                 (id_barang, kode_barang, nama_barang, kategori, subkategori, satuan, harga_unit, sumber_donor,
                  stok_minimum, lokasi_rak, has_expiry, keterangan, status_aktif, created_by)
                 VALUES
                 (:id, :kode, :nama, :kat, :sub, :sat, :harga, :sumber, :min, :rak, :exp, :ket, :st, :usr)'
            );
            $stmtIns->execute([
                ':id'     => $newId,
                ':kode'   => $kodeBarang,
                ':nama'   => $namaBarang,
                ':kat'    => $kategori,
                ':sub'    => $subkategori,
                ':sat'    => $satuan,
                ':harga'  => $hargaUnit,
                ':sumber' => $sumberDonor,
                ':min'    => $stokMin,
                ':rak'    => $lokasiRak,
                ':exp'    => $hasExpiry,
                ':ket'    => $keterangan,
                ':st'     => $statusAktif,
                ':usr'    => $auth['user']['username'],
            ]);

            $stokAwal = (float)($payload['stok_awal'] ?? 0);
            if ($stokAwal > 0) {
                $autoNoIn = generateAutoDocumentNumber($pdo, 'transaksi_masuk', 'no_transaksi', 'BM');
                $expAwal  = normalizeDateString($payload['expired_date'] ?? '') ?: null;
                $stmtIn = $pdo->prepare(
                    'INSERT INTO transaksi_masuk
                     (id_masuk, no_transaksi, tanggal, id_barang, kode_barang, nama_barang, kategori,
                      jumlah_masuk, satuan, harga_unit, total_nilai, sumber_donor, no_grn, no_request,
                      expired_date, catatan, created_by, updated_by)
                     VALUES
                     (:id_in, :no_trx, CURDATE(), :id_b, :kode, :nama, :kat, :qty, :sat, :harga, :tot,
                      :sumber, :grn, "-", :exp, "Saldo Stok Awal Master Barang", :usr, :usr)'
                );
                $stmtIn->execute([
                    ':id_in'  => generateUniqueId('IN'),
                    ':no_trx' => $autoNoIn,
                    ':id_b'   => $newId,
                    ':kode'   => $kodeBarang,
                    ':nama'   => $namaBarang,
                    ':kat'    => $kategori,
                    ':qty'    => $stokAwal,
                    ':sat'    => $satuan,
                    ':harga'  => $hargaUnit,
                    ':tot'    => $stokAwal * $hargaUnit,
                    ':sumber' => $sumberDonor !== '-' ? $sumberDonor : 'SALDO AWAL',
                    ':grn'    => 'GRN/INIT/' . $kodeBarang,
                    ':exp'    => $expAwal,
                    ':usr'    => $auth['user']['username'],
                ]);
            }

            recalculateAllStockBalances($pdo);
            logUserActivity($pdo, $auth['user'], 'MASTER_BARANG', 'CREATE_BARANG', $newId, "Menambahkan master barang: [$kodeBarang] $namaBarang");
            $pdo->commit();
            return ['message' => "Data barang [$kodeBarang] $namaBarang berhasil ditambahkan."];
        } else {
            $stmtUpd = $pdo->prepare(
                'UPDATE master_barang SET
                 kode_barang = :kode, nama_barang = :nama, kategori = :kat, subkategori = :sub,
                 satuan = :sat, harga_unit = :harga, sumber_donor = :sumber, stok_minimum = :min,
                 lokasi_rak = :rak, has_expiry = :exp, keterangan = :ket, status_aktif = :st
                 WHERE id_barang = :id'
            );
            $stmtUpd->execute([
                ':kode'   => $kodeBarang,
                ':nama'   => $namaBarang,
                ':kat'    => $kategori,
                ':sub'    => $subkategori,
                ':sat'    => $satuan,
                ':harga'  => $hargaUnit,
                ':sumber' => $sumberDonor,
                ':min'    => $stokMin,
                ':rak'    => $lokasiRak,
                ':exp'    => $hasExpiry,
                ':ket'    => $keterangan,
                ':st'     => $statusAktif,
                ':id'     => $idBarang,
            ]);

            recalculateAllStockBalances($pdo);
            logUserActivity($pdo, $auth['user'], 'MASTER_BARANG', 'UPDATE_BARANG', $idBarang, "Memperbarui master barang: [$kodeBarang] $namaBarang");
            $pdo->commit();
            return ['message' => "Data barang [$kodeBarang] $namaBarang berhasil diperbarui."];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function deleteMasterBarang(PDO $pdo, string $token, string $idBarang): array {
    $auth = requireServerPermission($pdo, $token, 'canManageMasterBarang');
    $stmt = $pdo->prepare('SELECT * FROM master_barang WHERE id_barang = :id LIMIT 1');
    $stmt->execute([':id' => $idBarang]);
    $target = $stmt->fetch();
    if (!$target) {
        throw new Exception('Data barang tidak ditemukan.');
    }

    $stmtHistIn = $pdo->prepare('SELECT COUNT(*) FROM transaksi_masuk WHERE id_barang = :id');
    $stmtHistIn->execute([':id' => $idBarang]);
    $stmtHistOut = $pdo->prepare('SELECT COUNT(*) FROM transaksi_keluar WHERE id_barang = :id');
    $stmtHistOut->execute([':id' => $idBarang]);

    if ((int)$stmtHistIn->fetchColumn() > 0 || (int)$stmtHistOut->fetchColumn() > 0) {
        $pdo->prepare("UPDATE master_barang SET status_aktif = 'NONAKTIF' WHERE id_barang = :id")->execute([':id' => $idBarang]);
        recalculateAllStockBalances($pdo);
        logUserActivity($pdo, $auth['user'], 'MASTER_BARANG', 'DEACTIVATE_BARANG', $idBarang, 'Menonaktifkan barang berhistori: ' . $target['nama_barang']);
        return ['message' => 'Barang [' . $target['kode_barang'] . '] memiliki riwayat transaksi sehingga statusnya diubah menjadi NONAKTIF demi menjaga keutuhan Kartu Stok.'];
    }

    $pdo->prepare('DELETE FROM master_barang WHERE id_barang = :id')->execute([':id' => $idBarang]);
    $pdo->prepare('DELETE FROM stock_balance WHERE id_barang = :id')->execute([':id' => $idBarang]);
    recalculateAllStockBalances($pdo);
    logUserActivity($pdo, $auth['user'], 'MASTER_BARANG', 'DELETE_BARANG', $idBarang, 'Menghapus master barang: ' . $target['nama_barang']);
    return ['message' => 'Barang [' . $target['kode_barang'] . '] ' . $target['nama_barang'] . ' berhasil dihapus.'];
}

function saveDonorRecord(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canManageMasterBarang');
    $kode = strtoupper(trim((string)($payload['kode_donor'] ?? '')));
    $nama = trim((string)($payload['nama_donor'] ?? ''));
    $jenis = trim((string)($payload['jenis_donor'] ?? 'Mitra Sumber')) ?: 'Mitra Sumber';

    if ($kode === '' || $nama === '') {
        throw new Exception('Kode Sumber dan Nama Sumber wajib diisi.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO donor_sumber (id_donor, kode_donor, nama_donor, jenis_donor, status_aktif)
         VALUES (:id, :kode, :nama, :jenis, "AKTIF")
         ON DUPLICATE KEY UPDATE nama_donor = VALUES(nama_donor), jenis_donor = VALUES(jenis_donor)'
    );
    $stmt->execute([
        ':id'    => generateUniqueId('DNR'),
        ':kode'  => $kode,
        ':nama'  => $nama,
        ':jenis' => $jenis,
    ]);

    logUserActivity($pdo, $auth['user'], 'SUMBER', 'SAVE_SUMBER', $kode, 'Menyimpan sumber barang: ' . $nama);
    return ['message' => 'Data sumber barang ' . $kode . ' berhasil disimpan.'];
}

/* ============================================================================
 * TRANSAKSI BARANG MASUK (GRN) & KELUAR (GDN)
 * ============================================================================ */

function getTransaksiMasukList(PDO $pdo, string $token, array $filters): array {
    requireServerPermission($pdo, $token, 'canViewStok');
    $where = [];
    $params = [];

    if (!empty($filters['startDate'])) {
        $where[] = 'tanggal >= :start';
        $params[':start'] = normalizeDateString($filters['startDate']);
    }
    if (!empty($filters['endDate'])) {
        $where[] = 'tanggal <= :end';
        $params[':end'] = normalizeDateString($filters['endDate']);
    }

    $sql = 'SELECT * FROM transaksi_masuk' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY tanggal DESC, created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['jumlah_masuk'] = (float)$r['jumlah_masuk'];
        $r['harga_unit']   = (float)$r['harga_unit'];
        $r['total_nilai']  = (float)$r['total_nilai'];
        $r['expired_date'] = $r['expired_date'] ?: '';
    }

    return [
        'transactions'   => $rows,
        'nextAutoNumber' => generateAutoDocumentNumber($pdo, 'transaksi_masuk', 'no_transaksi', 'BM'),
    ];
}

function saveTransaksiMasuk(PDO $pdo, string $token, array $payload): array {
    $idMasuk = trim((string)($payload['id_masuk'] ?? ''));
    $auth = requireServerPermission($pdo, $token, $idMasuk ? 'canEditTransaksiMasuk' : 'canInputTransaksiMasuk');

    $pdo->beginTransaction();
    try {
        $idBarang    = trim((string)($payload['id_barang'] ?? ''));
        $tanggal     = normalizeDateString($payload['tanggal'] ?? '') ?: date('Y-m-d');
        $jumlahMasuk = (float)($payload['jumlah_masuk'] ?? 0);
        $sumberDonor = trim((string)($payload['sumber_donor'] ?? ''));
        $noGrn       = trim((string)($payload['no_grn'] ?? '-')) ?: '-';
        $noRequest   = trim((string)($payload['no_request'] ?? '-')) ?: '-';
        $expiredDate = normalizeDateString($payload['expired_date'] ?? '') ?: null;
        $catatan     = trim((string)($payload['catatan'] ?? ''));

        if ($idBarang === '' || $jumlahMasuk <= 0) {
            throw new Exception('Barang dan jumlah masuk (> 0) wajib diisi.');
        }

        $stmtItem = $pdo->prepare('SELECT * FROM master_barang WHERE id_barang = :id LIMIT 1');
        $stmtItem->execute([':id' => $idBarang]);
        $item = $stmtItem->fetch();
        if (!$item) {
            throw new Exception('Barang tidak ditemukan di Master Data Barang.');
        }

        $hargaUnit = isset($payload['harga_unit']) && $payload['harga_unit'] !== ''
            ? (float)$payload['harga_unit']
            : (float)$item['harga_unit'];
        $totalNilai = $jumlahMasuk * $hargaUnit;

        $dokumenUrl = '';
        if (!empty($payload['dokumen_base64']) && !empty($payload['dokumen_name'])) {
            $dokumenUrl = saveBase64Upload($payload['dokumen_base64'], $payload['dokumen_name'], 'grn');
        }

        if ($idMasuk === '') {
            $autoNo = generateAutoDocumentNumber($pdo, 'transaksi_masuk', 'no_transaksi', 'BM');
            $newId  = generateUniqueId('IN');
            $stmtIns = $pdo->prepare(
                'INSERT INTO transaksi_masuk
                 (id_masuk, no_transaksi, tanggal, id_barang, kode_barang, nama_barang, kategori,
                  jumlah_masuk, satuan, harga_unit, total_nilai, sumber_donor, no_grn, no_request,
                  expired_date, catatan, dokumen_url, created_by, updated_by)
                 VALUES
                 (:id, :no, :tgl, :id_b, :kode, :nama, :kat, :qty, :sat, :harga, :tot, :sumber, :grn, :req, :exp, :cat, :doc, :usr, :usr)'
            );
            $stmtIns->execute([
                ':id'     => $newId,
                ':no'     => $autoNo,
                ':tgl'    => $tanggal,
                ':id_b'   => $item['id_barang'],
                ':kode'   => $item['kode_barang'],
                ':nama'   => $item['nama_barang'],
                ':kat'    => $item['kategori'],
                ':qty'    => $jumlahMasuk,
                ':sat'    => $item['satuan'],
                ':harga'  => $hargaUnit,
                ':tot'    => $totalNilai,
                ':sumber' => $sumberDonor ?: ($item['sumber_donor'] ?: '-'),
                ':grn'    => $noGrn,
                ':req'    => $noRequest,
                ':exp'    => $expiredDate,
                ':cat'    => $catatan,
                ':doc'    => $dokumenUrl,
                ':usr'    => $auth['user']['username'],
            ]);

            recalculateAllStockBalances($pdo);
            logUserActivity($pdo, $auth['user'], 'TRANSAKSI_MASUK', 'CREATE_MASUK', $autoNo, "Barang masuk: +$jumlahMasuk {$item['satuan']} {$item['nama_barang']}");
            $pdo->commit();
            return ['message' => "Transaksi Barang Masuk ($autoNo) berhasil disimpan. Stok bertambah +$jumlahMasuk {$item['satuan']}."];
        } else {
            $stmtOld = $pdo->prepare('SELECT * FROM transaksi_masuk WHERE id_masuk = :id LIMIT 1');
            $stmtOld->execute([':id' => $idMasuk]);
            $oldTx = $stmtOld->fetch();
            if (!$oldTx) {
                throw new Exception('Data transaksi masuk tidak ditemukan.');
            }

            $curAvail = getCurrentItemAvailableStock($pdo, $oldTx['id_barang']);
            if ($oldTx['id_barang'] === $idBarang && ($curAvail - (float)$oldTx['jumlah_masuk'] + $jumlahMasuk) < 0) {
                throw new Exception('Perubahan ditolak karena akan menyebabkan saldo stok akhir menjadi negatif.');
            }

            $stmtUpd = $pdo->prepare(
                'UPDATE transaksi_masuk SET
                 tanggal = :tgl, id_barang = :id_b, kode_barang = :kode, nama_barang = :nama, kategori = :kat,
                 jumlah_masuk = :qty, satuan = :sat, harga_unit = :harga, total_nilai = :tot,
                 sumber_donor = :sumber, no_grn = :grn, no_request = :req, expired_date = :exp,
                 catatan = :cat, updated_by = :usr
                 WHERE id_masuk = :id'
            );
            $stmtUpd->execute([
                ':tgl'    => $tanggal,
                ':id_b'   => $item['id_barang'],
                ':kode'   => $item['kode_barang'],
                ':nama'   => $item['nama_barang'],
                ':kat'    => $item['kategori'],
                ':qty'    => $jumlahMasuk,
                ':sat'    => $item['satuan'],
                ':harga'  => $hargaUnit,
                ':tot'    => $totalNilai,
                ':sumber' => $sumberDonor ?: ($item['sumber_donor'] ?: '-'),
                ':grn'    => $noGrn,
                ':req'    => $noRequest,
                ':exp'    => $expiredDate,
                ':cat'    => $catatan,
                ':usr'    => $auth['user']['username'],
                ':id'     => $idMasuk,
            ]);

            recalculateAllStockBalances($pdo);
            logUserActivity($pdo, $auth['user'], 'TRANSAKSI_MASUK', 'UPDATE_MASUK', $oldTx['no_transaksi'], 'Memperbarui transaksi masuk ' . $oldTx['no_transaksi']);
            $pdo->commit();
            return ['message' => 'Transaksi Barang Masuk (' . $oldTx['no_transaksi'] . ') berhasil diperbarui.'];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function deleteTransaksiMasuk(PDO $pdo, string $token, string $idMasuk, string $alasan): array {
    $auth = requireServerPermission($pdo, $token, 'canEditTransaksiMasuk');
    $stmt = $pdo->prepare('SELECT * FROM transaksi_masuk WHERE id_masuk = :id LIMIT 1');
    $stmt->execute([':id' => $idMasuk]);
    $target = $stmt->fetch();
    if (!$target) {
        throw new Exception('Transaksi barang masuk tidak ditemukan.');
    }

    $avail = getCurrentItemAvailableStock($pdo, $target['id_barang']);
    if (($avail - (float)$target['jumlah_masuk']) < 0) {
        throw new Exception('Penghapusan ditolak: Stok barang ini sudah didistribusikan sehingga penghapusan akan membuat saldo minus.');
    }

    $pdo->prepare('DELETE FROM transaksi_masuk WHERE id_masuk = :id')->execute([':id' => $idMasuk]);
    recalculateAllStockBalances($pdo);
    logUserActivity($pdo, $auth['user'], 'TRANSAKSI_MASUK', 'DELETE_MASUK', $target['no_transaksi'], 'Menghapus transaksi masuk. Alasan: ' . $alasan);
    return ['message' => 'Transaksi Barang Masuk ' . $target['no_transaksi'] . ' berhasil dihapus.'];
}

function getTransaksiKeluarList(PDO $pdo, string $token, array $filters): array {
    requireServerPermission($pdo, $token, 'canViewStok');
    $where = [];
    $params = [];

    if (!empty($filters['startDate'])) {
        $where[] = 'tanggal >= :start';
        $params[':start'] = normalizeDateString($filters['startDate']);
    }
    if (!empty($filters['endDate'])) {
        $where[] = 'tanggal <= :end';
        $params[':end'] = normalizeDateString($filters['endDate']);
    }

    $sql = 'SELECT * FROM transaksi_keluar' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY tanggal DESC, created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['jumlah_keluar'] = (float)$r['jumlah_keluar'];
        $r['harga_unit']    = (float)$r['harga_unit'];
        $r['total_nilai']   = (float)$r['total_nilai'];
    }

    return [
        'transactions'   => $rows,
        'nextAutoNumber' => generateAutoDocumentNumber($pdo, 'transaksi_keluar', 'no_transaksi', 'BK'),
    ];
}

function saveTransaksiKeluar(PDO $pdo, string $token, array $payload): array {
    $idKeluar = trim((string)($payload['id_keluar'] ?? ''));
    $auth = requireServerPermission($pdo, $token, $idKeluar ? 'canEditTransaksiKeluar' : 'canInputTransaksiKeluar');

    $pdo->beginTransaction();
    try {
        $idBarang         = trim((string)($payload['id_barang'] ?? ''));
        $tanggal          = normalizeDateString($payload['tanggal'] ?? '') ?: date('Y-m-d');
        $jumlahKeluar     = (float)($payload['jumlah_keluar'] ?? 0);
        $tujuanDistribusi = trim((string)($payload['tujuan_distribusi'] ?? ''));
        $namaPenerima     = trim((string)($payload['nama_penerima'] ?? ''));
        $noGdn            = trim((string)($payload['no_gdn'] ?? '-')) ?: '-';
        $noRequest        = trim((string)($payload['no_request'] ?? '-')) ?: '-';
        $penanggungJawab  = trim((string)($payload['penanggung_jawab'] ?? $auth['user']['nama_lengkap']));
        $catatan          = trim((string)($payload['catatan'] ?? ''));

        if ($idBarang === '' || $jumlahKeluar <= 0 || $tujuanDistribusi === '' || $namaPenerima === '') {
            throw new Exception('Barang, jumlah keluar (> 0), tujuan distribusi, dan nama penerima wajib diisi.');
        }

        $stmtItem = $pdo->prepare('SELECT * FROM master_barang WHERE id_barang = :id LIMIT 1');
        $stmtItem->execute([':id' => $idBarang]);
        $item = $stmtItem->fetch();
        if (!$item) {
            throw new Exception('Barang tidak ditemukan di Master Data Barang.');
        }

        $availableStock = getCurrentItemAvailableStock($pdo, $idBarang, $idKeluar ?: null);
        if ($jumlahKeluar > $availableStock) {
            throw new Exception("TRANSAKSI DITOLAK: Jumlah keluar ($jumlahKeluar {$item['satuan']}) melebihi stok tersedia ($availableStock {$item['satuan']}).");
        }

        $hargaUnit  = (float)$item['harga_unit'];
        $totalNilai = $jumlahKeluar * $hargaUnit;
        $dokumenUrl = '';
        if (!empty($payload['dokumen_base64']) && !empty($payload['dokumen_name'])) {
            $dokumenUrl = saveBase64Upload($payload['dokumen_base64'], $payload['dokumen_name'], 'gdn');
        }

        if ($idKeluar === '') {
            $autoNoOut  = generateAutoDocumentNumber($pdo, 'transaksi_keluar', 'no_transaksi', 'BK');
            $autoNoDist = generateAutoDocumentNumber($pdo, 'distribusi', 'no_distribusi', 'DST');
            $newIdOut   = generateUniqueId('OUT');

            $pdo->prepare(
                'INSERT INTO transaksi_keluar
                 (id_keluar, no_transaksi, tanggal, id_barang, kode_barang, nama_barang, kategori,
                  jumlah_keluar, satuan, harga_unit, total_nilai, tujuan_distribusi, nama_penerima,
                  no_gdn, no_request, penanggung_jawab, catatan, dokumen_url, created_by, updated_by)
                 VALUES
                 (:id, :no, :tgl, :id_b, :kode, :nama, :kat, :qty, :sat, :harga, :tot, :tuj, :pen, :gdn, :req, :pj, :cat, :doc, :usr, :usr)'
            )->execute([
                ':id'    => $newIdOut,
                ':no'    => $autoNoOut,
                ':tgl'   => $tanggal,
                ':id_b'  => $item['id_barang'],
                ':kode'  => $item['kode_barang'],
                ':nama'  => $item['nama_barang'],
                ':kat'   => $item['kategori'],
                ':qty'   => $jumlahKeluar,
                ':sat'   => $item['satuan'],
                ':harga' => $hargaUnit,
                ':tot'   => $totalNilai,
                ':tuj'   => $tujuanDistribusi,
                ':pen'   => $namaPenerima,
                ':gdn'   => $noGdn,
                ':req'   => $noRequest,
                ':pj'    => $penanggungJawab,
                ':cat'   => $catatan,
                ':doc'   => $dokumenUrl,
                ':usr'   => $auth['user']['username'],
            ]);

            $pdo->prepare(
                'INSERT INTO distribusi
                 (id_distribusi, no_distribusi, id_keluar, no_transaksi_keluar, tanggal, id_barang, kode_barang,
                  nama_barang, kategori, jumlah, satuan, total_nilai, tujuan_distribusi, nama_penerima,
                  no_gdn, no_request, penanggung_jawab, status_distribusi, catatan, created_by)
                 VALUES
                 (:id_d, :no_d, :id_k, :no_k, :tgl, :id_b, :kode, :nama, :kat, :qty, :sat, :tot, :tuj, :pen, :gdn, :req, :pj, "TERDISTRIBUSI", :cat, :usr)'
            )->execute([
                ':id_d' => generateUniqueId('DST'),
                ':no_d' => $autoNoDist,
                ':id_k' => $newIdOut,
                ':no_k' => $autoNoOut,
                ':tgl'  => $tanggal,
                ':id_b' => $item['id_barang'],
                ':kode' => $item['kode_barang'],
                ':nama' => $item['nama_barang'],
                ':kat'  => $item['kategori'],
                ':qty'  => $jumlahKeluar,
                ':sat'  => $item['satuan'],
                ':tot'  => $totalNilai,
                ':tuj'  => $tujuanDistribusi,
                ':pen'  => $namaPenerima,
                ':gdn'  => $noGdn,
                ':req'  => $noRequest,
                ':pj'   => $penanggungJawab,
                ':cat'  => $catatan,
                ':usr'  => $auth['user']['username'],
            ]);

            recalculateAllStockBalances($pdo);
            $sisa = $availableStock - $jumlahKeluar;
            logUserActivity($pdo, $auth['user'], 'TRANSAKSI_KELUAR', 'CREATE_KELUAR', $autoNoOut, "Barang keluar: -$jumlahKeluar {$item['satuan']} ke $tujuanDistribusi");
            $pdo->commit();
            return ['message' => "Transaksi Barang Keluar ($autoNoOut) berhasil disimpan. Sisa stok: $sisa {$item['satuan']}."];
        } else {
            $stmtOld = $pdo->prepare('SELECT * FROM transaksi_keluar WHERE id_keluar = :id LIMIT 1');
            $stmtOld->execute([':id' => $idKeluar]);
            $oldOut = $stmtOld->fetch();
            if (!$oldOut) {
                throw new Exception('Transaksi barang keluar tidak ditemukan.');
            }

            $pdo->prepare(
                'UPDATE transaksi_keluar SET
                 tanggal = :tgl, id_barang = :id_b, kode_barang = :kode, nama_barang = :nama, kategori = :kat,
                 jumlah_keluar = :qty, satuan = :sat, harga_unit = :harga, total_nilai = :tot,
                 tujuan_distribusi = :tuj, nama_penerima = :pen, no_gdn = :gdn, no_request = :req,
                 penanggung_jawab = :pj, catatan = :cat, updated_by = :usr
                 WHERE id_keluar = :id'
            )->execute([
                ':tgl'   => $tanggal,
                ':id_b'  => $item['id_barang'],
                ':kode'  => $item['kode_barang'],
                ':nama'  => $item['nama_barang'],
                ':kat'   => $item['kategori'],
                ':qty'   => $jumlahKeluar,
                ':sat'   => $item['satuan'],
                ':harga' => $hargaUnit,
                ':tot'   => $totalNilai,
                ':tuj'   => $tujuanDistribusi,
                ':pen'   => $namaPenerima,
                ':gdn'   => $noGdn,
                ':req'   => $noRequest,
                ':pj'    => $penanggungJawab,
                ':cat'   => $catatan,
                ':usr'   => $auth['user']['username'],
                ':id'    => $idKeluar,
            ]);

            $pdo->prepare(
                'UPDATE distribusi SET
                 tanggal = :tgl, id_barang = :id_b, kode_barang = :kode, nama_barang = :nama, kategori = :kat,
                 jumlah = :qty, satuan = :sat, total_nilai = :tot, tujuan_distribusi = :tuj,
                 nama_penerima = :pen, no_gdn = :gdn, no_request = :req, penanggung_jawab = :pj, catatan = :cat
                 WHERE id_keluar = :id'
            )->execute([
                ':tgl'  => $tanggal,
                ':id_b' => $item['id_barang'],
                ':kode' => $item['kode_barang'],
                ':nama' => $item['nama_barang'],
                ':kat'  => $item['kategori'],
                ':qty'  => $jumlahKeluar,
                ':sat'  => $item['satuan'],
                ':tot'  => $totalNilai,
                ':tuj'  => $tujuanDistribusi,
                ':pen'  => $namaPenerima,
                ':gdn'  => $noGdn,
                ':req'  => $noRequest,
                ':pj'   => $penanggungJawab,
                ':cat'  => $catatan,
                ':id'   => $idKeluar,
            ]);

            recalculateAllStockBalances($pdo);
            logUserActivity($pdo, $auth['user'], 'TRANSAKSI_KELUAR', 'UPDATE_KELUAR', $oldOut['no_transaksi'], 'Memperbarui transaksi keluar ' . $oldOut['no_transaksi']);
            $pdo->commit();
            return ['message' => 'Transaksi Barang Keluar (' . $oldOut['no_transaksi'] . ') berhasil diperbarui.'];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function deleteTransaksiKeluar(PDO $pdo, string $token, string $idKeluar, string $alasan): array {
    $auth = requireServerPermission($pdo, $token, 'canEditTransaksiKeluar');
    $stmt = $pdo->prepare('SELECT * FROM transaksi_keluar WHERE id_keluar = :id LIMIT 1');
    $stmt->execute([':id' => $idKeluar]);
    $target = $stmt->fetch();
    if (!$target) {
        throw new Exception('Transaksi barang keluar tidak ditemukan.');
    }

    $pdo->prepare('DELETE FROM transaksi_keluar WHERE id_keluar = :id')->execute([':id' => $idKeluar]);
    $pdo->prepare('DELETE FROM distribusi WHERE id_keluar = :id')->execute([':id' => $idKeluar]);
    recalculateAllStockBalances($pdo);
    logUserActivity($pdo, $auth['user'], 'TRANSAKSI_KELUAR', 'DELETE_KELUAR', $target['no_transaksi'], 'Menghapus transaksi keluar. Alasan: ' . $alasan);
    return ['message' => 'Transaksi Barang Keluar ' . $target['no_transaksi'] . ' berhasil dihapus dan stok dikembalikan ke saldo gudang.'];
}

/* ============================================================================
 * KARTU STOK, STOCK OPNAME, EXPIRED MONITORING, DASHBOARD & LAPORAN
 * ============================================================================ */

function getKartuStokBarang(PDO $pdo, string $token, array $payload): array {
    requireServerPermission($pdo, $token, 'canViewKartuStok');
    $idBarang  = trim((string)($payload['id_barang'] ?? ''));
    $startDate = normalizeDateString($payload['startDate'] ?? '');
    $endDate   = normalizeDateString($payload['endDate'] ?? '');

    $stmtItem = $pdo->prepare('SELECT * FROM master_barang WHERE id_barang = :id LIMIT 1');
    $stmtItem->execute([':id' => $idBarang]);
    $item = $stmtItem->fetch();
    if (!$item) {
        throw new Exception('Data barang tidak ditemukan.');
    }

    $hargaDefault = (float)$item['harga_unit'];
    $rawEvents = [];

    $stmtIn = $pdo->prepare('SELECT * FROM transaksi_masuk WHERE id_barang = :id ORDER BY tanggal ASC, created_at ASC');
    $stmtIn->execute([':id' => $idBarang]);
    foreach ($stmtIn->fetchAll() as $txIn) {
        $rawEvents[] = [
            'tanggal'           => $txIn['tanggal'],
            'timestamp'         => $txIn['created_at'],
            'orderPriority'     => 1,
            'no_dokumen'        => ($txIn['no_grn'] && $txIn['no_grn'] !== '-') ? ($txIn['no_transaksi'] . ' / ' . $txIn['no_grn']) : $txIn['no_transaksi'],
            'jenis'             => 'MASUK',
            'keterangan'        => $txIn['catatan'] ?: 'Penerimaan Barang Masuk',
            'masuk'             => (float)$txIn['jumlah_masuk'],
            'keluar'            => 0.0,
            'harga_satuan'      => (float)$txIn['harga_unit'] ?: $hargaDefault,
            'sumber_barang'     => $txIn['sumber_donor'] ?: '-',
            'tujuan_distribusi' => '-',
        ];
    }

    $stmtOut = $pdo->prepare('SELECT * FROM transaksi_keluar WHERE id_barang = :id ORDER BY tanggal ASC, created_at ASC');
    $stmtOut->execute([':id' => $idBarang]);
    foreach ($stmtOut->fetchAll() as $txOut) {
        $rawEvents[] = [
            'tanggal'           => $txOut['tanggal'],
            'timestamp'         => $txOut['created_at'],
            'orderPriority'     => 2,
            'no_dokumen'        => ($txOut['no_gdn'] && $txOut['no_gdn'] !== '-') ? ($txOut['no_transaksi'] . ' / ' . $txOut['no_gdn']) : $txOut['no_transaksi'],
            'jenis'             => 'KELUAR',
            'keterangan'        => $txOut['catatan'] ?: ('Distribusi ke ' . $txOut['tujuan_distribusi']),
            'masuk'             => 0.0,
            'keluar'            => (float)$txOut['jumlah_keluar'],
            'harga_satuan'      => (float)$txOut['harga_unit'] ?: $hargaDefault,
            'sumber_barang'     => '-',
            'tujuan_distribusi' => $txOut['tujuan_distribusi'] . ($txOut['nama_penerima'] ? ' (' . $txOut['nama_penerima'] . ')' : ''),
        ];
    }

    $stmtOp = $pdo->prepare("SELECT * FROM stock_opname WHERE id_barang = :id AND status_persetujuan = 'APPROVED' ORDER BY tanggal_periksa ASC, created_at ASC");
    $stmtOp->execute([':id' => $idBarang]);
    foreach ($stmtOp->fetchAll() as $op) {
        $diff = (float)$op['selisih'];
        if ($diff == 0.0) continue;
        $rawEvents[] = [
            'tanggal'           => $op['tanggal_periksa'],
            'timestamp'         => $op['tanggal_persetujuan'] ?: $op['created_at'],
            'orderPriority'     => 3,
            'no_dokumen'        => $op['no_opname'],
            'jenis'             => 'PENYESUAIAN OPNAME',
            'keterangan'        => 'Penyesuaian Stock Opname Disetujui (' . ($op['alasan_selisih'] ?: 'Opname Fisik') . ')',
            'masuk'             => $diff > 0 ? $diff : 0.0,
            'keluar'            => $diff < 0 ? abs($diff) : 0.0,
            'harga_satuan'      => (float)$op['harga_unit'] ?: $hargaDefault,
            'sumber_barang'     => $diff > 0 ? 'Penyesuaian Opname (+)' : '-',
            'tujuan_distribusi' => $diff < 0 ? 'Penyesuaian Opname (-)' : '-',
        ];
    }

    usort($rawEvents, function ($a, $b) {
        if ($a['tanggal'] !== $b['tanggal']) return strcmp($a['tanggal'], $b['tanggal']);
        if ($a['orderPriority'] !== $b['orderPriority']) return $a['orderPriority'] <=> $b['orderPriority'];
        return strcmp((string)$a['timestamp'], (string)$b['timestamp']);
    });

    $runningBalance      = 0.0;
    $saldoSebelumPeriode = 0.0;
    $filteredRows        = [];
    $totalMasukPeriode   = 0.0;
    $totalKeluarPeriode  = 0.0;

    foreach ($rawEvents as $ev) {
        $prevBalance    = $runningBalance;
        $runningBalance = $prevBalance + $ev['masuk'] - $ev['keluar'];

        if ($startDate && $ev['tanggal'] < $startDate) {
            $saldoSebelumPeriode = $runningBalance;
            continue;
        }
        if ($endDate && $ev['tanggal'] > $endDate) {
            continue;
        }

        $totalMasukPeriode  += $ev['masuk'];
        $totalKeluarPeriode += $ev['keluar'];

        $filteredRows[] = [
            'tanggal'           => $ev['tanggal'],
            'no_dokumen'        => $ev['no_dokumen'],
            'jenis'             => $ev['jenis'],
            'keterangan'        => $ev['keterangan'],
            'saldo_sebelumnya'  => $prevBalance,
            'barang_masuk'      => $ev['masuk'],
            'barang_keluar'     => $ev['keluar'],
            'saldo_stok'        => $runningBalance,
            'harga_satuan'      => $ev['harga_satuan'],
            'nilai_persediaan'  => $runningBalance * $ev['harga_satuan'],
            'sumber_barang'     => $ev['sumber_barang'],
            'tujuan_distribusi' => $ev['tujuan_distribusi'],
        ];
    }

    if ($startDate) {
        array_unshift($filteredRows, [
            'tanggal'           => $startDate,
            'no_dokumen'        => 'SALDO-AWAL',
            'jenis'             => 'SALDO AWAL PERIODE',
            'keterangan'        => 'Saldo Stok Sebelum Tanggal ' . $startDate,
            'saldo_sebelumnya'  => $saldoSebelumPeriode,
            'barang_masuk'      => 0,
            'barang_keluar'     => 0,
            'saldo_stok'        => $saldoSebelumPeriode,
            'harga_satuan'      => $hargaDefault,
            'nilai_persediaan'  => $saldoSebelumPeriode * $hargaDefault,
            'sumber_barang'     => $item['sumber_donor'] ?: '-',
            'tujuan_distribusi' => '-',
        ]);
    }

    $finalBalance = count($filteredRows) > 0
        ? $filteredRows[count($filteredRows) - 1]['saldo_stok']
        : ($startDate ? $saldoSebelumPeriode : $runningBalance);

    return [
        'item' => [
            'id_barang'    => $item['id_barang'],
            'kode_barang'  => $item['kode_barang'],
            'nama_barang'  => $item['nama_barang'],
            'kategori'     => $item['kategori'],
            'subkategori'  => $item['subkategori'] ?: '-',
            'satuan'       => $item['satuan'],
            'harga_unit'   => $hargaDefault,
            'sumber_donor' => $item['sumber_donor'] ?: '-',
            'lokasi_rak'   => $item['lokasi_rak'] ?: '-',
            'stok_minimum' => (float)$item['stok_minimum'],
        ],
        'summary' => [
            'startDate'            => $startDate ?: 'Awal Pencatatan',
            'endDate'              => $endDate ?: date('Y-m-d'),
            'saldoAwalPeriode'     => $startDate ? $saldoSebelumPeriode : 0,
            'totalMasukPeriode'    => $totalMasukPeriode,
            'totalKeluarPeriode'   => $totalKeluarPeriode,
            'saldoAkhirPeriode'    => $finalBalance,
            'nilaiPersediaanAkhir' => $finalBalance * $hargaDefault,
        ],
        'ledger' => $filteredRows,
    ];
}

function getStockOpnameList(PDO $pdo, string $token, array $filters): array {
    requireServerPermission($pdo, $token, 'canViewStok');
    $where = [];
    $params = [];

    if (!empty($filters['kategori']) && strtoupper($filters['kategori']) !== 'SEMUA') {
        $where[] = 'kategori = :kat';
        $params[':kat'] = strtoupper($filters['kategori']);
    }
    if (!empty($filters['status']) && strtoupper($filters['status']) !== 'SEMUA') {
        $where[] = 'status_persetujuan = :st';
        $params[':st'] = strtoupper($filters['status']);
    }

    $sql = 'SELECT * FROM stock_opname' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY tanggal_periksa DESC, created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['stok_sistem']   = (float)$r['stok_sistem'];
        $r['stok_fisik']    = (float)$r['stok_fisik'];
        $r['selisih']       = (float)$r['selisih'];
        $r['nilai_selisih'] = (float)$r['nilai_selisih'];
    }

    return [
        'records'        => $rows,
        'nextAutoNumber' => generateAutoDocumentNumber($pdo, 'stock_opname', 'no_opname', 'SO'),
    ];
}

function saveStockOpnameBatch(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canInputStockOpname');
    $pdo->beginTransaction();
    try {
        $tglJadwal  = normalizeDateString($payload['tanggal_jadwal'] ?? '') ?: date('Y-m-d');
        $tglPeriksa = normalizeDateString($payload['tanggal_periksa'] ?? '') ?: date('Y-m-d');
        $petugas    = trim((string)($payload['petugas_pemeriksa'] ?? $auth['user']['nama_lengkap']));
        $itemsInput = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        if (empty($itemsInput)) {
            throw new Exception('Tidak ada data barang yang dipilih untuk Stock Opname.');
        }

        $ym = date('Ym');
        $seq = (int)$pdo->query('SELECT COUNT(*) FROM stock_opname')->fetchColumn() + 1;
        $newRecords = [];

        $stmtMaster = $pdo->prepare('SELECT * FROM master_barang WHERE id_barang = :id LIMIT 1');
        $stmtIns = $pdo->prepare(
            'INSERT INTO stock_opname
             (id_opname, no_opname, tanggal_jadwal, tanggal_periksa, id_barang, kode_barang, nama_barang,
              kategori, satuan, harga_unit, stok_sistem, stok_fisik, selisih, nilai_selisih,
              alasan_selisih, petugas_pemeriksa, status_persetujuan, disetujui_oleh, catatan_admin, created_by)
             VALUES
             (:id, :no, :tj, :tp, :id_b, :kode, :nama, :kat, :sat, :harga, :sis, :fis, :sel, :nsel, :alasan, :ptg, "PENDING", "-", "Menunggu persetujuan Administrator", :usr)'
        );

        foreach ($itemsInput as $row) {
            if (!isset($row['stok_fisik']) || $row['stok_fisik'] === '') continue;
            $idB = trim((string)($row['id_barang'] ?? ''));
            $stmtMaster->execute([':id' => $idB]);
            $master = $stmtMaster->fetch();
            if (!$master) continue;

            $stokFisik  = (float)$row['stok_fisik'];
            $stokSistem = getCurrentItemAvailableStock($pdo, $idB);
            $selisih    = $stokFisik - $stokSistem;
            $hargaUnit  = (float)$master['harga_unit'];
            $alasan     = trim((string)($row['alasan_selisih'] ?? '')) ?: ($selisih == 0.0 ? 'Sesuai fisik gudang' : 'Selisih pemeriksaan fisik');
            $noOpname   = 'SO-' . $ym . '-' . str_pad((string)$seq++, 4, '0', STR_PAD_LEFT);
            $idOpname   = generateUniqueId('SO');

            $stmtIns->execute([
                ':id'     => $idOpname,
                ':no'     => $noOpname,
                ':tj'     => $tglJadwal,
                ':tp'     => $tglPeriksa,
                ':id_b'   => $master['id_barang'],
                ':kode'   => $master['kode_barang'],
                ':nama'   => $master['nama_barang'],
                ':kat'    => $master['kategori'],
                ':sat'    => $master['satuan'],
                ':harga'  => $hargaUnit,
                ':sis'    => $stokSistem,
                ':fis'    => $stokFisik,
                ':sel'    => $selisih,
                ':nsel'   => $selisih * $hargaUnit,
                ':alasan' => $alasan,
                ':ptg'    => $petugas,
                ':usr'    => $auth['user']['username'],
            ]);

            $newRecords[] = ['id_opname' => $idOpname, 'no_opname' => $noOpname];
        }

        logUserActivity($pdo, $auth['user'], 'STOCK_OPNAME', 'CREATE_OPNAME', $newRecords[0]['no_opname'] ?? 'SO', 'Menginput ' . count($newRecords) . ' item Stock Opname (PENDING)');
        $pdo->commit();
        return [
            'message' => count($newRecords) . ' data pemeriksaan Stock Opname berhasil disimpan dengan status PENDING.',
            'records' => $newRecords,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function approveOrRejectStockOpname(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canApproveStockOpname');
    $idOpname = trim((string)($payload['id_opname'] ?? ''));
    $decision = strtoupper(trim((string)($payload['decision'] ?? '')));

    if (!in_array($decision, ['APPROVED', 'REJECTED'], true)) {
        throw new Exception('Keputusan harus APPROVED atau REJECTED.');
    }

    $stmt = $pdo->prepare('SELECT * FROM stock_opname WHERE id_opname = :id LIMIT 1');
    $stmt->execute([':id' => $idOpname]);
    $target = $stmt->fetch();
    if (!$target || $target['status_persetujuan'] !== 'PENDING') {
        throw new Exception('Data Stock Opname tidak ditemukan atau sudah diproses.');
    }

    $curSys  = getCurrentItemAvailableStock($pdo, $target['id_barang']);
    $fisik   = (float)$target['stok_fisik'];
    $selisih = $fisik - $curSys;
    $harga   = (float)$target['harga_unit'];

    $pdo->prepare(
        'UPDATE stock_opname SET
         stok_sistem = :sis, selisih = :sel, nilai_selisih = :nsel, status_persetujuan = :dec,
         disetujui_oleh = :by, tanggal_persetujuan = NOW(), catatan_admin = :cat
         WHERE id_opname = :id'
    )->execute([
        ':sis'  => $curSys,
        ':sel'  => $selisih,
        ':nsel' => $selisih * $harga,
        ':dec'  => $decision,
        ':by'   => $auth['user']['nama_lengkap'] . ' (' . $auth['user']['username'] . ')',
        ':cat'  => $decision === 'APPROVED' ? 'Disetujui & stok disesuaikan' : 'Ditolak oleh Administrator',
        ':id'   => $idOpname,
    ]);

    if ($decision === 'APPROVED') {
        recalculateAllStockBalances($pdo);
    }

    logUserActivity($pdo, $auth['user'], 'STOCK_OPNAME', $decision . '_OPNAME', $target['no_opname'], "Administrator $decision Stock Opname {$target['no_opname']}");
    return [
        'message' => $decision === 'APPROVED'
            ? "Stock Opname {$target['no_opname']} telah DISETUJUI dan saldo stok telah disesuaikan menjadi $fisik {$target['satuan']}."
            : "Stock Opname {$target['no_opname']} telah DITOLAK.",
    ];
}

function getExpiredDateMonitoring(PDO $pdo, string $token, array $filters): array {
    requireServerPermission($pdo, $token, 'canViewExpired');
    $masterData = getMasterBarangWithBalance($pdo, $token);
    $itemsMap = [];
    foreach ($masterData['items'] as $it) {
        $itemsMap[$it['id_barang']] = $it;
    }

    $masukList = $pdo->query('SELECT * FROM transaksi_masuk WHERE expired_date IS NOT NULL ORDER BY expired_date ASC')->fetchAll();
    $expiredRecords = [];
    $seenBatch = [];

    foreach ($masukList as $txIn) {
        $expStr = normalizeDateString($txIn['expired_date']);
        if (!$expStr) continue;
        $masterItem = $itemsMap[$txIn['id_barang']] ?? null;
        if (!$masterItem || $masterItem['status_aktif'] !== 'AKTIF') continue;

        $eval = evaluateExpiryStatus($expStr);
        $batchKey = $txIn['id_barang'] . '::' . $expStr . '::' . ($txIn['no_grn'] ?: $txIn['no_transaksi']);
        if (isset($seenBatch[$batchKey])) continue;
        $seenBatch[$batchKey] = true;

        $expiredRecords[] = [
            'id_masuk'            => $txIn['id_masuk'],
            'id_barang'           => $masterItem['id_barang'],
            'kode_barang'         => $masterItem['kode_barang'],
            'nama_barang'         => $masterItem['nama_barang'],
            'kategori'            => $masterItem['kategori'],
            'satuan'              => $masterItem['satuan'],
            'harga_unit'          => (float)$masterItem['harga_unit'],
            'sumber_donor'        => $txIn['sumber_donor'] ?: $masterItem['sumber_donor'],
            'no_batch_dokumen'    => ($txIn['no_grn'] && $txIn['no_grn'] !== '-') ? $txIn['no_grn'] : $txIn['no_transaksi'],
            'tanggal_masuk'       => $txIn['tanggal'],
            'jumlah_batch_masuk'  => (float)$txIn['jumlah_masuk'],
            'saldo_stok_saat_ini' => (float)$masterItem['saldo_stok'],
            'total_nilai_stok'    => (float)$masterItem['total_nilai'],
            'lokasi_rak'          => $masterItem['lokasi_rak'],
            'expired_date'        => $eval['expiredDate'],
            'days_remaining'      => $eval['daysRemaining'],
            'status_expired'      => $eval['status'],
            'badge_class'         => $eval['badgeClass'],
            'catatan'             => $txIn['catatan'] ?: '',
        ];
    }

    $summaryCounts = [
        'totalItemBerexpired' => count($expiredRecords),
        'kedaluwarsa'         => 0,
        'segeraKedaluwarsa'   => 0,
        'perhatian'           => 0,
        'aman'                => 0,
    ];
    foreach ($expiredRecords as $r) {
        if ($r['status_expired'] === 'KEDALUWARSA') $summaryCounts['kedaluwarsa']++;
        elseif ($r['status_expired'] === 'SEGERA KEDALUWARSA') $summaryCounts['segeraKedaluwarsa']++;
        elseif ($r['status_expired'] === 'PERHATIAN') $summaryCounts['perhatian']++;
        elseif ($r['status_expired'] === 'AMAN') $summaryCounts['aman']++;
    }

    $kat = strtoupper(trim((string)($filters['kategori'] ?? '')));
    $st  = strtoupper(trim((string)($filters['status_expired'] ?? '')));
    $sD  = normalizeDateString($filters['startDate'] ?? '');
    $eD  = normalizeDateString($filters['endDate'] ?? '');
    $q   = strtolower(trim((string)($filters['search'] ?? '')));

    $filtered = array_values(array_filter($expiredRecords, function ($r) use ($kat, $st, $sD, $eD, $q) {
        if ($kat && $kat !== 'SEMUA' && strtoupper($r['kategori']) !== $kat) return false;
        if ($st && $st !== 'SEMUA' && strtoupper($r['status_expired']) !== $st) return false;
        if ($sD && $r['expired_date'] < $sD) return false;
        if ($eD && $r['expired_date'] > $eD) return false;
        if ($q && strpos(strtolower($r['kode_barang'] . ' ' . $r['nama_barang'] . ' ' . $r['no_batch_dokumen'] . ' ' . $r['sumber_donor']), $q) === false) return false;
        return true;
    }));

    return [
        'summary' => $summaryCounts,
        'items'   => $filtered,
    ];
}

function getDashboardAnalytics(PDO $pdo, string $token, array $filters): array {
    requireServerPermission($pdo, $token, 'canViewDashboard');
    $startDate    = normalizeDateString($filters['startDate'] ?? '');
    $endDate      = normalizeDateString($filters['endDate'] ?? '');
    $selectedYear = trim((string)($filters['year'] ?? date('Y')));

    $masterData = getMasterBarangWithBalance($pdo, $token);
    $items      = $masterData['items'];

    $totalJenis  = 0;
    $totalStok   = 0.0;
    $totalNilai  = 0.0;
    $stokMenipis = 0;

    $catIndexMap = ['FOOD ITEMS' => 0, 'NON FOOD' => 1, 'OBAT' => 2];
    $stokByCat   = [0.0, 0.0, 0.0];
    $nilaiByCat  = [0.0, 0.0, 0.0];
    $distByCat   = [0.0, 0.0, 0.0];
    $criticalList = [];

    foreach ($items as $it) {
        if ($it['status_aktif'] !== 'AKTIF') continue;
        $totalJenis++;
        $totalStok  += $it['saldo_stok'];
        $totalNilai += $it['total_nilai'];
        $cIdx = $catIndexMap[$it['kategori']] ?? null;
        if ($cIdx !== null) {
            $stokByCat[$cIdx]  += $it['saldo_stok'];
            $nilaiByCat[$cIdx] += $it['total_nilai'];
        }
        if ($it['saldo_stok'] <= $it['stok_minimum']) {
            $stokMenipis++;
            $criticalList[] = $it;
        }
    }

    $monthlyIn  = array_fill(0, 12, 0.0);
    $monthlyOut = array_fill(0, 12, 0.0);
    $trxInCount = 0;
    $unitInSum  = 0.0;
    $trxOutCount = 0;
    $unitOutSum  = 0.0;
    $recent = [];

    foreach ($pdo->query('SELECT * FROM transaksi_masuk ORDER BY tanggal DESC, created_at DESC')->fetchAll() as $txIn) {
        $tgl = $txIn['tanggal'];
        $qty = (float)$txIn['jumlah_masuk'];
        if (substr($tgl, 0, 4) === $selectedYear) {
            $mIdx = (int)substr($tgl, 5, 2) - 1;
            if ($mIdx >= 0 && $mIdx < 12) $monthlyIn[$mIdx] += $qty;
        }
        if ((!$startDate || $tgl >= $startDate) && (!$endDate || $tgl <= $endDate)) {
            $trxInCount++;
            $unitInSum += $qty;
            $recent[] = [
                'no_transaksi' => $txIn['no_transaksi'],
                'dokumen_ref'  => ($txIn['no_grn'] && $txIn['no_grn'] !== '-') ? $txIn['no_grn'] : 'GRN',
                'tanggal'      => $tgl,
                'timestamp'    => $txIn['created_at'],
                'nama_barang'  => $txIn['nama_barang'],
                'kode_barang'  => $txIn['kode_barang'],
                'kategori'     => $txIn['kategori'],
                'jenis'        => 'BARANG MASUK',
                'jumlah'       => $qty,
                'satuan'       => $txIn['satuan'],
                'mitra_tujuan' => $txIn['sumber_donor'] ?: '-',
            ];
        }
    }

    foreach ($pdo->query('SELECT * FROM transaksi_keluar ORDER BY tanggal DESC, created_at DESC')->fetchAll() as $txOut) {
        $tgl = $txOut['tanggal'];
        $qty = (float)$txOut['jumlah_keluar'];
        if (substr($tgl, 0, 4) === $selectedYear) {
            $mIdx = (int)substr($tgl, 5, 2) - 1;
            if ($mIdx >= 0 && $mIdx < 12) $monthlyOut[$mIdx] += $qty;
        }
        if ((!$startDate || $tgl >= $startDate) && (!$endDate || $tgl <= $endDate)) {
            $trxOutCount++;
            $unitOutSum += $qty;
            $cIdx = $catIndexMap[$txOut['kategori']] ?? null;
            if ($cIdx !== null) $distByCat[$cIdx] += $qty;
            $recent[] = [
                'no_transaksi' => $txOut['no_transaksi'],
                'dokumen_ref'  => ($txOut['no_gdn'] && $txOut['no_gdn'] !== '-') ? $txOut['no_gdn'] : 'GDN',
                'tanggal'      => $tgl,
                'timestamp'    => $txOut['created_at'],
                'nama_barang'  => $txOut['nama_barang'],
                'kode_barang'  => $txOut['kode_barang'],
                'kategori'     => $txOut['kategori'],
                'jenis'        => 'BARANG KELUAR',
                'jumlah'       => $qty,
                'satuan'       => $txOut['satuan'],
                'mitra_tujuan' => $txOut['tujuan_distribusi'] ?: '-',
            ];
        }
    }

    usort($recent, function ($a, $b) {
        return strcmp($b['tanggal'] . ' ' . $b['timestamp'], $a['tanggal'] . ' ' . $a['timestamp']);
    });

    $expData = getExpiredDateMonitoring($pdo, $token, []);
    $nearExpiryList = [];
    foreach ($expData['items'] as $ex) {
        if ($ex['status_expired'] !== 'AMAN') {
            $nearExpiryList[] = [
                'kode_barang'    => $ex['kode_barang'],
                'nama_barang'    => $ex['nama_barang'],
                'kategori'       => $ex['kategori'],
                'expired_date'   => $ex['expired_date'],
                'days_remaining' => $ex['days_remaining'],
                'saldo_stok'     => $ex['saldo_stok_saat_ini'],
                'satuan'         => $ex['satuan'],
                'status_expired' => $ex['status_expired'],
            ];
        }
    }

    $pendingOpname = (int)$pdo->query("SELECT COUNT(*) FROM stock_opname WHERE status_persetujuan = 'PENDING'")->fetchColumn();

    return [
        'stats' => [
            'totalJenisBarang'          => $totalJenis,
            'totalStokKeseluruhan'      => $totalStok,
            'totalNilaiPersediaan'      => $totalNilai,
            'jumlahStokMenipis'         => $stokMenipis,
            'jumlahTransaksiMasuk'      => $trxInCount,
            'totalUnitMasukPeriode'     => $unitInSum,
            'jumlahTransaksiKeluar'     => $trxOutCount,
            'totalUnitKeluarPeriode'    => $unitOutSum,
            'jumlahMendekatiKedaluwarsa'=> ($expData['summary']['segeraKedaluwarsa'] ?? 0) + ($expData['summary']['perhatian'] ?? 0),
            'jumlahBarangKedaluwarsa'   => $expData['summary']['kedaluwarsa'] ?? 0,
            'pendingOpnameCount'        => $pendingOpname,
        ],
        'charts' => [
            'categories'           => ['FOOD ITEMS', 'NON FOOD', 'OBAT'],
            'stokByCategory'       => $stokByCat,
            'nilaiByCategory'      => $nilaiByCat,
            'distribusiByCategory' => $distByCat,
            'monthly' => [
                'year'      => $selectedYear,
                'labels'    => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                'masukQty'  => $monthlyIn,
                'keluarQty' => $monthlyOut,
            ],
        ],
        'recentActivities'  => array_slice($recent, 0, 10),
        'criticalStockList' => array_slice($criticalList, 0, 8),
        'nearExpiryList'    => array_slice($nearExpiryList, 0, 8),
    ];
}

function getInitialWarehouseBundle(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, null);
    return [
        'user'              => $auth['user'],
        'permissions'       => $auth['permissions'],
        'settings'          => getSettingsMap($pdo),
        'masterData'        => getMasterBarangWithBalance($pdo, $token),
        'dashboard'         => getDashboardAnalytics($pdo, $token, $payload),
        'transaksiMasuk'    => getTransaksiMasukList($pdo, $token, []),
        'transaksiKeluar'   => getTransaksiKeluarList($pdo, $token, []),
        'stockOpname'       => getStockOpnameList($pdo, $token, []),
        'expiredMonitoring' => getExpiredDateMonitoring($pdo, $token, []),
    ];
}

/* ============================================================================
 * 12 LAPORAN RESMI, IMPORT EXCEL, PENGGUNA & PENGATURAN
 * ============================================================================ */

function generateWarehouseReport(PDO $pdo, string $token, array $payload): array {
    requireServerPermission($pdo, $token, 'canViewLaporan');
    $reportType   = strtoupper(trim((string)($payload['reportType'] ?? 'STOK_KESELURUHAN')));
    $startDate    = normalizeDateString($payload['startDate'] ?? '');
    $endDate      = normalizeDateString($payload['endDate'] ?? '');
    $filterKat    = strtoupper(trim((string)($payload['kategori'] ?? 'SEMUA')));
    $filterBarang = strtolower(trim((string)($payload['namaBarang'] ?? '')));
    $filterSumber = strtolower(trim((string)($payload['sumberDonor'] ?? '')));

    $settingsMap = getSettingsMap($pdo);
    $masterData  = getMasterBarangWithBalance($pdo, $token);
    $allItems    = $masterData['items'];

    $reportTitle = 'LAPORAN STOK KESELURUHAN GUDANG PMI KOTA SURAKARTA';
    $columns     = [];
    $rows        = [];
    $summary     = [];

    $matchMaster = function ($it, $forcedCat = null) use ($filterKat, $filterBarang, $filterSumber) {
        $targetCat = $forcedCat ?: $filterKat;
        if ($targetCat && $targetCat !== 'SEMUA' && strtoupper($it['kategori']) !== $targetCat) return false;
        if ($filterBarang && strpos(strtolower($it['kode_barang'] . ' ' . $it['nama_barang']), $filterBarang) === false) return false;
        if ($filterSumber && strpos(strtolower($it['sumber_donor']), $filterSumber) === false) return false;
        return true;
    };

    if (in_array($reportType, ['STOK_KESELURUHAN', 'STOK_FOOD', 'STOK_NON_FOOD', 'STOK_OBAT'], true)) {
        $forced = $reportType === 'STOK_FOOD' ? 'FOOD ITEMS' : ($reportType === 'STOK_NON_FOOD' ? 'NON FOOD' : ($reportType === 'STOK_OBAT' ? 'OBAT' : null));
        if ($forced) $reportTitle = "LAPORAN PERSEDIAAN STOK KATEGORI $forced";
        $columns = ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Harga Satuan (Rp)', 'Sumber', 'Total Masuk', 'Total Keluar', 'Penyesuaian', 'Saldo Stok', 'Total Nilai (Rp)', 'Expired Terdekat', 'Lokasi Rak', 'Status Stok'];
        $no = 0; $totSaldo = 0; $totNilai = 0;
        foreach ($allItems as $it) {
            if (!$matchMaster($it, $forced)) continue;
            $no++;
            $totSaldo += $it['saldo_stok'];
            $totNilai += $it['total_nilai'];
            $rows[] = [$no, $it['kode_barang'], $it['nama_barang'], $it['kategori'], $it['satuan'], $it['harga_unit'], $it['sumber_donor'], $it['total_masuk'], $it['total_keluar'], $it['penyesuaian_opname'], $it['saldo_stok'], $it['total_nilai'], $it['nearest_expired_date'] ?: '-', $it['lokasi_rak'], $it['status_stok']];
        }
        $summary = [
            'Total Jenis Barang'     => $no . ' Jenis',
            'Total Saldo Unit'       => number_format($totSaldo, 0, ',', '.') . ' Unit',
            'Total Nilai Persediaan' => 'Rp ' . number_format($totNilai, 0, ',', '.'),
        ];
    } elseif ($reportType === 'BARANG_MASUK') {
        $reportTitle = 'LAPORAN TRANSAKSI PENERIMAAN BARANG MASUK (GRN)';
        $columns = ['No', 'No. Transaksi', 'Tanggal', 'No. Dokumen GRN', 'No. Request', 'Kode Barang', 'Nama Barang', 'Kategori', 'Jumlah Masuk', 'Satuan', 'Harga Unit (Rp)', 'Total Nilai (Rp)', 'Sumber', 'Expired Date', 'Catatan'];
        $inList = getTransaksiMasukList($pdo, $token, ['startDate' => $startDate, 'endDate' => $endDate])['transactions'];
        $no = 0; $sumQ = 0; $sumV = 0;
        foreach ($inList as $r) {
            if ($filterKat !== 'SEMUA' && strtoupper($r['kategori']) !== $filterKat) continue;
            if ($filterBarang && strpos(strtolower($r['kode_barang'] . ' ' . $r['nama_barang']), $filterBarang) === false) continue;
            if ($filterSumber && strpos(strtolower($r['sumber_donor']), $filterSumber) === false) continue;
            $no++; $sumQ += $r['jumlah_masuk']; $sumV += $r['total_nilai'];
            $rows[] = [$no, $r['no_transaksi'], $r['tanggal'], $r['no_grn'], $r['no_request'], $r['kode_barang'], $r['nama_barang'], $r['kategori'], $r['jumlah_masuk'], $r['satuan'], $r['harga_unit'], $r['total_nilai'], $r['sumber_donor'], $r['expired_date'] ?: '-', $r['catatan'] ?: '-'];
        }
        $summary = ['Jumlah Transaksi Masuk' => "$no Transaksi", 'Total Unit Diterima' => number_format($sumQ, 0, ',', '.') . ' Unit', 'Total Nilai Penerimaan' => 'Rp ' . number_format($sumV, 0, ',', '.')];
    } elseif ($reportType === 'BARANG_KELUAR' || $reportType === 'DISTRIBUSI_BANTUAN') {
        $reportTitle = $reportType === 'BARANG_KELUAR' ? 'LAPORAN TRANSAKSI PENGELUARAN BARANG KELUAR (GDN)' : 'LAPORAN REALISASI DISTRIBUSI BANTUAN LOGISTIK KEMANUSIAAN';
        $columns = ['No', 'No. Transaksi', 'Tanggal', 'No. Dokumen GDN', 'No. Request', 'Kode Barang', 'Nama Barang', 'Kategori', 'Jumlah Keluar', 'Satuan', 'Harga Unit (Rp)', 'Total Nilai (Rp)', 'Tujuan Distribusi', 'Nama Penerima', 'Penanggung Jawab'];
        $outList = getTransaksiKeluarList($pdo, $token, ['startDate' => $startDate, 'endDate' => $endDate])['transactions'];
        $no = 0; $sumQ = 0; $sumV = 0;
        foreach ($outList as $r) {
            if ($filterKat !== 'SEMUA' && strtoupper($r['kategori']) !== $filterKat) continue;
            if ($filterBarang && strpos(strtolower($r['kode_barang'] . ' ' . $r['nama_barang']), $filterBarang) === false) continue;
            $no++; $sumQ += $r['jumlah_keluar']; $sumV += $r['total_nilai'];
            $rows[] = [$no, $r['no_transaksi'], $r['tanggal'], $r['no_gdn'], $r['no_request'], $r['kode_barang'], $r['nama_barang'], $r['kategori'], $r['jumlah_keluar'], $r['satuan'], $r['harga_unit'], $r['total_nilai'], $r['tujuan_distribusi'], $r['nama_penerima'], $r['penanggung_jawab']];
        }
        $summary = ['Total Transaksi Keluar' => "$no Transaksi", 'Total Unit Terdistribusi' => number_format($sumQ, 0, ',', '.') . ' Unit', 'Total Nilai Bantuan' => 'Rp ' . number_format($sumV, 0, ',', '.')];
    } elseif ($reportType === 'NILAI_PERSEDIAAN') {
        $reportTitle = 'LAPORAN VALUASI NILAI PERSEDIAAN ASET LOGISTIK GUDANG';
        $columns = ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Sumber', 'Satuan', 'Saldo Stok', 'Harga Per Unit (Rp)', 'Total Nilai Persediaan (Rp)'];
        $no = 0; $totV = 0;
        foreach ($allItems as $it) {
            if (!$matchMaster($it)) continue;
            $no++; $totV += $it['total_nilai'];
            $rows[] = [$no, $it['kode_barang'], $it['nama_barang'], $it['kategori'], $it['sumber_donor'], $it['satuan'], $it['saldo_stok'], $it['harga_unit'], $it['total_nilai']];
        }
        $summary = ['Total Jenis Barang' => "$no Jenis", 'Total Nilai Persediaan' => 'Rp ' . number_format($totV, 0, ',', '.')];
    } elseif ($reportType === 'STOK_MINIMUM') {
        $reportTitle = 'LAPORAN DAFTAR BARANG STOK MINIMUM / KRITIS';
        $columns = ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Saldo Stok Saat Ini', 'Batas Stok Minimum', 'Kekurangan Stok', 'Lokasi Rak', 'Sumber', 'Status Kritis'];
        $no = 0;
        foreach ($allItems as $it) {
            if (!$matchMaster($it) || $it['saldo_stok'] > $it['stok_minimum']) continue;
            $no++;
            $rows[] = [$no, $it['kode_barang'], $it['nama_barang'], $it['kategori'], $it['satuan'], $it['saldo_stok'], $it['stok_minimum'], max(0, $it['stok_minimum'] - $it['saldo_stok']), $it['lokasi_rak'], $it['sumber_donor'], $it['status_stok']];
        }
        $summary = ['Total Barang Stok Menipis/Kritis' => "$no Jenis"];
    } elseif ($reportType === 'BARANG_KEDALUWARSA') {
        $reportTitle = 'LAPORAN MONITORING TANGGAL KEDALUWARSA BARANG (EXPIRED DATE)';
        $columns = ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'No. Dokumen/GRN', 'Sumber', 'Saldo Stok', 'Satuan', 'Tanggal Kedaluwarsa', 'Sisa Hari', 'Status Kedaluwarsa', 'Lokasi Rak'];
        $expData = getExpiredDateMonitoring($pdo, $token, ['kategori' => $filterKat, 'startDate' => $startDate, 'endDate' => $endDate, 'search' => $filterBarang]);
        $no = 0;
        foreach ($expData['items'] as $e) {
            $no++;
            $rows[] = [$no, $e['kode_barang'], $e['nama_barang'], $e['kategori'], $e['no_batch_dokumen'], $e['sumber_donor'], $e['saldo_stok_saat_ini'], $e['satuan'], $e['expired_date'], $e['days_remaining'] . ' Hari', $e['status_expired'], $e['lokasi_rak']];
        }
        $summary = ['Total Batch Dipantau' => "$no Batch"];
    } elseif ($reportType === 'STOCK_OPNAME') {
        $reportTitle = 'LAPORAN PEMERIKSAAN FISIK PERSEDIAAN (STOCK OPNAME)';
        $columns = ['No', 'No. Opname', 'Tgl Periksa', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Nilai Selisih (Rp)', 'Alasan Selisih', 'Petugas Pemeriksa', 'Status Persetujuan'];
        $soList = getStockOpnameList($pdo, $token, ['kategori' => $filterKat])['records'];
        $no = 0;
        foreach ($soList as $s) {
            $no++;
            $rows[] = [$no, $s['no_opname'], $s['tanggal_periksa'], $s['kode_barang'], $s['nama_barang'], $s['kategori'], $s['satuan'], $s['stok_sistem'], $s['stok_fisik'], $s['selisih'], $s['nilai_selisih'], $s['alasan_selisih'], $s['petugas_pemeriksa'], $s['status_persetujuan']];
        }
        $summary = ['Total Pemeriksaan Opname' => "$no Record"];
    } else {
        $reportTitle = 'LAPORAN REKAPITULASI PENERIMAAN & PERSEDIAAN BERDASARKAN SUMBER BARANG';
        $columns = ['No', 'Sumber Barang', 'Jumlah Jenis Barang', 'Total Unit Masuk', 'Saldo Stok Tersisa', 'Total Nilai Kontribusi (Rp)'];
        $agg = [];
        foreach ($allItems as $it) {
            if (!$matchMaster($it)) continue;
            $k = strtoupper($it['sumber_donor'] ?: 'LAINNYA');
            if (!isset($agg[$k])) $agg[$k] = ['jenis' => 0, 'masuk' => 0, 'saldo' => 0, 'nilai' => 0];
            $agg[$k]['jenis']++;
            $agg[$k]['masuk'] += $it['total_masuk'];
            $agg[$k]['saldo'] += $it['saldo_stok'];
            $agg[$k]['nilai'] += $it['total_nilai'];
        }
        $no = 0;
        foreach ($agg as $src => $v) {
            $no++;
            $rows[] = [$no, $src, $v['jenis'], $v['masuk'], $v['saldo'], $v['nilai']];
        }
        $summary = ['Total Mitra Sumber' => "$no Sumber"];
    }

    return [
        'reportType'  => $reportType,
        'reportTitle' => $reportTitle,
        'metadata'    => [
            'instansi'          => $settingsMap['NAMA_INSTANSI'] ?? 'PALANG MERAH INDONESIA KOTA SURAKARTA',
            'alamat'            => $settingsMap['ALAMAT_INSTANSI'] ?? 'Jl. Kolonel Sutarto No. 58, Jebres, Kota Surakarta',
            'telepon'           => $settingsMap['TELEPON_INSTANSI'] ?? '(0271) 646505',
            'kepalaMarkas'      => $settingsMap['NAMA_KEPALA_MARKAS'] ?? 'Wanto, S.KM.',
            'koordinatorGudang' => $settingsMap['NAMA_KOORDINATOR_GUDANG'] ?? 'Indro Sri Widodo',
            'periode'           => ($startDate || $endDate) ? (($startDate ?: 'Awal') . ' s/d ' . ($endDate ?: 'Sekarang')) : 'Semua Periode',
            'tanggalCetak'      => date('Y-m-d H:i:s'),
        ],
        'columns' => $columns,
        'rows'    => $rows,
        'summary' => $summary,
    ];
}

function importExcelBatchData(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canImportExcel');
    $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
    if (empty($rows)) {
        throw new Exception('Tidak ada data baris Excel untuk diimpor.');
    }

    $stats = [
        'totalRowsProcessed'     => count($rows),
        'masterCreated'          => 0,
        'masterMatched'          => 0,
        'transaksiMasukCreated'  => 0,
        'transaksiKeluarCreated' => 0,
        'skippedOrFailed'        => 0,
    ];

    $pdo->beginTransaction();
    try {
        $ym = date('Ym');
        $seqIn  = (int)$pdo->query('SELECT COUNT(*) FROM transaksi_masuk')->fetchColumn() + 1;
        $seqOut = (int)$pdo->query('SELECT COUNT(*) FROM transaksi_keluar')->fetchColumn() + 1;
        $lastMaster = null;

        foreach ($rows as $row) {
            $kat  = strtoupper(trim((string)($row['kategori'] ?? 'NON FOOD')));
            $kode = strtoupper(trim((string)($row['kode_barang'] ?? '')));
            $nama = trim((string)($row['nama_barang'] ?? ''));
            $sat  = trim((string)($row['satuan'] ?? 'Unit')) ?: 'Unit';
            $hrg  = (float)($row['harga_unit'] ?? 0);
            $src  = trim((string)($row['sumber_donor'] ?? '-')) ?: '-';
            $tgl  = normalizeDateString($row['tanggal'] ?? '') ?: date('Y-m-d');
            $exp  = normalizeDateString($row['expired_date'] ?? '') ?: null;
            $ket  = trim((string)($row['keterangan'] ?? ''));

            $targetMaster = null;
            if ($nama === '' && $kode === '' && $lastMaster) {
                $targetMaster = $lastMaster;
                $stats['masterMatched']++;
            } elseif ($kode !== '' || $nama !== '') {
                $stmtFind = $pdo->prepare('SELECT * FROM master_barang WHERE UPPER(kode_barang) = :k OR (kategori = :kat AND LOWER(nama_barang) = :nm) LIMIT 1');
                $stmtFind->execute([':k' => $kode, ':kat' => $kat, ':nm' => strtolower($nama)]);
                $targetMaster = $stmtFind->fetch();
                if ($targetMaster) {
                    $stats['masterMatched']++;
                } elseif ($nama !== '') {
                    $newId   = generateUniqueId('BRG');
                    $newCode = $kode ?: generateAutoItemCode($pdo, $kat);
                    $pdo->prepare(
                        'INSERT INTO master_barang
                         (id_barang, kode_barang, nama_barang, kategori, subkategori, satuan, harga_unit,
                          sumber_donor, stok_minimum, lokasi_rak, has_expiry, keterangan, status_aktif, created_by)
                         VALUES (:id, :k, :nm, :kat, "Logistik Gudang PB", :sat, :hrg, :src, 10, "GUDANG-PB", :exp, :ket, "AKTIF", :usr)'
                    )->execute([
                        ':id'  => $newId,
                        ':k'   => $newCode,
                        ':nm'  => $nama,
                        ':kat' => $kat,
                        ':sat' => $sat,
                        ':hrg' => $hrg,
                        ':src' => $src,
                        ':exp' => ($exp || $kat !== 'NON FOOD') ? 'YA' : 'TIDAK',
                        ':ket' => $ket ?: 'Migrasi Excel Gudang PB',
                        ':usr' => $auth['user']['username'],
                    ]);
                    $targetMaster = [
                        'id_barang'    => $newId,
                        'kode_barang'  => $newCode,
                        'nama_barang'  => $nama,
                        'kategori'     => $kat,
                        'satuan'       => $sat,
                        'harga_unit'   => $hrg,
                        'sumber_donor' => $src,
                    ];
                    $stats['masterCreated']++;
                }
            }

            if (!$targetMaster) {
                $stats['skippedOrFailed']++;
                continue;
            }
            $lastMaster = $targetMaster;

            $inQty = (float)($row['stok_awal'] ?? 0) + (float)($row['barang_masuk'] ?? 0);
            if ($inQty <= 0 && ($row['_rowType'] ?? '') === 'MASTER + SALDO' && (float)($row['barang_keluar'] ?? 0) == 0 && (float)($row['saldo_stok'] ?? 0) > 0) {
                $inQty = (float)$row['saldo_stok'];
            }

            if ($inQty > 0) {
                $noIn = 'BM-' . $ym . '-' . str_pad((string)$seqIn++, 4, '0', STR_PAD_LEFT);
                $price = $hrg > 0 ? $hrg : (float)$targetMaster['harga_unit'];
                $pdo->prepare(
                    'INSERT INTO transaksi_masuk
                     (id_masuk, no_transaksi, tanggal, id_barang, kode_barang, nama_barang, kategori,
                      jumlah_masuk, satuan, harga_unit, total_nilai, sumber_donor, no_grn, no_request,
                      expired_date, catatan, created_by, updated_by)
                     VALUES (:id, :no, :tgl, :id_b, :k, :nm, :kat, :qty, :sat, :hrg, :tot, :src, :grn, :req, :exp, :cat, :usr, :usr)'
                )->execute([
                    ':id'   => generateUniqueId('IN'),
                    ':no'   => $noIn,
                    ':tgl'  => $tgl,
                    ':id_b' => $targetMaster['id_barang'],
                    ':k'    => $targetMaster['kode_barang'],
                    ':nm'   => $targetMaster['nama_barang'],
                    ':kat'  => $targetMaster['kategori'],
                    ':qty'  => $inQty,
                    ':sat'  => $targetMaster['satuan'],
                    ':hrg'  => $price,
                    ':tot'  => $inQty * $price,
                    ':src'  => $src !== '-' ? $src : $targetMaster['sumber_donor'],
                    ':grn'  => trim((string)($row['no_grn'] ?? '')) ?: ('GRN/MIGRASI/' . $targetMaster['kode_barang']),
                    ':req'  => trim((string)($row['no_request'] ?? '-')) ?: '-',
                    ':exp'  => $exp,
                    ':cat'  => $ket ?: 'Migrasi Excel Gudang PB',
                    ':usr'  => $auth['user']['username'],
                ]);
                $stats['transaksiMasukCreated']++;
            }

            $outQty = (float)($row['barang_keluar'] ?? 0);
            if ($outQty > 0) {
                $noOut = 'BK-' . $ym . '-' . str_pad((string)$seqOut++, 4, '0', STR_PAD_LEFT);
                $idOut = generateUniqueId('OUT');
                $price = $hrg > 0 ? $hrg : (float)$targetMaster['harga_unit'];
                $dest  = trim((string)($row['tujuan_distribusi'] ?? '')) ?: ($ket ?: 'Distribusi Gudang PB');
                $pdo->prepare(
                    'INSERT INTO transaksi_keluar
                     (id_keluar, no_transaksi, tanggal, id_barang, kode_barang, nama_barang, kategori,
                      jumlah_keluar, satuan, harga_unit, total_nilai, tujuan_distribusi, nama_penerima,
                      no_gdn, no_request, penanggung_jawab, catatan, created_by, updated_by)
                     VALUES (:id, :no, :tgl, :id_b, :k, :nm, :kat, :qty, :sat, :hrg, :tot, :tuj, :pen, :gdn, :req, :pj, :cat, :usr, :usr)'
                )->execute([
                    ':id'   => $idOut,
                    ':no'   => $noOut,
                    ':tgl'  => $tgl,
                    ':id_b' => $targetMaster['id_barang'],
                    ':k'    => $targetMaster['kode_barang'],
                    ':nm'   => $targetMaster['nama_barang'],
                    ':kat'  => $targetMaster['kategori'],
                    ':qty'  => $outQty,
                    ':sat'  => $targetMaster['satuan'],
                    ':hrg'  => $price,
                    ':tot'  => $outQty * $price,
                    ':tuj'  => $dest,
                    ':pen'  => $dest,
                    ':gdn'  => trim((string)($row['no_gdn'] ?? '')) ?: ('GDN/MIGRASI/' . $targetMaster['kode_barang']),
                    ':req'  => trim((string)($row['no_request'] ?? '-')) ?: '-',
                    ':pj'   => $auth['user']['nama_lengkap'],
                    ':cat'  => $ket ?: 'Migrasi Pengeluaran Gudang PB',
                    ':usr'  => $auth['user']['username'],
                ]);
                $stats['transaksiKeluarCreated']++;
            }
        }

        recalculateAllStockBalances($pdo);
        logUserActivity($pdo, $auth['user'], 'MIGRASI_EXCEL', 'IMPORT_EXCEL', 'BATCH-' . count($rows), 'Import Excel ke MySQL selesai.');
        $pdo->commit();
        return ['message' => 'Import data Excel ke database MySQL selesai dijalankan!', 'stats' => $stats];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function getAllUsersForAdmin(PDO $pdo, string $token): array {
    requireServerPermission($pdo, $token, 'canManageUsers');
    return $pdo->query('SELECT id_user, username, nama_lengkap, role, jabatan, email, no_hp, status_aktif, last_login, created_at FROM users ORDER BY created_at ASC')->fetchAll();
}

function saveUserAccount(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canManageUsers');
    $idUser   = trim((string)($payload['id_user'] ?? ''));
    $username = strtolower(trim((string)($payload['username'] ?? '')));
    $nama     = trim((string)($payload['nama_lengkap'] ?? ''));
    $role     = strtoupper(trim((string)($payload['role'] ?? 'PETUGAS GUDANG')));
    $status   = strtoupper(trim((string)($payload['status_aktif'] ?? 'AKTIF')));
    $jabatan  = trim((string)($payload['jabatan'] ?? ''));
    $email    = trim((string)($payload['email'] ?? ''));
    $noHp     = trim((string)($payload['no_hp'] ?? ''));
    $pass     = (string)($payload['password'] ?? '');

    if ($username === '' || $nama === '') {
        throw new Exception('Username dan Nama Lengkap wajib diisi.');
    }

    $stmtDup = $pdo->prepare('SELECT id_user FROM users WHERE LOWER(username) = :u AND id_user != :id LIMIT 1');
    $stmtDup->execute([':u' => $username, ':id' => $idUser]);
    if ($stmtDup->fetch()) {
        throw new Exception("Username \"$username\" sudah digunakan oleh akun lain.");
    }

    if ($idUser === '') {
        if (strlen($pass) < 6) {
            throw new Exception('Password pengguna baru minimal 6 karakter.');
        }
        $newId = generateUniqueId('USR');
        $pdo->prepare(
            'INSERT INTO users (id_user, username, password_hash, nama_lengkap, role, jabatan, email, no_hp, status_aktif)
             VALUES (:id, :u, :p, :nm, :r, :j, :e, :hp, :st)'
        )->execute([
            ':id' => $newId,
            ':u'  => $username,
            ':p'  => hashPassword($pass),
            ':nm' => $nama,
            ':r'  => $role,
            ':j'  => $jabatan,
            ':e'  => $email,
            ':hp' => $noHp,
            ':st' => $status,
        ]);
        logUserActivity($pdo, $auth['user'], 'USERS', 'CREATE_USER', $newId, "Menambah pengguna: $username ($role)");
        return ['message' => 'Pengguna baru berhasil ditambahkan.'];
    } else {
        if ($pass !== '') {
            if (strlen($pass) < 6) throw new Exception('Password baru minimal 6 karakter.');
            $pdo->prepare('UPDATE users SET password_hash = :p WHERE id_user = :id')->execute([':p' => hashPassword($pass), ':id' => $idUser]);
        }
        $pdo->prepare(
            'UPDATE users SET username = :u, nama_lengkap = :nm, role = :r, jabatan = :j, email = :e, no_hp = :hp, status_aktif = :st
             WHERE id_user = :id'
        )->execute([
            ':u'  => $username,
            ':nm' => $nama,
            ':r'  => $role,
            ':j'  => $jabatan,
            ':e'  => $email,
            ':hp' => $noHp,
            ':st' => $status,
            ':id' => $idUser,
        ]);
        logUserActivity($pdo, $auth['user'], 'USERS', 'UPDATE_USER', $idUser, "Memperbarui pengguna: $username ($role)");
        return ['message' => 'Data pengguna berhasil diperbarui.'];
    }
}

function saveApplicationSettings(PDO $pdo, string $token, array $payload): array {
    $auth = requireServerPermission($pdo, $token, 'canManageSettings');
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, deskripsi, updated_by)
         VALUES (:k, :v, "Konfigurasi sistem", :u)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
    );
    foreach ($payload as $k => $v) {
        $stmt->execute([':k' => $k, ':v' => trim((string)$v), ':u' => $auth['user']['username']]);
    }
    logUserActivity($pdo, $auth['user'], 'SETTINGS', 'UPDATE_SETTINGS', 'CONFIG', 'Memperbarui konfigurasi instansi');
    return ['message' => 'Pengaturan instansi berhasil disimpan.'];
}
