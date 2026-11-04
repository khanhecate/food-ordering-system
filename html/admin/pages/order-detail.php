<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ?page=transaksi'); exit; }

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    $allowed = ['paid', 'packing', 'shipping', 'delivered', 'done', 'batal'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE transaksi SET status = :status WHERE id = :id")->execute([':status' => $newStatus, ':id' => $id]);
        header("Location: ?page=order-detail&id={$id}&msg=updated");
        exit;
    }
}

$stmt = $db->prepare("SELECT t.*, u.nama_lengkap as customer_name FROM transaksi t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = :id");
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();
if (!$order) { header('Location: ?page=transaksi'); exit; }

$itemStmt = $db->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id = :id");
$itemStmt->execute([':id' => $id]);
$items = $itemStmt->fetchAll();

$statusMap = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'color' => '#9e9e9e'],
    'paid' => ['label' => 'Dibayar', 'color' => '#2196f3'],
    'packing' => ['label' => 'Di Proses', 'color' => '#ff9800'],
    'shipping' => ['label' => 'Dikirim', 'color' => '#9c27b0'],
    'delivered' => ['label' => 'Sampai', 'color' => '#4caf50'],
    'done' => ['label' => 'Selesai', 'color' => '#388e3c'],
    'batal' => ['label' => 'Dibatalkan', 'color' => '#f44336'],
];
$st = $statusMap[$order['status']] ?? $statusMap['pending'];
?>

<h1 class="page-title">Order Detail</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">Status berhasil diupdate!</div>
<?php endif; ?>

<a href="?page=transaksi" style="display:inline-block;margin-bottom:15px;color:var(--primary);text-decoration:none;font-size:0.9rem;">← Kembali</a>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:15px;">
        <div>
            <h3 style="margin:0;"><?php echo $order['no_transaksi']; ?></h3>
            <p style="color:var(--gray);font-size:0.85rem;margin-top:3px;"><?php echo date('d M Y, H:i', strtotime($order['tanggal'])); ?></p>
        </div>
        <span class="badge" style="background:<?php echo $st['color']; ?>;color:white;font-size:0.85rem;"><?php echo $st['label']; ?></span>
    </div>
    <table class="table">
        <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                <td>Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?></td>
                <td><?php echo $item['qty']; ?></td>
                <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="3" style="text-align:right;"><strong>Total</strong></td><td><strong>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></strong></td></tr>
        </tbody>
    </table>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
    <div class="card">
        <h3>Pengiriman</h3>
        <p style="font-size:0.88rem;margin-bottom:6px;"><strong>Penerima:</strong> <?php echo htmlspecialchars($order['nama_penerima'] ?? '-'); ?></p>
        <p style="font-size:0.88rem;margin-bottom:6px;"><strong>Telepon:</strong> <?php echo htmlspecialchars($order['telp_penerima'] ?? '-'); ?></p>
        <p style="font-size:0.88rem;margin-bottom:6px;"><strong>Alamat:</strong> <?php echo htmlspecialchars($order['alamat_pengiriman'] ?? '-'); ?></p>
        <?php if ($order['catatan']): ?><p style="font-size:0.88rem;"><strong>Catatan:</strong> <?php echo htmlspecialchars($order['catatan']); ?></p><?php endif; ?>
    </div>
    <div class="card">
        <h3>Pembayaran</h3>
        <p style="font-size:0.88rem;margin-bottom:6px;"><strong>Metode:</strong> <?php echo strtoupper($order['metode_bayar']); ?></p>
        <p style="font-size:0.88rem;margin-bottom:6px;"><strong>Ref:</strong> <code><?php echo $order['payment_ref'] ?? '-'; ?></code></p>
        <p style="font-size:0.88rem;"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></p>
    </div>
</div>

<!-- Update Status -->
<?php if (!in_array($order['status'], ['done', 'batal'])): ?>
<div class="card" style="margin-top:15px;">
    <h3>Update Status</h3>
    <form method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select name="new_status" style="padding:8px 12px;border:1.5px solid #ecf0f1;border-radius:6px;font-size:0.9rem;">
            <option value="paid" <?php echo $order['status']==='paid'?'selected':''; ?>>Dibayar</option>
            <option value="packing" <?php echo $order['status']==='packing'?'selected':''; ?>>Di Proses</option>
            <option value="shipping" <?php echo $order['status']==='shipping'?'selected':''; ?>>Dikirim</option>
            <option value="delivered" <?php echo $order['status']==='delivered'?'selected':''; ?>>Sampai</option>
            <option value="done" <?php echo $order['status']==='done'?'selected':''; ?>>Selesai</option>
            <option value="batal">Batalkan</option>
        </select>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Update status?')">Update Status</button>
    </form>
</div>
<?php endif; ?>
