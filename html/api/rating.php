<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../config/database.php';
$db = getDB();

// GET: Ambil rata-rata rating per produk
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $produkId = $_GET['produk_id'] ?? null;
    
    if ($produkId) {
        // Rating untuk 1 produk
        $stmt = $db->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_rating FROM rating_produk WHERE produk_id = :id");
        $stmt->execute([':id' => (int)$produkId]);
        $data = $stmt->fetch();
        
        // Rating user saat ini (jika login)
        $userRating = 0;
        if (isset($_SESSION['user'])) {
            $uStmt = $db->prepare("SELECT rating FROM rating_produk WHERE produk_id = :pid AND user_id = :uid");
            $uStmt->execute([':pid' => (int)$produkId, ':uid' => $_SESSION['user']['id']]);
            $userRating = (int)($uStmt->fetchColumn() ?: 0);
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'avg_rating' => round((float)$data['avg_rating'], 1),
                'total_rating' => (int)$data['total_rating'],
                'user_rating' => $userRating
            ]
        ]);
    } else {
        // Rating semua produk (bulk)
        $stmt = $db->query("SELECT produk_id, ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_rating FROM rating_produk GROUP BY produk_id");
        $ratings = [];
        while ($row = $stmt->fetch()) {
            $ratings[$row['produk_id']] = [
                'avg_rating' => (float)$row['avg_rating'],
                'total_rating' => (int)$row['total_rating']
            ];
        }
        echo json_encode(['success' => true, 'data' => $ratings]);
    }
    exit;
}

// POST: Submit rating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Harus login untuk memberi rating']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $produkId = (int)($input['produk_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $userId = $_SESSION['user']['id'];
    
    if ($produkId <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating harus 1-5']);
        exit;
    }
    
    // Upsert (insert or update)
    $stmt = $db->prepare("INSERT INTO rating_produk (produk_id, user_id, rating) VALUES (:pid, :uid, :rating) ON DUPLICATE KEY UPDATE rating = :rating2");
    $stmt->execute([':pid' => $produkId, ':uid' => $userId, ':rating' => $rating, ':rating2' => $rating]);
    
    // Return updated avg
    $avgStmt = $db->prepare("SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_rating FROM rating_produk WHERE produk_id = :id");
    $avgStmt->execute([':id' => $produkId]);
    $data = $avgStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Rating berhasil disimpan',
        'data' => [
            'avg_rating' => (float)$data['avg_rating'],
            'total_rating' => (int)$data['total_rating'],
            'user_rating' => $rating
        ]
    ]);
    exit;
}
