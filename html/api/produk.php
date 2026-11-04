<?php
// API: List Produk (untuk shop customer)
// GET /api/produk.php
// GET /api/produk.php?kategori=1
// GET /api/produk.php?search=mawar

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Query params
$kategori = $_GET['kategori'] ?? null;
$search = $_GET['search'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

// Build query
$where = ['p.is_active = 1', 'p.stok > 0'];
$params = [];

if ($kategori) {
    $where[] = 'p.kategori_id = :kategori';
    $params[':kategori'] = (int)$kategori;
}

if ($search) {
    $where[] = '(p.nama_bunga LIKE :search OR p.kode_bunga LIKE :search2)';
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}

$whereSQL = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM produk p WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['total'];

// Fetch data
$sql = "SELECT p.id, p.kode_bunga, p.nama_bunga, p.harga_jual, p.stok, p.foto, p.deskripsi,
               k.nama_kategori
        FROM produk p
        LEFT JOIN kategori_produk k ON p.kategori_id = k.id
        WHERE $whereSQL
        ORDER BY p.nama_bunga ASC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$produk = $stmt->fetchAll();

// Response
echo json_encode([
    'success' => true,
    'data' => $produk,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);
