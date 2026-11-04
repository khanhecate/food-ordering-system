<?php
$user = $_SESSION['user'];

// Handle confirm delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $orderId = (int)$_POST['confirm_order'];
    $db->prepare("UPDATE transaksi SET status = 'done' WHERE id = :id AND user_id = :uid AND status = 'delivered'")->execute([':id' => $orderId, ':uid' => $user['id']]);
    header("Location: /shop/?page=tracking&detail={$orderId}&msg=confirmed");
    exit;
}

// Auto-complete delivered > 3 days
$db->query("UPDATE transaksi SET status = 'done' WHERE status = 'delivered' AND updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY)");

$successMsg = '';
if (isset($_GET['success'])) $successMsg = 'Pesanan ' . htmlspecialchars($_GET['success']) . ' berhasil dibuat!';
if (isset($_GET['msg']) && $_GET['msg'] === 'confirmed') $successMsg = 'Pesanan dikonfirmasi selesai!';

// Fetch orders
$stmt = $db->prepare("SELECT * FROM transaksi WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $user['id']]);
$orders = $stmt->fetchAll();

// Detail
$orderDetail = null;
if (isset($_GET['detail'])) {
    $detailStmt = $db->prepare("SELECT t.*, dt.nama_produk, dt.harga_jual, dt.qty, dt.subtotal as item_subtotal FROM transaksi t JOIN detail_transaksi dt ON dt.transaksi_id = t.id WHERE t.id = :id AND t.user_id = :uid");
    $detailStmt->execute([':id' => (int)$_GET['detail'], ':uid' => $user['id']]);
    $orderDetail = $detailStmt->fetchAll();
}

$statusMap = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#9e9e9e', 'icon' => '⏳'],
    'paid' => ['label' => 'Pembayaran Diterima', 'color' => '#2196f3', 'icon' => '✅'],
    'packing' => ['label' => 'Sedang Dikemas', 'color' => '#ff9800', 'icon' => '📦'],
    'shipping' => ['label' => 'Dalam Pengiriman', 'color' => '#9c27b0', 'icon' => '🚚'],
    'delivered' => ['label' => 'Sudah Sampai', 'color' => '#4caf50', 'icon' => '🏠'],
    'done' => ['label' => 'Selesai', 'color' => '#388e3c', 'icon' => '🎉'],
    'batal' => ['label' => 'Dibatalkan', 'color' => '#f44336', 'icon' => '❌'],
];
$statusSteps = ['paid', 'packing', 'shipping', 'delivered', 'done'];
?>

<div class="container tracking-page">
    <h1>Pesanan Saya</h1>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>

    <?php if ($orderDetail): ?>
        <?php
            $order = $orderDetail[0];
            $st = $statusMap[$order['status']] ?? $statusMap['pending'];
            $currentStep = array_search($order['status'], $statusSteps);
        ?>
        <a href="?page=tracking" class="back-link">← Kembali ke daftar pesanan</a>

        <div class="order-detail-card">
            <div class="order-detail-header">
                <div>
                    <h2><?php echo $order['no_transaksi']; ?></h2>
                    <p class="order-date"><?php echo date('d M Y, H:i', strtotime($order['tanggal'])); ?></p>
                </div>
                <span class="status-badge" style="background:<?php echo $st['color']; ?>"><?php echo $st['icon'] . ' ' . $st['label']; ?></span>
            </div>

            <?php if ($order['status'] !== 'batal' && $order['status'] !== 'pending'): ?>
            <div class="progress-tracker">
                <?php foreach ($statusSteps as $i => $step): ?>
                    <?php $stepInfo = $statusMap[$step]; $isActive = $currentStep !== false && $i <= $currentStep; $isCurrent = $order['status'] === $step; ?>
                    <div class="progress-step <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCurrent ? 'current' : ''; ?>">
                        <div class="step-dot"><?php echo $stepInfo['icon']; ?></div>
                        <span class="step-label"><?php echo $stepInfo['label']; ?></span>
                    </div>
                    <?php if ($i < count($statusSteps) - 1): ?><div class="step-line <?php echo ($currentStep !== false && $i < $currentStep) ? 'active' : ''; ?>"></div><?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="detail-section">
                <h3>Item Pesanan</h3>
                <div class="detail-items">
                    <?php foreach ($orderDetail as $item): ?>
                    <div class="detail-item"><span><?php echo htmlspecialchars($item['nama_produk']); ?> x <?php echo $item['qty']; ?></span><span>Rp <?php echo number_format($item['item_subtotal'], 0, ',', '.'); ?></span></div>
                    <?php endforeach; ?>
                    <div class="detail-item total"><span><strong>Total</strong></span><span><strong>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></strong></span></div>
                </div>
            </div>

            <div class="detail-section">
                <h3>Info Pengiriman</h3>
                <div class="shipping-info">
                    <p><strong>Penerima:</strong> <?php echo htmlspecialchars($order['nama_penerima']); ?></p>
                    <p><strong>Telepon:</strong> <?php echo htmlspecialchars($order['telp_penerima']); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($order['alamat_pengiriman']); ?></p>
                    <?php if ($order['catatan']): ?><p><strong>Catatan:</strong> <?php echo htmlspecialchars($order['catatan']); ?></p><?php endif; ?>
                </div>
            </div>

            <div class="detail-section">
                <h3>Pembayaran</h3>
                <div class="shipping-info">
                    <p><strong>Metode:</strong> <?php echo strtoupper($order['metode_bayar']); ?></p>
                    <p><strong>Ref:</strong> <code><?php echo $order['payment_ref']; ?></code></p>
                </div>
            </div>

            <?php if ($order['status'] === 'delivered'): ?>
            <div class="detail-section confirm-section">
                <h3>Konfirmasi Penerimaan</h3>
                <p class="confirm-text">Pesanan sudah sampai? Konfirmasi untuk menyelesaikan pesanan ini.</p>
                <p class="auto-confirm-note">Otomatis selesai dalam 3 hari jika tidak dikonfirmasi.</p>
                <form method="POST">
                    <input type="hidden" name="confirm_order" value="<?php echo $order['id']; ?>">
                    <button type="submit" class="btn-confirm" onclick="return confirm('Konfirmasi pesanan sudah diterima?')">Pesanan Sudah Diterima</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <p>Belum ada pesanan</p>
                <a href="?page=katalog" class="btn-shop">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php $st = $statusMap[$order['status']] ?? $statusMap['pending']; ?>
                    <a href="?page=tracking&detail=<?php echo $order['id']; ?>" class="order-card">
                        <div class="order-card-top">
                            <span class="order-no"><?php echo $order['no_transaksi']; ?></span>
                            <span class="status-badge" style="background:<?php echo $st['color']; ?>"><?php echo $st['icon'] . ' ' . $st['label']; ?></span>
                        </div>
                        <div class="order-card-bottom">
                            <span class="order-date"><?php echo date('d M Y, H:i', strtotime($order['tanggal'])); ?></span>
                            <span class="order-total">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span>
                        </div>
                        <?php if ($order['status'] === 'delivered'): ?>
                            <div class="order-card-action">Perlu konfirmasi penerimaan</div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
