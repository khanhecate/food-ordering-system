<?php
/**
 * TEMPLATE PRINT LAPORAN
 * 
 * File ini di-load di window baru saat tombol Print diklik.
 * Edit file ini untuk mengubah tampilan print/PDF.
 * 
 * Data tersedia dari query berdasarkan ?tab= dan ?periode=
 * Variabel yang bisa dipakai di template:
 *   $tab, $periodeLabel, $dateFrom, $dateTo
 *   + data spesifik per tab (lihat di bawah)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Load settings
$_settings = [];
$rows = $db->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll();
foreach ($rows as $r) { $_settings[$r['setting_key']] = $r['setting_value']; }
$namaToko = $_settings['nama_toko'] ?? 'Toko Bunga';

// Params
$tab = $_GET['tab'] ?? 'overview';
$periode = $_GET['periode'] ?? 'bulan_ini';
$customFrom = $_GET['dari'] ?? '';
$customTo = $_GET['sampai'] ?? '';

switch ($periode) {
    case 'hari_ini': $dateFrom = date('Y-m-d'); $dateTo = date('Y-m-d'); $periodeLabel = 'Hari Ini ('.date('d M Y').')'; break;
    case 'minggu_ini': $dateFrom = date('Y-m-d', strtotime('monday this week')); $dateTo = date('Y-m-d'); $periodeLabel = 'Minggu Ini'; break;
    case 'bulan_ini': $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); $periodeLabel = date('F Y'); break;
    case 'bulan_lalu': $dateFrom = date('Y-m-01', strtotime('first day of last month')); $dateTo = date('Y-m-t', strtotime('last day of last month')); $periodeLabel = date('F Y', strtotime('last month')); break;
    case 'custom': $dateFrom = $customFrom ?: date('Y-m-01'); $dateTo = $customTo ?: date('Y-m-d'); $periodeLabel = date('d M Y', strtotime($dateFrom)).' - '.date('d M Y', strtotime($dateTo)); break;
    default: $dateFrom = date('Y-m-01'); $dateTo = date('Y-m-d'); $periodeLabel = date('F Y');
}

$tabTitles = ['overview'=>'Laporan Penjualan','stok'=>'Stok & Inventaris','keuangan'=>'Laporan Keuangan','pelanggan'=>'Laporan Pelanggan','supplier'=>'Laporan Supplier'];
$title = $tabTitles[$tab] ?? 'Laporan';

// ========== FETCH DATA PER TAB ==========
$data = [];

if ($tab === 'overview') {
    $stmt = $db->prepare("SELECT COUNT(*) as total_transaksi, COALESCE(SUM(total),0) as total_penjualan FROM transaksi WHERE DATE(tanggal) BETWEEN :d AND :s AND status NOT IN ('batal','pending')");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['penjualan'] = $stmt->fetch();

    $stmt = $db->prepare("SELECT COALESCE(SUM(dt.qty*p.harga_beli),0) FROM detail_transaksi dt JOIN transaksi t ON t.id=dt.transaksi_id JOIN produk p ON p.id=dt.produk_id WHERE DATE(t.tanggal) BETWEEN :d AND :s AND t.status NOT IN ('batal','pending')");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['hpp'] = (float)$stmt->fetchColumn();
    $data['profit'] = (float)$data['penjualan']['total_penjualan'] - $data['hpp'];
    $data['margin'] = (float)$data['penjualan']['total_penjualan'] > 0 ? ($data['profit']/(float)$data['penjualan']['total_penjualan'])*100 : 0;

    $stmt = $db->prepare("SELECT dt.nama_produk, SUM(dt.qty) as qty, SUM(dt.subtotal) as revenue FROM detail_transaksi dt JOIN transaksi t ON t.id=dt.transaksi_id WHERE DATE(t.tanggal) BETWEEN :d AND :s AND t.status NOT IN ('batal','pending') GROUP BY dt.nama_produk ORDER BY qty DESC LIMIT 10");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['top_products'] = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT DATE(tanggal) as tgl, COUNT(*) as trx, SUM(total) as omzet FROM transaksi WHERE DATE(tanggal) BETWEEN :d AND :s AND status NOT IN ('batal','pending') GROUP BY DATE(tanggal) ORDER BY tgl");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['daily'] = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT COUNT(*) as total_po, COALESCE(SUM(total),0) as total_pembelian FROM pembelian_stok WHERE DATE(tanggal) BETWEEN :d AND :s");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['pembelian'] = $stmt->fetch();

    $stmt = $db->prepare("SELECT status, COUNT(*) as jumlah FROM transaksi WHERE DATE(tanggal) BETWEEN :d AND :s GROUP BY status");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['status_breakdown'] = $stmt->fetchAll();
}

if ($tab === 'stok') {
    $data['stok'] = $db->query("SELECT p.kode_bunga, p.nama_bunga, p.stok, p.harga_beli, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.kategori_id=k.id WHERE p.is_active=1 ORDER BY p.stok ASC")->fetchAll();
    $data['nilai'] = array_sum(array_map(fn($p)=>$p['stok']*$p['harga_beli'], $data['stok']));
    $stokMin = (int)($_settings['stok_minimum_alert'] ?? 5);
    $data['stok_rendah'] = count(array_filter($data['stok'], fn($p) => $p['stok'] <= $stokMin));
    
    $stmt = $db->prepare("SELECT ps.no_pembelian, ps.tanggal, ps.total, s.nama as supplier_nama FROM pembelian_stok ps LEFT JOIN supplier s ON s.id=ps.supplier_id WHERE DATE(ps.tanggal) BETWEEN :d AND :s ORDER BY ps.tanggal DESC LIMIT 20");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['riwayat_po'] = $stmt->fetchAll();
}

if ($tab === 'keuangan') {
    $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM transaksi WHERE DATE(tanggal) BETWEEN :d AND :s AND status NOT IN ('batal','pending')");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['revenue'] = (float)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(SUM(dt.qty*p.harga_beli),0) FROM detail_transaksi dt JOIN transaksi t ON t.id=dt.transaksi_id JOIN produk p ON p.id=dt.produk_id WHERE DATE(t.tanggal) BETWEEN :d AND :s AND t.status NOT IN ('batal','pending')");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['hpp'] = (float)$stmt->fetchColumn();
    $data['laba'] = $data['revenue'] - $data['hpp'];
    $data['margin_persen'] = $data['revenue'] > 0 ? ($data['laba']/$data['revenue'])*100 : 0;

    $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pembelian_stok WHERE DATE(tanggal) BETWEEN :d AND :s");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['pengeluaran'] = (float)$stmt->fetchColumn();
    $data['cash_flow'] = $data['revenue'] - $data['pengeluaran'];

    $stmt = $db->prepare("SELECT dt.nama_produk, SUM(dt.qty) as qty, SUM(dt.subtotal) as rev, SUM(dt.qty*p.harga_beli) as modal FROM detail_transaksi dt JOIN transaksi t ON t.id=dt.transaksi_id JOIN produk p ON p.id=dt.produk_id WHERE DATE(t.tanggal) BETWEEN :d AND :s AND t.status NOT IN ('batal','pending') GROUP BY dt.produk_id,dt.nama_produk ORDER BY (SUM(dt.subtotal)-SUM(dt.qty*p.harga_beli)) DESC LIMIT 15");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['margin_produk'] = $stmt->fetchAll();
}

if ($tab === 'pelanggan') {
    $data['pelanggan'] = $db->query("SELECT u.username, u.nama_lengkap, COUNT(t.id) as orders, COALESCE(SUM(CASE WHEN t.status!='batal' THEN t.total ELSE 0 END),0) as belanja FROM users u LEFT JOIN transaksi t ON t.user_id=u.id WHERE u.role='kasir' GROUP BY u.id ORDER BY belanja DESC")->fetchAll();
}

if ($tab === 'supplier') {
    $data['supplier'] = $db->query("SELECT s.nama, COUNT(ps.id) as po, COALESCE(SUM(ps.total),0) as total FROM supplier s LEFT JOIN pembelian_stok ps ON ps.supplier_id=s.id GROUP BY s.id ORDER BY total DESC")->fetchAll();
    $stmt = $db->prepare("SELECT ps.no_pembelian, ps.tanggal, ps.total, s.nama as supplier_nama FROM pembelian_stok ps LEFT JOIN supplier s ON s.id=ps.supplier_id WHERE DATE(ps.tanggal) BETWEEN :d AND :s ORDER BY ps.tanggal DESC");
    $stmt->execute([':d'=>$dateFrom,':s'=>$dateTo]); $data['po_periode'] = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> - <?php echo $namaToko; ?></title>
    <!-- ============================
         EDIT STYLE DI BAWAH INI
         untuk ubah tampilan print
    ============================ -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 40px;
            color: #222;
            max-width: 800px;
            margin: 0 auto;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Header */
        .print-header {
            border-bottom: 2px solid #222;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .print-header h1 {
            font-size: 18px;
            font-weight: 700;
        }
        .print-header .store-name {
            font-size: 12px;
            color: #666;
        }
        .print-header .periode {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }

        /* Summary Row */
        .summary-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-item {
            border: 1px solid #ddd;
            padding: 10px 14px;
            border-radius: 4px;
            min-width: 140px;
        }
        .summary-item .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
        }
        .summary-item .value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 2px;
        }
        .green { color: #2e7d32; }
        .red { color: #c62828; }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 7px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f5f5f5;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
        }
        td { font-size: 12px; }

        /* Section Title */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        /* Footer */
        .print-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        @media print {
            body { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- ============================
     EDIT TEMPLATE DI BAWAH INI
     Ubah layout sesuai keinginan
============================ -->

<!-- Header -->
<div class="print-header">
    <h1><?php echo $title; ?></h1>
    <p class="store-name"><?php echo $namaToko; ?></p>
    <p class="periode">Periode: <?php echo $periodeLabel; ?></p>
</div>

<?php if ($tab === 'overview'): ?>
<!-- ===== PENJUALAN ===== -->
<div class="summary-row">
    <div class="summary-item"><div class="label">Total Penjualan</div><div class="value">Rp <?php echo number_format($data['penjualan']['total_penjualan'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">HPP</div><div class="value">Rp <?php echo number_format($data['hpp'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">Profit</div><div class="value <?php echo $data['profit']>=0?'green':'red'; ?>">Rp <?php echo number_format($data['profit'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">Margin</div><div class="value"><?php echo number_format($data['margin'],1); ?>%</div></div>
    <div class="summary-item"><div class="label">Transaksi</div><div class="value"><?php echo $data['penjualan']['total_transaksi']; ?></div></div>
    <div class="summary-item"><div class="label">Total PO (Restock)</div><div class="value">Rp <?php echo number_format($data['pembelian']['total_pembelian'],0,',','.'); ?></div></div>
</div>

<p class="section-title">Penjualan Harian</p>
<table>
    <thead><tr><th>Tanggal</th><th>Transaksi</th><th>Omzet</th></tr></thead>
    <tbody>
        <?php foreach ($data['daily'] as $d): ?>
        <tr><td><?php echo date('d M Y',strtotime($d['tgl'])); ?></td><td><?php echo $d['trx']; ?></td><td>Rp <?php echo number_format($d['omzet'],0,',','.'); ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($data['daily'])): ?><tr><td colspan="3" style="text-align:center;color:#999;">Tidak ada data</td></tr><?php endif; ?>
    </tbody>
</table>

<p class="section-title">Produk Terlaris</p>
<table>
    <thead><tr><th>Produk</th><th>Qty</th><th>Revenue</th></tr></thead>
    <tbody>
        <?php foreach ($data['top_products'] as $p): ?>
        <tr><td><?php echo $p['nama_produk']; ?></td><td><?php echo $p['qty']; ?></td><td>Rp <?php echo number_format($p['revenue'],0,',','.'); ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($data['top_products'])): ?><tr><td colspan="3" style="text-align:center;color:#999;">Tidak ada data</td></tr><?php endif; ?>
    </tbody>
</table>

<?php if (!empty($data['status_breakdown'])): ?>
<p class="section-title">Status Order</p>
<table>
    <thead><tr><th>Status</th><th>Jumlah</th></tr></thead>
    <tbody>
        <?php foreach ($data['status_breakdown'] as $sb): ?>
        <tr><td><?php echo ucfirst($sb['status']); ?></td><td><?php echo $sb['jumlah']; ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Chart (rendered as static image via Chart.js) -->
<p class="section-title">Grafik Penjualan</p>
<canvas id="printChart" width="700" height="250"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
new Chart(document.getElementById('printChart'),{type:'line',data:{labels:<?php echo json_encode(array_map(function($d){return date('d M',strtotime($d['tgl']));},$data['daily'])); ?>,datasets:[{label:'Omzet',data:<?php echo json_encode(array_map(function($d){return (float)$d['omzet'];},$data['daily'])); ?>,borderColor:'#e91e63',backgroundColor:'rgba(233,30,99,0.1)',fill:true,tension:0.3}]},options:{responsive:false,animation:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>
<?php endif; ?>

<?php if ($tab === 'stok'): ?>
<!-- ===== STOK ===== -->
<div class="summary-row">
    <div class="summary-item"><div class="label">Total SKU</div><div class="value"><?php echo count($data['stok']); ?></div></div>
    <div class="summary-item"><div class="label">Stok Rendah</div><div class="value red"><?php echo $data['stok_rendah']; ?> produk</div></div>
    <div class="summary-item"><div class="label">Nilai Inventaris</div><div class="value">Rp <?php echo number_format($data['nilai'],0,',','.'); ?></div></div>
</div>

<p class="section-title">Daftar Stok</p>
<table>
    <thead><tr><th>Kode</th><th>Produk</th><th>Kategori</th><th>Stok</th><th>Harga Beli</th><th>Nilai</th></tr></thead>
    <tbody>
        <?php foreach ($data['stok'] as $p): ?>
        <tr><td><?php echo $p['kode_bunga']; ?></td><td><?php echo $p['nama_bunga']; ?></td><td><?php echo $p['nama_kategori']??'-'; ?></td><td><?php echo $p['stok']; ?></td><td>Rp <?php echo number_format($p['harga_beli'],0,',','.'); ?></td><td>Rp <?php echo number_format($p['stok']*$p['harga_beli'],0,',','.'); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (!empty($data['riwayat_po'])): ?>
<p class="section-title">Riwayat Pembelian (Periode Ini)</p>
<table>
    <thead><tr><th>No PO</th><th>Supplier</th><th>Total</th><th>Tanggal</th></tr></thead>
    <tbody>
        <?php foreach ($data['riwayat_po'] as $po): ?>
        <tr><td><?php echo $po['no_pembelian']; ?></td><td><?php echo $po['supplier_nama']; ?></td><td>Rp <?php echo number_format($po['total'],0,',','.'); ?></td><td><?php echo date('d M Y',strtotime($po['tanggal'])); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php endif; ?>

<?php if ($tab === 'keuangan'): ?>
<!-- ===== KEUANGAN ===== -->
<div class="summary-row">
    <div class="summary-item"><div class="label">Revenue</div><div class="value">Rp <?php echo number_format($data['revenue'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">HPP</div><div class="value">Rp <?php echo number_format($data['hpp'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">Laba Kotor</div><div class="value <?php echo $data['laba']>=0?'green':'red'; ?>">Rp <?php echo number_format($data['laba'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">Margin</div><div class="value"><?php echo number_format($data['margin_persen'],1); ?>%</div></div>
    <div class="summary-item"><div class="label">Pengeluaran (PO)</div><div class="value">Rp <?php echo number_format($data['pengeluaran'],0,',','.'); ?></div></div>
    <div class="summary-item"><div class="label">Cash Flow</div><div class="value <?php echo $data['cash_flow']>=0?'green':'red'; ?>">Rp <?php echo number_format($data['cash_flow'],0,',','.'); ?></div></div>
</div>

<p class="section-title">Margin per Produk</p>
<table>
    <thead><tr><th>Produk</th><th>Qty</th><th>Revenue</th><th>Modal</th><th>Profit</th><th>Margin</th></tr></thead>
    <tbody>
        <?php foreach ($data['margin_produk'] as $mp): $profit=(float)$mp['rev']-(float)$mp['modal']; $m=(float)$mp['rev']>0?($profit/(float)$mp['rev'])*100:0; ?>
        <tr><td><?php echo $mp['nama_produk']; ?></td><td><?php echo $mp['qty']; ?></td><td>Rp <?php echo number_format($mp['rev'],0,',','.'); ?></td><td>Rp <?php echo number_format($mp['modal'],0,',','.'); ?></td><td class="<?php echo $profit>=0?'green':'red'; ?>">Rp <?php echo number_format($profit,0,',','.'); ?></td><td><?php echo number_format($m,1); ?>%</td></tr>
        <?php endforeach; ?>
        <?php if (empty($data['margin_produk'])): ?><tr><td colspan="6" style="text-align:center;color:#999;">Tidak ada data</td></tr><?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($tab === 'pelanggan'): ?>
<!-- ===== PELANGGAN ===== -->
<p class="section-title">Ranking Pelanggan</p>
<table>
    <thead><tr><th>Username</th><th>Nama</th><th>Total Order</th><th>Total Belanja</th></tr></thead>
    <tbody>
        <?php foreach ($data['pelanggan'] as $pl): ?>
        <tr><td><?php echo $pl['username']; ?></td><td><?php echo $pl['nama_lengkap']; ?></td><td><?php echo $pl['orders']; ?></td><td>Rp <?php echo number_format($pl['belanja'],0,',','.'); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($tab === 'supplier'): ?>
<!-- ===== SUPPLIER ===== -->
<p class="section-title">Ranking Supplier (All Time)</p>
<table>
    <thead><tr><th>Supplier</th><th>Total PO</th><th>Total Pembelian</th></tr></thead>
    <tbody>
        <?php foreach ($data['supplier'] as $sl): ?>
        <tr><td><?php echo $sl['nama']; ?></td><td><?php echo $sl['po']; ?></td><td>Rp <?php echo number_format($sl['total'],0,',','.'); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (!empty($data['po_periode'])): ?>
<p class="section-title">Riwayat PO (Periode Ini)</p>
<table>
    <thead><tr><th>No PO</th><th>Supplier</th><th>Total</th><th>Tanggal</th></tr></thead>
    <tbody>
        <?php foreach ($data['po_periode'] as $po): ?>
        <tr><td><?php echo $po['no_pembelian']; ?></td><td><?php echo $po['supplier_nama']; ?></td><td>Rp <?php echo number_format($po['total'],0,',','.'); ?></td><td><?php echo date('d M Y',strtotime($po['tanggal'])); ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php endif; ?>

<!-- Footer -->
<div class="print-footer">
    <p>Dicetak pada: <?php echo date('d M Y, H:i'); ?></p>
</div>

<script>window.onload = function() { setTimeout(function(){ window.print(); }, 500); }</script>
</body>
</html>
