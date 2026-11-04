<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID required']);
    exit;
}

// Fetch PO header
$stmt = $db->prepare("SELECT p.*, s.nama as supplier_nama FROM pembelian_stok p LEFT JOIN supplier s ON s.id = p.supplier_id WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$po = $stmt->fetch();

if (!$po) {
    echo json_encode(['success' => false, 'message' => 'PO not found']);
    exit;
}

// Fetch items
$itemStmt = $db->prepare("SELECT dp.*, pr.nama_bunga as nama_produk FROM detail_pembelian_stok dp LEFT JOIN produk pr ON pr.id = dp.produk_id WHERE dp.pembelian_id = :id");
$itemStmt->execute([':id' => $id]);
$items = $itemStmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => [
        'id' => $po['id'],
        'no_pembelian' => $po['no_pembelian'],
        'supplier_nama' => $po['supplier_nama'],
        'tanggal' => date('d M Y, H:i', strtotime($po['tanggal'])),
        'total' => $po['total'],
        'catatan' => $po['catatan'],
        'items' => $items
    ]
]);
