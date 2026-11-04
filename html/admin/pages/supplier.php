<?php
// === CRUD Supplier ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_nama'])) {
    $sid = $_POST['supplier_id'] ?? '';
    $nama = trim($_POST['supplier_nama']);
    $telp = trim($_POST['supplier_telp'] ?? '');
    $email = trim($_POST['supplier_email'] ?? '');
    $alamat = trim($_POST['supplier_alamat'] ?? '');
    $kontak = trim($_POST['supplier_kontak'] ?? '');

    if ($sid) {
        $db->prepare("UPDATE supplier SET nama=:n, no_telp=:t, email=:e, alamat=:a, kontak_person=:k WHERE id=:id")
           ->execute([':n'=>$nama,':t'=>$telp,':e'=>$email,':a'=>$alamat,':k'=>$kontak,':id'=>(int)$sid]);
    } else {
        $db->prepare("INSERT INTO supplier (nama, no_telp, email, alamat, kontak_person) VALUES (:n,:t,:e,:a,:k)")
           ->execute([':n'=>$nama,':t'=>$telp,':e'=>$email,':a'=>$alamat,':k'=>$kontak]);
    }
    header('Location: ?page=supplier&msg=saved');
    exit;
}

if (isset($_GET['delete_supplier'])) {
    $db->prepare("DELETE FROM supplier WHERE id = :id")->execute([':id'=>(int)$_GET['delete_supplier']]);
    header('Location: ?page=supplier&msg=deleted');
    exit;
}

// === Create PO (Pembelian Stok) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['po_supplier_id'])) {
    $suppId = (int)$_POST['po_supplier_id'];
    $catatan = trim($_POST['po_catatan'] ?? '');
    $items = json_decode($_POST['po_items'] ?? '[]', true);

    if (!empty($items) && $suppId) {
        $today = date('Ymd');
        $countStmt = $db->prepare("SELECT COUNT(*) FROM pembelian_stok WHERE DATE(tanggal) = CURDATE()");
        $countStmt->execute();
        $seq = $countStmt->fetchColumn() + 1;
        $noPO = "PO-{$today}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

        $total = 0;
        foreach ($items as $item) {
            $total += $item['harga_beli'] * $item['qty'];
        }

        $db->prepare("INSERT INTO pembelian_stok (no_pembelian, supplier_id, user_id, total, catatan) VALUES (:no,:sid,:uid,:total,:cat)")
           ->execute([':no'=>$noPO, ':sid'=>$suppId, ':uid'=>$_SESSION['user']['id'], ':total'=>$total, ':cat'=>$catatan]);
        $poId = $db->lastInsertId();

        $detailStmt = $db->prepare("INSERT INTO detail_pembelian_stok (pembelian_id, produk_id, qty, harga_beli, subtotal) VALUES (:pid,:prodid,:qty,:harga,:sub)");
        $stokStmt = $db->prepare("UPDATE produk SET stok = stok + :qty WHERE id = :id");

        foreach ($items as $item) {
            $sub = $item['harga_beli'] * $item['qty'];
            $detailStmt->execute([':pid'=>$poId, ':prodid'=>$item['produk_id'], ':qty'=>$item['qty'], ':harga'=>$item['harga_beli'], ':sub'=>$sub]);
            $stokStmt->execute([':qty'=>$item['qty'], ':id'=>$item['produk_id']]);
        }

        header('Location: ?page=supplier&msg=po_created&po=' . $noPO);
        exit;
    }
}

// Fetch data
$suppliers = $db->query("SELECT s.*, (SELECT COUNT(*) FROM pembelian_stok WHERE supplier_id = s.id) as total_po, (SELECT COALESCE(SUM(total),0) FROM pembelian_stok WHERE supplier_id = s.id) as total_pembelian FROM supplier s ORDER BY s.nama")->fetchAll();
$produkList = $db->query("SELECT id, kode_bunga, nama_bunga, harga_beli FROM produk WHERE is_active = 1 ORDER BY nama_bunga")->fetchAll();
$recentPO = $db->query("SELECT p.*, s.nama as supplier_nama FROM pembelian_stok p LEFT JOIN supplier s ON s.id = p.supplier_id ORDER BY p.created_at DESC LIMIT 10")->fetchAll();

// Edit supplier
$editSupplier = null;
if (isset($_GET['edit_supplier'])) {
    $stmt = $db->prepare("SELECT * FROM supplier WHERE id = :id");
    $stmt->execute([':id'=>(int)$_GET['edit_supplier']]);
    $editSupplier = $stmt->fetch();
}
?>

