<?php
// API: List Kategori
// GET /api/kategori.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

$db = getDB();

$stmt = $db->query("SELECT id, nama_kategori, deskripsi FROM kategori_produk ORDER BY nama_kategori ASC");
$kategori = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => $kategori
]);
