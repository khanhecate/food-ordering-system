<?php
// Dashboard content
$totalProduk = $db->query("SELECT COUNT(*) FROM produk WHERE is_active = 1")->fetchColumn();
$totalStokRendah = $db->query("SELECT COUNT(*) FROM produk WHERE is_active = 1 AND stok <= 5")->fetchColumn();

$today = date('Y-m-d');
$trxHariIni = $db->prepare("SELECT COUNT(*) as jumlah, COALESCE(SUM(total), 0) as omzet FROM transaksi WHERE DATE(tanggal) = :today AND status != 'batal'");
$trxHariIni->execute([':today' => $today]);
$trxData = $trxHariIni->fetch();

$orderPending = $db->query("SELECT COUNT(*) FROM transaksi WHERE status IN ('paid', 'packing')")->fetchColumn();
$recentOrders = $db->query("SELECT id, no_transaksi, total, status, nama_penerima, created_at FROM transaksi ORDER BY created_at DESC LIMIT 5")->fetchAll();
$produkTerlaris = $db->query("SELECT dt.nama_produk, SUM(dt.qty) as total_terjual FROM detail_transaksi dt JOIN transaksi t ON t.id = dt.transaksi_id WHERE t.status != 'batal' AND MONTH(t.tanggal) = MONTH(CURDATE()) AND YEAR(t.tanggal) = YEAR(CURDATE()) GROUP BY dt.nama_produk ORDER BY total_terjual DESC LIMIT 5")->fetchAll();
$stokRendah = $db->query("SELECT id, kode_bunga, nama_bunga, stok FROM produk WHERE is_active = 1 AND stok <= 5 ORDER BY stok ASC LIMIT 10")->fetchAll();
?>

<h1 class="page-title">Dashboard</h1>
<p style="color:var(--gray);font-size:0.88rem;margin-top:-15px;margin-bottom:20px;"><?php echo date('l, d F Y'); ?></p>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="card summary-card">
        <div><p class="summary-label">Omzet Hari Ini</p><p class="summary-value">Rp <?php echo number_format($trxData['omzet'], 0, ',', '.'); ?></p></div>
    </div>
    <div class="card summary-card">
        <div><p class="summary-label">Transaksi Hari Ini</p><p class="summary-value"><?php echo $trxData['jumlah']; ?></p></div>
    </div>
    <div class="card summary-card">
        <div><p class="summary-label">Perlu Diproses</p><p class="summary-value"><?php echo $orderPending; ?> order</p></div>
    </div>
    <div class="card summary-card">
        <div><p class="summary-label">Total Produk</p><p class="summary-value"><?php echo $totalProduk; ?></p></div>
    </div>
    <div class="card summary-card">
        <div><p class="summary-label">Stok Rendah</p><p class="summary-value"><?php echo $totalStokRendah; ?> produk</p></div>
    </div>
</div>

<!-- Recent Orders & Produk Terlaris -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
    <section class="card">
        <h3>Transaksi Terbaru</h3>
        <?php if (!empty($recentOrders)): ?>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>No. Transaksi</th><th>Penerima</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><a href="?page=order-detail&id=<?php echo $o['id']; ?>" style="color:var(--primary);text-decoration:none;"><code><?php echo $o['no_transaksi']; ?></code></a></td>
                        <td><?php echo htmlspecialchars($o['nama_penerima'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format($o['total'], 0, ',', '.'); ?></td>
                        <td><span class="badge badge-ok"><?php echo $o['status']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:var(--gray);font-size:0.9rem;">Belum ada transaksi</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3>Produk Terlaris (Bulan Ini)</h3>
        <?php if (!empty($produkTerlaris)): ?>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Produk</th><th>Terjual</th></tr></thead>
                <tbody>
                    <?php foreach ($produkTerlaris as $pt): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pt['nama_produk']); ?></td>
                        <td><strong><?php echo $pt['total_terjual']; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:var(--gray);font-size:0.9rem;">Belum ada data</p>
        <?php endif; ?>
    </section>
</div>

<!-- Stok Rendah -->
<?php if (!empty($stokRendah)): ?>
<section class="card">
    <h3>Produk Stok Rendah (≤ 5)</h3>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Kode</th><th>Nama Produk</th><th>Stok</th></tr></thead>
            <tbody>
                <?php foreach ($stokRendah as $p): ?>
                <tr>
                    <td><a href="?page=produk&edit=<?php echo $p['id']; ?>" style="color:var(--primary);text-decoration:none;"><code><?php echo $p['kode_bunga']; ?></code></a></td>
                    <td><?php echo htmlspecialchars($p['nama_bunga']); ?></td>
                    <td><span class="badge badge-danger"><?php echo $p['stok']; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<section class="card">
    <h3>Menu Cepat</h3>
    <div class="quick-links">
        <a href="?page=produk" class="btn btn-primary">+ Tambah Produk</a>
        <a href="?page=transaksi" class="btn btn-secondary">Lihat Transaksi</a>
    </div>
</section>
