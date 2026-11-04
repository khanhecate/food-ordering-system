<?php
$tab = $_GET['tab'] ?? 'overview';
$periode = $_GET['periode'] ?? 'bulan_ini';
$customFrom = $_GET['dari'] ?? '';
$customTo = $_GET['sampai'] ?? '';

switch ($periode) {
    case 'hari_ini': $dateFrom = date('Y-m-d'); $dateTo = date('Y-m-d'); $periodeLabel = 'Hari Ini'; break;
    case 'minggu_ini': $dateFrom = date('Y-m-d', strtotime('monday this week')); $dateTo = date('Y-m-d'); $periodeLabel = 'Minggu Ini'; break;
    case 'bulan_ini': $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); $periodeLabel = date('F Y'); break;
    case 'bulan_lalu': $dateFrom = date('Y-m-01', strtotime('first day of last month')); $dateTo = date('Y-m-t', strtotime('last day of last month')); $periodeLabel = date('F Y', strtotime('last month')); break;
    case 'custom': $dateFrom = $customFrom ?: date('Y-m-01'); $dateTo = $customTo ?: date('Y-m-d'); $periodeLabel = date('d M Y', strtotime($dateFrom)) . ' - ' . date('d M Y', strtotime($dateTo)); break;
    default: $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); $periodeLabel = date('F Y');
}

$tabTitles = ['overview'=>'Laporan Penjualan','stok'=>'Stok & Inventaris','keuangan'=>'Laporan Keuangan','pelanggan'=>'Laporan Pelanggan','supplier'=>'Laporan Supplier'];
?>
<h1 class="page-title"><?php echo $tabTitles[$tab] ?? 'Laporan'; ?></h1>

<!-- Filter Periode -->
<div class="card" style="padding:14px 16px;margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <p style="color:var(--gray);font-size:0.85rem;margin:0;">Periode: <strong style="color:var(--dark);"><?php echo $periodeLabel; ?></strong></p>
        <form method="GET" id="laporan-form" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <input type="hidden" name="page" value="laporan">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <select name="periode" onchange="changePeriode(this.value)" style="padding:8px 12px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
                <option value="hari_ini" <?php echo $periode==='hari_ini'?'selected':''; ?>>Hari Ini</option>
                <option value="minggu_ini" <?php echo $periode==='minggu_ini'?'selected':''; ?>>Minggu Ini</option>
                <option value="bulan_ini" <?php echo $periode==='bulan_ini'?'selected':''; ?>>Bulan Ini</option>
                <option value="bulan_lalu" <?php echo $periode==='bulan_lalu'?'selected':''; ?>>Bulan Lalu</option>
                <option value="custom" <?php echo $periode==='custom'?'selected':''; ?>>Custom</option>
            </select>
            <div id="custom-dates" style="display:<?php echo $periode==='custom'?'flex':'none'; ?>;gap:8px;align-items:center;">
                <input type="date" name="dari" value="<?php echo $dateFrom; ?>" onchange="submitCustomDate()" style="padding:7px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
                <span style="color:var(--gray);">-</span>
                <input type="date" name="sampai" value="<?php echo $dateTo; ?>" onchange="submitCustomDate()" style="padding:7px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="printReport()">Print</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="downloadPDF()">PDF</button>
        </form>
    </div>
</div>

<?php if ($tab === 'overview'): ?>
<?php
$stmt = $db->prepare("SELECT COUNT(*) as total_transaksi, COALESCE(SUM(total),0) as total_penjualan, COALESCE(AVG(total),0) as avg_per_transaksi FROM transaksi WHERE DATE(tanggal) BETWEEN :dari AND :sampai AND status NOT IN ('batal','pending')");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$penjualan = $stmt->fetch();

