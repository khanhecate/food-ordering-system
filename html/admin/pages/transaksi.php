<?php
// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $allowed = ['paid', 'packing', 'shipping', 'delivered', 'done', 'batal'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE transaksi SET status = :status WHERE id = :id")->execute([':status' => $newStatus, ':id' => $orderId]);
        header('Location: ?page=transaksi&msg=updated');
        exit;
    }
}

$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['dari'] ?? '';
$dateTo = $_GET['sampai'] ?? '';

$where = '1=1';
$params = [];
if ($filterStatus) { $where .= ' AND t.status = :status'; $params[':status'] = $filterStatus; }
if ($search) { $where .= ' AND (t.no_transaksi LIKE :s1 OR u.nama_lengkap LIKE :s2 OR u.username LIKE :s3 OR t.nama_penerima LIKE :s4)'; $params[':s1'] = "%$search%"; $params[':s2'] = "%$search%"; $params[':s3'] = "%$search%"; $params[':s4'] = "%$search%"; }
if ($dateFrom) { $where .= ' AND DATE(t.tanggal) >= :dari'; $params[':dari'] = $dateFrom; }
if ($dateTo) { $where .= ' AND DATE(t.tanggal) <= :sampai'; $params[':sampai'] = $dateTo; }

$sql = "SELECT t.*, u.nama_lengkap as customer_name, (SELECT COUNT(*) FROM detail_transaksi WHERE transaksi_id = t.id) as item_count FROM transaksi t LEFT JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusMap = [
    'pending' => ['label' => 'Pending', 'color' => '#9e9e9e'],
    'paid' => ['label' => 'Paid', 'color' => '#2196f3'],
    'packing' => ['label' => 'Packing', 'color' => '#ff9800'],
    'shipping' => ['label' => 'Shipping', 'color' => '#9c27b0'],
    'delivered' => ['label' => 'Delivered', 'color' => '#4caf50'],
    'done' => ['label' => 'Done', 'color' => '#388e3c'],
    'batal' => ['label' => 'Batal', 'color' => '#f44336'],
];
$nextStatus = ['paid' => 'packing', 'packing' => 'shipping', 'shipping' => 'delivered', 'delivered' => 'done'];
?>

<h1 class="page-title">Manage Orders</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">Status order berhasil diupdate!</div>
<?php endif; ?>

<!-- Filter (Status + Date Range) -->
<div class="card" style="padding:12px 16px;">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="hidden" name="page" value="transaksi">
        <?php if ($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
        <span style="font-size:0.85rem;font-weight:600;">Status:</span>
        <a href="?page=transaksi<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $dateFrom ? '&dari='.$dateFrom : ''; ?><?php echo $dateTo ? '&sampai='.$dateTo : ''; ?>" class="btn btn-sm <?php echo !$filterStatus ? 'btn-primary' : 'btn-secondary'; ?>">Semua</a>
        <?php foreach ($statusMap as $key => $val): ?>
            <a href="?page=transaksi&status=<?php echo $key; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $dateFrom ? '&dari='.$dateFrom : ''; ?><?php echo $dateTo ? '&sampai='.$dateTo : ''; ?>" class="btn btn-sm <?php echo $filterStatus === $key ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $val['label']; ?></a>
        <?php endforeach; ?>
        <span style="margin-left:auto;"></span>
        <input type="date" name="dari" value="<?php echo $dateFrom; ?>" onchange="this.form.submit()" style="padding:7px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
        <span style="color:var(--gray);">-</span>
        <input type="date" name="sampai" value="<?php echo $dateTo; ?>" onchange="this.form.submit()" style="padding:7px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
        <?php if ($dateFrom || $dateTo): ?>
            <a href="?page=transaksi<?php echo $filterStatus ? '&status='.$filterStatus : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-secondary btn-sm">Reset Tanggal</a>
        <?php endif; ?>
    </form>
</div>

<!-- Orders Table -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
        <h3 style="margin:0;">Daftar Order (<?php echo count($orders); ?>)</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="order-search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari transaksi, user, penerima..." style="padding:7px 12px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;outline:none;width:250px;">
            <?php if ($search): ?>
                <a href="?page=transaksi<?php echo $filterStatus ? '&status='.$filterStatus : ''; ?><?php echo $dateFrom ? '&dari='.$dateFrom : ''; ?><?php echo $dateTo ? '&sampai='.$dateTo : ''; ?>" class="btn btn-secondary btn-sm">x</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>No. Transaksi</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <?php $st = $statusMap[$order['status']] ?? $statusMap['pending']; ?>
                <tr>
                    <td><a href="?page=order-detail&id=<?php echo $order['id']; ?>" style="color:var(--primary);text-decoration:none;"><code><?php echo $order['no_transaksi']; ?></code></a></td>
                    <td><?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></td>
                    <td><?php echo $order['item_count']; ?> item</td>
                    <td><strong>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></strong></td>
                    <td><span class="badge" style="background:<?php echo $st['color']; ?>;color:white;"><?php echo $st['label']; ?></span></td>
                    <td style="font-size:0.82rem;"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td style="white-space:nowrap;">
                        <?php if (isset($nextStatus[$order['status']])): ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="order_id" value="<?php echo $order['id']; ?>"><input type="hidden" name="new_status" value="<?php echo $nextStatus[$order['status']]; ?>"><button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Update status?')">→ <?php echo $statusMap[$nextStatus[$order['status']]]['label']; ?></button></form>
                        <?php endif; ?>
                        <a href="?page=order-detail&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-edit">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?><tr><td colspan="7" class="empty">Belum ada order</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    let timeout;
    const input = document.getElementById('order-search');
    if (!input) return;
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'transaksi');
            if (input.value.trim()) { params.set('search', input.value.trim()); }
            else { params.delete('search'); }
            window.location.href = '?' + params.toString();
        }, 400);
    });
})();
</script>