<h1 class="page-title">Supplier & Pembelian</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $poNo = $_GET['po'] ?? '';
        $msgs = ['saved'=>'Supplier berhasil disimpan!', 'deleted'=>'Supplier dihapus!', 'po_created'=>'PO '.$poNo.' berhasil dibuat! Stok diupdate.'];
        echo $msgs[$_GET['msg']] ?? 'Berhasil!';
        ?>
    </div>
<?php endif; ?>

<!-- Supplier List -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
        <h3 style="margin:0;">Daftar Supplier (<?php echo count($suppliers); ?>)</h3>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary" onclick="openPOModal()">+ Buat PO</button>
            <button class="btn btn-primary" onclick="openSupplierModal()">+ Tambah Supplier</button>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Nama</th><th>Kontak</th><th>Telepon</th><th>Total PO</th><th>Total Pembelian</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['nama']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['kontak_person'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($s['no_telp'] ?? '-'); ?></td>
                    <td><?php echo $s['total_po']; ?> PO</td>
                    <td>Rp <?php echo number_format($s['total_pembelian'], 0, ',', '.'); ?></td>
                    <td style="white-space:nowrap;display:flex;gap:5px;">
                        <a href="#" onclick="openEditSupplier(<?php echo $s['id']; ?>)" class="btn btn-sm btn-edit">Edit</a>
                        <a href="?page=supplier&delete_supplier=<?php echo $s['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus supplier ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                <tr><td colspan="6" class="empty">Belum ada supplier</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent PO -->
<div class="card">
    <h3>Riwayat Pembelian (PO Terakhir)</h3>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>No. PO</th><th>Supplier</th><th>Total</th><th>Tanggal</th></tr></thead>
            <tbody>
                <?php foreach ($recentPO as $po): ?>
                <tr>
                    <td><a href="#" onclick="openPODetail(<?php echo $po['id']; ?>)" style="color:var(--primary);text-decoration:none;"><code><?php echo $po['no_pembelian']; ?></code></a></td>
                    <td><?php echo htmlspecialchars($po['supplier_nama'] ?? '-'); ?></td>
                    <td>Rp <?php echo number_format($po['total'], 0, ',', '.'); ?></td>
                    <td style="font-size:0.82rem;"><?php echo date('d/m/Y H:i', strtotime($po['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentPO)): ?>
                <tr><td colspan="4" class="empty">Belum ada PO</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Supplier (Add/Edit) -->
<div class="modal-overlay" id="supplier-modal" onclick="if(event.target===this)closeSupplierModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="supplier-modal-title">Tambah Supplier</h3>
            <button class="modal-close" onclick="closeSupplierModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="supplier_id" id="sup-id" value="">
            <div class="form-grid">
                <div class="form-group"><label>Nama Supplier</label><input type="text" name="supplier_nama" id="sup-nama" required></div>
                <div class="form-group"><label>Kontak Person</label><input type="text" name="supplier_kontak" id="sup-kontak"></div>
                <div class="form-group"><label>Telepon</label><input type="tel" name="supplier_telp" id="sup-telp"></div>
                <div class="form-group"><label>Email</label><input type="email" name="supplier_email" id="sup-email"></div>
                <div class="form-group full-width"><label>Alamat</label><textarea name="supplier_alamat" id="sup-alamat" rows="2"></textarea></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="sup-submit-btn">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeSupplierModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal PO (Buat Pembelian) -->
<div class="modal-overlay" id="po-modal" onclick="if(event.target===this)closePOModal()">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <h3>Buat Purchase Order (PO)</h3>
            <button class="modal-close" onclick="closePOModal()">&times;</button>
        </div>
        <form method="POST" id="po-form">
            <input type="hidden" name="po_items" id="po-items-data" value="[]">
            <div class="form-grid">
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="po_supplier_id" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nama']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="po_catatan" placeholder="Opsional">
                </div>
            </div>

            <!-- Add Item -->
            <div style="margin:15px 0;padding:12px;background:#f9f9f9;border-radius:8px;">
                <label style="font-size:0.82rem;font-weight:600;display:block;margin-bottom:8px;">Tambah Item</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
                    <select id="po-produk" style="flex:2;padding:8px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
                        <option value="">Pilih Produk</option>
                        <?php foreach ($produkList as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-harga="<?php echo $p['harga_beli']; ?>" data-nama="<?php echo htmlspecialchars($p['nama_bunga']); ?>"><?php echo htmlspecialchars($p['nama_bunga']); ?> (Rp <?php echo number_format($p['harga_beli'],0,',','.'); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="po-qty" placeholder="Qty" min="1" value="1" style="width:70px;padding:8px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
                    <input type="number" id="po-harga" placeholder="Harga beli" min="0" style="width:110px;padding:8px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="addPOItem()">+ Tambah</button>
                </div>
            </div>

            <!-- PO Items List -->
            <div id="po-items-list" style="margin-bottom:15px;"></div>
            <div id="po-total" style="font-weight:700;font-size:1rem;margin-bottom:15px;"></div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="po-submit-btn" disabled>Simpan PO</button>
                <button type="button" class="btn btn-secondary" onclick="closePOModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal PO Detail -->
<div class="modal-overlay" id="po-detail-modal" onclick="if(event.target===this)closePODetail()">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <h3 id="po-detail-title">Detail PO</h3>
            <button class="modal-close" onclick="closePODetail()">&times;</button>
        </div>
        <div id="po-detail-content">Loading...</div>
        <div class="form-actions" style="margin-top:15px;">
            <button type="button" class="btn btn-primary" onclick="printPO()">Print</button>
            <button type="button" class="btn btn-secondary" onclick="closePODetail()">Tutup</button>
        </div>
    </div>
</div>

<!-- Hidden Print Template -->
<div id="po-print-area" style="display:none;"></div>

<script>
const supplierData = <?php echo json_encode($suppliers); ?>;
let poItems = [];

// === Supplier Modal ===
function openSupplierModal() {
    document.getElementById('supplier-modal-title').textContent = 'Tambah Supplier';
    document.getElementById('sup-id').value = '';
    document.getElementById('sup-nama').value = '';
    document.getElementById('sup-kontak').value = '';
    document.getElementById('sup-telp').value = '';
    document.getElementById('sup-email').value = '';
    document.getElementById('sup-alamat').value = '';
    document.getElementById('supplier-modal').classList.add('show');
}

function openEditSupplier(id) {
    const s = supplierData.find(item => item.id == id);
    if (!s) return;
    document.getElementById('supplier-modal-title').textContent = 'Edit: ' + s.nama;
    document.getElementById('sup-id').value = s.id;
    document.getElementById('sup-nama').value = s.nama;
    document.getElementById('sup-kontak').value = s.kontak_person || '';
    document.getElementById('sup-telp').value = s.no_telp || '';
    document.getElementById('sup-email').value = s.email || '';
    document.getElementById('sup-alamat').value = s.alamat || '';
    document.getElementById('supplier-modal').classList.add('show');
}

function closeSupplierModal() { document.getElementById('supplier-modal').classList.remove('show'); }

// === PO Modal ===
function openPOModal() {
    poItems = [];
    renderPOItems();
    document.getElementById('po-modal').classList.add('show');
}

function closePOModal() { document.getElementById('po-modal').classList.remove('show'); }

function addPOItem() {
    const sel = document.getElementById('po-produk');
    const qty = parseInt(document.getElementById('po-qty').value) || 0;
    const harga = parseInt(document.getElementById('po-harga').value) || 0;

    if (!sel.value || qty <= 0) { alert('Pilih produk dan qty'); return; }

    const opt = sel.options[sel.selectedIndex];
    const hargaFinal = harga > 0 ? harga : parseInt(opt.dataset.harga) || 0;

    poItems.push({
        produk_id: parseInt(sel.value),
        nama: opt.dataset.nama,
        qty: qty,
        harga_beli: hargaFinal
    });

    sel.value = '';
    document.getElementById('po-qty').value = '1';
    document.getElementById('po-harga').value = '';
    renderPOItems();
}

function removePOItem(idx) {
    poItems.splice(idx, 1);
    renderPOItems();
}

function renderPOItems() {
    const container = document.getElementById('po-items-list');
    const totalEl = document.getElementById('po-total');
    const submitBtn = document.getElementById('po-submit-btn');
    const dataInput = document.getElementById('po-items-data');

    if (poItems.length === 0) {
        container.innerHTML = '<p style="color:var(--gray);font-size:0.85rem;">Belum ada item. Tambahkan produk di atas.</p>';
        totalEl.textContent = '';
        submitBtn.disabled = true;
        dataInput.value = '[]';
        return;
    }

    let total = 0;
    container.innerHTML = '<table class="table"><thead><tr><th>Produk</th><th>Qty</th><th>Harga Beli</th><th>Subtotal</th><th></th></tr></thead><tbody>' +
        poItems.map((item, i) => {
            const sub = item.harga_beli * item.qty;
            total += sub;
            return `<tr><td>${item.nama}</td><td>${item.qty}</td><td>Rp ${Number(item.harga_beli).toLocaleString('id-ID')}</td><td>Rp ${Number(sub).toLocaleString('id-ID')}</td><td><button type="button" class="btn btn-sm btn-delete" onclick="removePOItem(${i})">x</button></td></tr>`;
        }).join('') +
        '</tbody></table>';

    totalEl.textContent = 'Total: Rp ' + Number(total).toLocaleString('id-ID');
    submitBtn.disabled = false;
    dataInput.value = JSON.stringify(poItems);
}

// Auto-fill harga beli when produk selected
document.getElementById('po-produk').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.dataset.harga) {
        document.getElementById('po-harga').value = opt.dataset.harga;
    }
});

// === PO Detail ===
function openPODetail(poId) {
    document.getElementById('po-detail-content').innerHTML = 'Loading...';
    document.getElementById('po-detail-modal').classList.add('show');

    fetch('../api/po-detail.php?id=' + poId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { document.getElementById('po-detail-content').innerHTML = 'Error loading data'; return; }
            const po = data.data;
            document.getElementById('po-detail-title').textContent = 'PO: ' + po.no_pembelian;

            let html = `
                <div style="margin-bottom:15px;font-size:0.88rem;">
                    <p><strong>No. PO:</strong> ${po.no_pembelian}</p>
                    <p><strong>Supplier:</strong> ${po.supplier_nama}</p>
                    <p><strong>Tanggal:</strong> ${po.tanggal}</p>
                    ${po.catatan ? '<p><strong>Catatan:</strong> ' + po.catatan + '</p>' : ''}
                </div>
                <table class="table">
                    <thead><tr><th>Produk</th><th>Qty</th><th>Harga Beli</th><th>Subtotal</th></tr></thead>
                    <tbody>`;

            po.items.forEach(item => {
                html += `<tr><td>${item.nama_produk}</td><td>${item.qty}</td><td>Rp ${Number(item.harga_beli).toLocaleString('id-ID')}</td><td>Rp ${Number(item.subtotal).toLocaleString('id-ID')}</td></tr>`;
            });

            html += `<tr><td colspan="3" style="text-align:right;"><strong>Total</strong></td><td><strong>Rp ${Number(po.total).toLocaleString('id-ID')}</strong></td></tr>`;
            html += '</tbody></table>';

            document.getElementById('po-detail-content').innerHTML = html;

            // Prepare print content
            document.getElementById('po-print-area').innerHTML = `
                <div style="font-family:monospace;padding:20px;max-width:600px;margin:0 auto;">
                    <h2 style="text-align:center;margin-bottom:5px;">PURCHASE ORDER</h2>
                    <p style="text-align:center;margin-bottom:20px;color:#666;">Toko Bunga Melati</p>
                    <hr>
                    <p><strong>No. PO:</strong> ${po.no_pembelian}</p>
                    <p><strong>Supplier:</strong> ${po.supplier_nama}</p>
                    <p><strong>Tanggal:</strong> ${po.tanggal}</p>
                    ${po.catatan ? '<p><strong>Catatan:</strong> ' + po.catatan + '</p>' : ''}
                    <hr>
                    <table style="width:100%;border-collapse:collapse;margin:10px 0;">
                        <thead><tr style="border-bottom:2px solid #000;"><th style="text-align:left;padding:5px;">Produk</th><th style="padding:5px;">Qty</th><th style="padding:5px;">Harga</th><th style="padding:5px;">Subtotal</th></tr></thead>
                        <tbody>
                        ${po.items.map(item => `<tr style="border-bottom:1px solid #ddd;"><td style="padding:5px;">${item.nama_produk}</td><td style="padding:5px;text-align:center;">${item.qty}</td><td style="padding:5px;text-align:right;">Rp ${Number(item.harga_beli).toLocaleString('id-ID')}</td><td style="padding:5px;text-align:right;">Rp ${Number(item.subtotal).toLocaleString('id-ID')}</td></tr>`).join('')}
                        </tbody>
                    </table>
                    <hr>
                    <p style="text-align:right;font-size:1.1em;"><strong>TOTAL: Rp ${Number(po.total).toLocaleString('id-ID')}</strong></p>
                    <br><br>
                    <div style="display:flex;justify-content:space-between;margin-top:40px;">
                        <div style="text-align:center;"><p>____________</p><p>Dibuat oleh</p></div>
                        <div style="text-align:center;"><p>____________</p><p>Disetujui oleh</p></div>
                    </div>
                </div>
            `;
        });
}

function closePODetail() { document.getElementById('po-detail-modal').classList.remove('show'); }

function printPO() {
    const content = document.getElementById('po-print-area').innerHTML;
    const win = window.open('', '_blank');
    win.document.write(`
        <html><head><title>Print PO</title>
        <style>body{margin:0;padding:20px;} @media print { body{padding:0;} }</style>
        </head><body>${content}
        <script>window.onload=function(){window.print();}<\/script>
        </body></html>
    `);
    win.document.close();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSupplierModal(); closePOModal(); closePODetail(); } });

<?php if ($editSupplier): ?>openEditSupplier(<?php echo $editSupplier['id']; ?>);<?php endif; ?>
</script>