$stmt = $db->prepare("SELECT COALESCE(SUM(dt.qty * p.harga_beli),0) as total_hpp FROM detail_transaksi dt JOIN transaksi t ON t.id = dt.transaksi_id JOIN produk p ON p.id = dt.produk_id WHERE DATE(t.tanggal) BETWEEN :dari AND :sampai AND t.status NOT IN ('batal','pending')");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$hpp = (float)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) as total_po, COALESCE(SUM(total),0) as total_pembelian FROM pembelian_stok WHERE DATE(tanggal) BETWEEN :dari AND :sampai");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$pembelian = $stmt->fetch();

$totalPenjualan = (float)$penjualan['total_penjualan'];
$grossProfit = $totalPenjualan - $hpp;
$netMargin = $totalPenjualan > 0 ? ($grossProfit / $totalPenjualan) * 100 : 0;

$stmt = $db->prepare("SELECT dt.nama_produk, SUM(dt.qty) as qty_terjual, SUM(dt.subtotal) as revenue FROM detail_transaksi dt JOIN transaksi t ON t.id = dt.transaksi_id WHERE DATE(t.tanggal) BETWEEN :dari AND :sampai AND t.status NOT IN ('batal','pending') GROUP BY dt.nama_produk ORDER BY qty_terjual DESC LIMIT 10");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$topProducts = $stmt->fetchAll();

$stmt = $db->prepare("SELECT DATE(tanggal) as tgl, COUNT(*) as jml_trx, SUM(total) as omzet FROM transaksi WHERE DATE(tanggal) BETWEEN :dari AND :sampai AND status NOT IN ('batal','pending') GROUP BY DATE(tanggal) ORDER BY tgl");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$dailySales = $stmt->fetchAll();

$stmt = $db->prepare("SELECT status, COUNT(*) as jumlah FROM transaksi WHERE DATE(tanggal) BETWEEN :dari AND :sampai GROUP BY status");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$statusBreakdown = $stmt->fetchAll();
?>

<!-- Summary Cards -->
<div class="summary-grid" style="margin-bottom:20px;">
    <div class="card summary-card"><div><p class="summary-label">Total Penjualan</p><p class="summary-value">Rp <?php echo number_format($totalPenjualan,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">HPP (Modal)</p><p class="summary-value">Rp <?php echo number_format($hpp,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Gross Profit</p><p class="summary-value" style="color:<?php echo $grossProfit>=0?'#2e7d32':'#c62828'; ?>">Rp <?php echo number_format($grossProfit,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Net Margin</p><p class="summary-value" style="color:<?php echo $netMargin>=0?'#2e7d32':'#c62828'; ?>"><?php echo number_format($netMargin,1); ?>%</p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Transaksi</p><p class="summary-value"><?php echo $penjualan['total_transaksi']; ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Avg / Transaksi</p><p class="summary-value">Rp <?php echo number_format($penjualan['avg_per_transaksi'],0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Total Pembelian</p><p class="summary-value">Rp <?php echo number_format($pembelian['total_pembelian'],0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Jumlah PO</p><p class="summary-value"><?php echo $pembelian['total_po']; ?></p></div></div>
</div>

<!-- Chart + Tables -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
    <div class="card"><h3>Penjualan Harian</h3><canvas id="dailyChart" height="180"></canvas></div>
    <div class="card"><h3>Produk Terlaris</h3><canvas id="productChart" height="180"></canvas></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
    <div class="card"><h3>Revenue vs Modal</h3><canvas id="profitChart" height="180"></canvas></div>
    <div class="card"><h3>Status Order</h3><canvas id="statusChart" height="180"></canvas></div>
</div>

<!-- Tables -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
    <div class="card"><h3>Penjualan Harian</h3>
        <?php if (!empty($dailySales)): ?>
        <div class="table-wrapper"><table class="table"><thead><tr><th>Tanggal</th><th>Trx</th><th>Omzet</th></tr></thead><tbody>
            <?php foreach ($dailySales as $d): ?><tr><td><?php echo date('d M',strtotime($d['tgl'])); ?></td><td><?php echo $d['jml_trx']; ?></td><td>Rp <?php echo number_format($d['omzet'],0,',','.'); ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php else: ?><p style="color:var(--gray);font-size:0.88rem;">Tidak ada data.</p><?php endif; ?>
    </div>
    <div class="card"><h3>Status Breakdown</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php foreach ($statusBreakdown as $sb): ?><div style="padding:8px 14px;background:var(--gray-light);border-radius:8px;font-size:0.85rem;"><strong><?php echo ucfirst($sb['status']); ?></strong>: <?php echo $sb['jumlah']; ?></div><?php endforeach; ?>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('dailyChart'),{type:'line',data:{labels:<?php echo json_encode(array_map(function($d){return date('d M',strtotime($d['tgl']));},$dailySales)); ?>,datasets:[{label:'Omzet',data:<?php echo json_encode(array_map(function($d){return (float)$d['omzet'];},$dailySales)); ?>,borderColor:'#e91e63',backgroundColor:'rgba(233,30,99,0.1)',fill:true,tension:0.3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('productChart'),{type:'bar',data:{labels:<?php echo json_encode(array_map(function($p){return $p['nama_produk'];},$topProducts)); ?>,datasets:[{data:<?php echo json_encode(array_map(function($p){return (int)$p['qty_terjual'];},$topProducts)); ?>,backgroundColor:['#e91e63','#2196f3','#4caf50','#ff9800','#9c27b0','#607d8b','#f44336','#009688','#795548','#cddc39']}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('profitChart'),{type:'doughnut',data:{labels:['Profit','Modal (HPP)'],datasets:[{data:[<?php echo max(0,$grossProfit); ?>,<?php echo $hpp; ?>],backgroundColor:['#4caf50','#ff9800']}]},options:{responsive:true}});
new Chart(document.getElementById('statusChart'),{type:'pie',data:{labels:<?php echo json_encode(array_map(function($s){return ucfirst($s['status']);},$statusBreakdown)); ?>,datasets:[{data:<?php echo json_encode(array_map(function($s){return (int)$s['jumlah'];},$statusBreakdown)); ?>,backgroundColor:['#9e9e9e','#2196f3','#ff9800','#9c27b0','#4caf50','#388e3c','#f44336']}]},options:{responsive:true}});
</script>
<?php endif; ?>

<?php if ($tab === 'stok'): ?>
<?php
$stokList = $db->query("SELECT p.kode_bunga, p.nama_bunga, p.stok, p.harga_beli, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.kategori_id = k.id WHERE p.is_active = 1 ORDER BY p.stok ASC")->fetchAll();
$stokMinimum = (int)($_settings['stok_minimum_alert'] ?? 5);
$lowStok = array_filter($stokList, fn($p) => $p['stok'] <= $stokMinimum);
$totalNilaiStok = array_sum(array_map(fn($p) => $p['stok'] * $p['harga_beli'], $stokList));

$riwayatBeli = $db->prepare("SELECT ps.no_pembelian, ps.tanggal, ps.total, s.nama as supplier_nama FROM pembelian_stok ps LEFT JOIN supplier s ON s.id = ps.supplier_id WHERE DATE(ps.tanggal) BETWEEN :dari AND :sampai ORDER BY ps.tanggal DESC LIMIT 20");
$riwayatBeli->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$riwayatBeli = $riwayatBeli->fetchAll();
?>

<div class="summary-grid" style="margin-bottom:20px;">
    <div class="card summary-card"><div><p class="summary-label">Total SKU Aktif</p><p class="summary-value"><?php echo count($stokList); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Stok Rendah</p><p class="summary-value" style="color:#c62828;"><?php echo count($lowStok); ?> produk</p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Nilai Inventaris</p><p class="summary-value">Rp <?php echo number_format($totalNilaiStok,0,',','.'); ?></p></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
    <div class="card"><h3>Stok Rendah (di bawah <?php echo $stokMinimum; ?>)</h3>
        <div class="table-wrapper"><table class="table"><thead><tr><th>Kode</th><th>Produk</th><th>Kategori</th><th>Stok</th></tr></thead><tbody>
            <?php foreach ($lowStok as $p): ?><tr><td><code><?php echo $p['kode_bunga']; ?></code></td><td><?php echo htmlspecialchars($p['nama_bunga']); ?></td><td><?php echo $p['nama_kategori']??'-'; ?></td><td><span class="badge badge-danger"><?php echo $p['stok']; ?></span></td></tr><?php endforeach; ?>
            <?php if (empty($lowStok)): ?><tr><td colspan="4" class="empty">Semua stok aman</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div class="card"><h3>Riwayat Pembelian Supplier</h3>
        <div class="table-wrapper"><table class="table"><thead><tr><th>No PO</th><th>Supplier</th><th>Total</th><th>Tanggal</th></tr></thead><tbody>
            <?php foreach ($riwayatBeli as $rb): ?><tr><td><code><?php echo $rb['no_pembelian']; ?></code></td><td><?php echo htmlspecialchars($rb['supplier_nama']); ?></td><td>Rp <?php echo number_format($rb['total'],0,',','.'); ?></td><td><?php echo date('d/m/Y',strtotime($rb['tanggal'])); ?></td></tr><?php endforeach; ?>
            <?php if (empty($riwayatBeli)): ?><tr><td colspan="4" class="empty">Tidak ada data</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>

<div class="card"><h3>Seluruh Stok Produk</h3>
    <div class="table-wrapper"><table class="table"><thead><tr><th>Kode</th><th>Produk</th><th>Kategori</th><th>Stok</th><th>Harga Beli</th><th>Nilai</th></tr></thead><tbody>
        <?php foreach ($stokList as $p): $nilai=$p['stok']*$p['harga_beli']; ?><tr><td><code><?php echo $p['kode_bunga']; ?></code></td><td><?php echo htmlspecialchars($p['nama_bunga']); ?></td><td><?php echo $p['nama_kategori']??'-'; ?></td><td><span class="badge <?php echo $p['stok']<=$stokMinimum?'badge-danger':'badge-ok'; ?>"><?php echo $p['stok']; ?></span></td><td>Rp <?php echo number_format($p['harga_beli'],0,',','.'); ?></td><td>Rp <?php echo number_format($nilai,0,',','.'); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php if ($tab === 'keuangan'): ?>
<?php
$stmt = $db->prepare("SELECT COALESCE(SUM(total),0) as revenue FROM transaksi WHERE DATE(tanggal) BETWEEN :dari AND :sampai AND status NOT IN ('batal','pending')");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$revenue = (float)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(dt.qty * p.harga_beli),0) FROM detail_transaksi dt JOIN transaksi t ON t.id = dt.transaksi_id JOIN produk p ON p.id = dt.produk_id WHERE DATE(t.tanggal) BETWEEN :dari AND :sampai AND t.status NOT IN ('batal','pending')");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$hppKeu = (float)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pembelian_stok WHERE DATE(tanggal) BETWEEN :dari AND :sampai");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$pengeluaran = (float)$stmt->fetchColumn();

$labaKotor = $revenue - $hppKeu;
$marginPersen = $revenue > 0 ? ($labaKotor/$revenue)*100 : 0;
$cashFlow = $revenue - $pengeluaran;

// Margin per produk
$stmt = $db->prepare("SELECT dt.nama_produk, SUM(dt.qty) as qty, SUM(dt.subtotal) as revenue, SUM(dt.qty * p.harga_beli) as modal FROM detail_transaksi dt JOIN transaksi t ON t.id = dt.transaksi_id JOIN produk p ON p.id = dt.produk_id WHERE DATE(t.tanggal) BETWEEN :dari AND :sampai AND t.status NOT IN ('batal','pending') GROUP BY dt.produk_id, dt.nama_produk ORDER BY (SUM(dt.subtotal)-SUM(dt.qty*p.harga_beli)) DESC LIMIT 15");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$marginProduk = $stmt->fetchAll();
?>

<div class="summary-grid" style="margin-bottom:20px;">
    <div class="card summary-card"><div><p class="summary-label">Revenue</p><p class="summary-value">Rp <?php echo number_format($revenue,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">HPP</p><p class="summary-value">Rp <?php echo number_format($hppKeu,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Laba Kotor</p><p class="summary-value" style="color:<?php echo $labaKotor>=0?'#2e7d32':'#c62828'; ?>">Rp <?php echo number_format($labaKotor,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Margin</p><p class="summary-value"><?php echo number_format($marginPersen,1); ?>%</p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Pengeluaran (PO)</p><p class="summary-value">Rp <?php echo number_format($pengeluaran,0,',','.'); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Cash Flow</p><p class="summary-value" style="color:<?php echo $cashFlow>=0?'#2e7d32':'#c62828'; ?>">Rp <?php echo number_format($cashFlow,0,',','.'); ?></p></div></div>
</div>

<div class="card"><h3>Margin Keuntungan per Produk</h3>
    <div class="table-wrapper"><table class="table"><thead><tr><th>Produk</th><th>Qty</th><th>Revenue</th><th>Modal</th><th>Profit</th><th>Margin</th></tr></thead><tbody>
        <?php foreach ($marginProduk as $mp): $profit=(float)$mp['revenue']-(float)$mp['modal']; $m=(float)$mp['revenue']>0?($profit/(float)$mp['revenue'])*100:0; ?>
        <tr><td><?php echo htmlspecialchars($mp['nama_produk']); ?></td><td><?php echo $mp['qty']; ?></td><td>Rp <?php echo number_format($mp['revenue'],0,',','.'); ?></td><td>Rp <?php echo number_format($mp['modal'],0,',','.'); ?></td><td style="color:<?php echo $profit>=0?'#2e7d32':'#c62828'; ?>">Rp <?php echo number_format($profit,0,',','.'); ?></td><td><?php echo number_format($m,1); ?>%</td></tr>
        <?php endforeach; ?>
        <?php if (empty($marginProduk)): ?><tr><td colspan="6" class="empty">Tidak ada data</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php if ($tab === 'pelanggan'): ?>
<?php
$stmt = $db->query("SELECT u.id, u.username, u.nama_lengkap, u.no_telp, COUNT(t.id) as total_order, COALESCE(SUM(CASE WHEN t.status!='batal' THEN t.total ELSE 0 END),0) as total_belanja, MAX(t.created_at) as last_order FROM users u LEFT JOIN transaksi t ON t.user_id = u.id WHERE u.role = 'kasir' GROUP BY u.id ORDER BY total_belanja DESC");
$pelangganList = $stmt->fetchAll();
?>

<div class="summary-grid" style="margin-bottom:20px;">
    <div class="card summary-card"><div><p class="summary-label">Total Pelanggan</p><p class="summary-value"><?php echo count($pelangganList); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Pelanggan Aktif</p><p class="summary-value"><?php echo count(array_filter($pelangganList, fn($p)=>$p['total_order']>0)); ?></p></div></div>
</div>

<div class="card"><h3>Pelanggan Teratas (by Total Belanja)</h3>
    <div class="table-wrapper"><table class="table"><thead><tr><th>Username</th><th>Nama</th><th>Telepon</th><th>Total Order</th><th>Total Belanja</th><th>Order Terakhir</th></tr></thead><tbody>
        <?php foreach ($pelangganList as $pl): ?>
        <tr>
            <td><code><?php echo htmlspecialchars($pl['username']); ?></code></td>
            <td><?php echo htmlspecialchars($pl['nama_lengkap']); ?></td>
            <td><?php echo $pl['no_telp']??'-'; ?></td>
            <td><?php echo $pl['total_order']; ?></td>
            <td><strong>Rp <?php echo number_format($pl['total_belanja'],0,',','.'); ?></strong></td>
            <td style="font-size:0.82rem;"><?php echo $pl['last_order'] ? date('d/m/Y',strtotime($pl['last_order'])) : '-'; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pelangganList)): ?><tr><td colspan="6" class="empty">Tidak ada data</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php if ($tab === 'supplier'): ?>
<?php
$stmt = $db->query("SELECT s.id, s.nama, s.kontak_person, COUNT(ps.id) as total_po, COALESCE(SUM(ps.total),0) as total_pembelian, MAX(ps.tanggal) as last_po FROM supplier s LEFT JOIN pembelian_stok ps ON ps.supplier_id = s.id GROUP BY s.id ORDER BY total_pembelian DESC");
$supplierList = $stmt->fetchAll();

// Detail pembelian per supplier
$stmt = $db->prepare("SELECT ps.no_pembelian, ps.tanggal, ps.total, s.nama as supplier_nama FROM pembelian_stok ps LEFT JOIN supplier s ON s.id = ps.supplier_id WHERE DATE(ps.tanggal) BETWEEN :dari AND :sampai ORDER BY ps.tanggal DESC");
$stmt->execute([':dari'=>$dateFrom,':sampai'=>$dateTo]);
$allPO = $stmt->fetchAll();
?>

<div class="summary-grid" style="margin-bottom:20px;">
    <div class="card summary-card"><div><p class="summary-label">Total Supplier</p><p class="summary-value"><?php echo count($supplierList); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">PO Periode Ini</p><p class="summary-value"><?php echo count($allPO); ?></p></div></div>
    <div class="card summary-card"><div><p class="summary-label">Total Pembelian Periode</p><p class="summary-value">Rp <?php echo number_format(array_sum(array_column($allPO,'total')),0,',','.'); ?></p></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
    <div class="card"><h3>Ranking Supplier (All Time)</h3>
        <div class="table-wrapper"><table class="table"><thead><tr><th>Supplier</th><th>Kontak</th><th>Total PO</th><th>Total Pembelian</th><th>PO Terakhir</th></tr></thead><tbody>
            <?php foreach ($supplierList as $sl): ?>
            <tr><td><strong><?php echo htmlspecialchars($sl['nama']); ?></strong></td><td><?php echo $sl['kontak_person']??'-'; ?></td><td><?php echo $sl['total_po']; ?></td><td>Rp <?php echo number_format($sl['total_pembelian'],0,',','.'); ?></td><td style="font-size:0.82rem;"><?php echo $sl['last_po']?date('d/m/Y',strtotime($sl['last_po'])):'-'; ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div>
    <div class="card"><h3>Riwayat PO (Periode Ini)</h3>
        <div class="table-wrapper"><table class="table"><thead><tr><th>No PO</th><th>Supplier</th><th>Total</th><th>Tanggal</th></tr></thead><tbody>
            <?php foreach ($allPO as $po): ?>
            <tr><td><code><?php echo $po['no_pembelian']; ?></code></td><td><?php echo htmlspecialchars($po['supplier_nama']); ?></td><td>Rp <?php echo number_format($po['total'],0,',','.'); ?></td><td style="font-size:0.82rem;"><?php echo date('d/m/Y',strtotime($po['tanggal'])); ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($allPO)): ?><tr><td colspan="4" class="empty">Tidak ada PO di periode ini</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'arsip'): ?>
<?php
// Handle actions
if (isset($_GET['lock'])) {
    $db->prepare("UPDATE laporan_artifacts SET is_locked = 1 WHERE id = :id")->execute([':id'=>(int)$_GET['lock']]);
    header('Location: ?page=laporan&tab=arsip&msg=locked'); exit;
}
if (isset($_GET['unlock'])) {
    $db->prepare("UPDATE laporan_artifacts SET is_locked = 0 WHERE id = :id")->execute([':id'=>(int)$_GET['unlock']]);
    header('Location: ?page=laporan&tab=arsip&msg=unlocked'); exit;
}
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $db->prepare("SELECT filename, is_locked FROM laporan_artifacts WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $artifact = $stmt->fetch();
    if ($artifact && !$artifact['is_locked']) {
        $path = __DIR__ . '/../../assets/reports/' . $artifact['filename'];
        if (file_exists($path)) unlink($path);
        $db->prepare("DELETE FROM laporan_artifacts WHERE id = :id")->execute([':id'=>$id]);
    }
    header('Location: ?page=laporan&tab=arsip&msg=deleted'); exit;
}

$artifacts = $db->query("SELECT la.*, u.username as created_by_name FROM laporan_artifacts la LEFT JOIN users u ON u.id = la.created_by ORDER BY la.created_at DESC")->fetchAll();
?>

<h1 class="page-title">Arsip PDF Laporan</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php echo ['locked'=>'File dikunci!','unlocked'=>'File dibuka kuncinya!','deleted'=>'File dihapus!'][$_GET['msg']] ?? 'Berhasil!'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Daftar File (<?php echo count($artifacts); ?>)</h3>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Nama</th><th>Tab</th><th>Periode</th><th>Ukuran</th><th>Dibuat</th><th>Oleh</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($artifacts as $a): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($a['original_name']); ?></strong><br><small style="color:var(--gray);"><?php echo $a['filename']; ?></small></td>
                    <td><?php echo ucfirst($a['tab']); ?></td>
                    <td><?php echo htmlspecialchars($a['periode_label']); ?></td>
                    <td><?php echo number_format($a['file_size']/1024, 1); ?> KB</td>
                    <td style="font-size:0.82rem;"><?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($a['created_by_name'] ?? '-'); ?></td>
                    <td>
                        <?php if ($a['is_locked']): ?>
                            <span class="badge badge-ok">Locked</span>
                        <?php else: ?>
                            <span class="badge" style="background:#f5f5f5;color:#666;">Open</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;display:flex;gap:5px;">
                        <a href="/assets/reports/<?php echo $a['filename']; ?>" target="_blank" class="btn btn-sm btn-edit">View</a>
                        <a href="/assets/reports/<?php echo $a['filename']; ?>" download class="btn btn-sm btn-secondary">Download</a>
                        <?php if ($a['is_locked']): ?>
                            <a href="?page=laporan&tab=arsip&unlock=<?php echo $a['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Unlock file ini?')">Unlock</a>
                        <?php else: ?>
                            <a href="?page=laporan&tab=arsip&lock=<?php echo $a['id']; ?>" class="btn btn-sm btn-edit" onclick="return confirm('Lock file ini?')">Lock</a>
                            <a href="?page=laporan&tab=arsip&hapus=<?php echo $a['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus file ini? Tidak bisa di-undo.')">Hapus</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($artifacts)): ?>
                <tr><td colspan="8" class="empty">Belum ada arsip. Generate PDF dari menu laporan lain.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function changePeriode(val) {
    document.getElementById('custom-dates').style.display = val === 'custom' ? 'flex' : 'none';
    if (val !== 'custom') { document.getElementById('laporan-form').submit(); }
}
function submitCustomDate() {
    var dari = document.querySelector('input[name="dari"]').value;
    var sampai = document.querySelector('input[name="sampai"]').value;
    if (dari && sampai) { document.getElementById('laporan-form').submit(); }
}
function printReport() {
    var params = new URLSearchParams(window.location.search);
    var url = '/admin/print-laporan.php?' + params.toString();
    window.open(url, '_blank');
}
function downloadPDF() {
    var params = new URLSearchParams(window.location.search);
    var url = '/admin/pdf-laporan.php?' + params.toString();
    window.open(url, '_blank');
}
</script>
