<?php
// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT foto, kode_bunga FROM produk WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        if ($row['foto']) {
            $oldPath = __DIR__ . '/../../assets/produk/' . $row['foto'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        // Rename kode to free up the UNIQUE constraint
        $deletedKode = $row['kode_bunga'] . '_DEL_' . time();
        $stmt = $db->prepare("UPDATE produk SET is_active = 0, foto = NULL, kode_bunga = :kode WHERE id = :id");
        $stmt->execute([':kode' => $deletedKode, ':id' => $id]);
    }
    header('Location: ?page=produk&msg=deleted');
    exit;
}

// Handle BULK DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));
    if ($ids) {
        $sel = $db->prepare("SELECT id, foto, kode_bunga FROM produk WHERE id = :id AND is_active = 1");
        $upd = $db->prepare("UPDATE produk SET is_active = 0, foto = NULL, kode_bunga = :kode WHERE id = :id");
        foreach ($ids as $id) {
            $sel->execute([':id' => $id]);
            $row = $sel->fetch();
            if (!$row) continue;
            if ($row['foto']) {
                $oldPath = __DIR__ . '/../../assets/produk/' . $row['foto'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            // Rename kode to free up the UNIQUE constraint (uniqid avoids clash on same-second deletes)
            $deletedKode = $row['kode_bunga'] . '_DEL_' . time() . '_' . uniqid();
            $upd->execute([':kode' => $deletedKode, ':id' => $id]);
        }
    }
    header('Location: ?page=produk&msg=bulk_deleted&n=' . count($ids));
    exit;
}

// Handle POST (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_bunga'])) {
    $id = $_POST['id'] ?? null;
    $kode_bunga = trim($_POST['kode_bunga']);
    $nama_bunga = trim($_POST['nama_bunga']);
    $kategori_id = (int)$_POST['kategori_id'];
    $harga_beli = (float)$_POST['harga_beli'];
    $harga_jual = (float)$_POST['harga_jual'];
    $stok = (int)$_POST['stok'];
    $deskripsi = trim($_POST['deskripsi']);
    
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($_FILES['foto']['type'], $allowed)) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'produk_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../../assets/produk/';
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename)) {
                $foto = $filename;
            }
        }
    }
    
    if ($id) {
        if ($foto) {
            $oldStmt = $db->prepare("SELECT foto FROM produk WHERE id = :id");
            $oldStmt->execute([':id' => (int)$id]);
            $oldFoto = $oldStmt->fetchColumn();
            if ($oldFoto) {
                $oldPath = __DIR__ . '/../../assets/produk/' . $oldFoto;
                if (file_exists($oldPath)) unlink($oldPath);
            }
        }
        $sql = "UPDATE produk SET kode_bunga = :kode, nama_bunga = :nama, kategori_id = :kat, harga_beli = :beli, harga_jual = :jual, stok = :stok, deskripsi = :desk";
        $params = [':kode' => $kode_bunga, ':nama' => $nama_bunga, ':kat' => $kategori_id, ':beli' => $harga_beli, ':jual' => $harga_jual, ':stok' => $stok, ':desk' => $deskripsi, ':id' => (int)$id];
        if ($foto) { $sql .= ", foto = :foto"; $params[':foto'] = $foto; }
        $sql .= " WHERE id = :id";
        $db->prepare($sql)->execute($params);
        header('Location: ?page=produk&msg=updated');
    } else {
        $sql = "INSERT INTO produk (kode_bunga, nama_bunga, kategori_id, harga_beli, harga_jual, stok, foto, deskripsi) VALUES (:kode, :nama, :kat, :beli, :jual, :stok, :foto, :desk)";
        $db->prepare($sql)->execute([':kode' => $kode_bunga, ':nama' => $nama_bunga, ':kat' => $kategori_id, ':beli' => $harga_beli, ':jual' => $harga_jual, ':stok' => $stok, ':foto' => $foto, ':desk' => $deskripsi]);
        header('Location: ?page=produk&msg=created');
    }
    exit;
}

// Handle Kategori CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_kategori'])) {
    $katId = $_POST['kat_id'] ?? '';
    $katNama = trim($_POST['nama_kategori']);
    if ($katNama) {
        if ($katId) {
            $db->prepare("UPDATE kategori_produk SET nama_kategori = :nama WHERE id = :id")->execute([':nama' => $katNama, ':id' => (int)$katId]);
        } else {
            $db->prepare("INSERT INTO kategori_produk (nama_kategori) VALUES (:nama)")->execute([':nama' => $katNama]);
        }
    }
    header('Location: ?page=produk&kat_msg=ok');
    exit;
}
if (isset($_GET['delete_kat'])) {
    $katId = (int)$_GET['delete_kat'];
    $check = $db->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id = :id AND is_active = 1");
    $check->execute([':id' => $katId]);
    if ($check->fetchColumn() == 0) {
        $db->prepare("DELETE FROM kategori_produk WHERE id = :id")->execute([':id' => $katId]);
    }
    header('Location: ?page=produk&kat_msg=ok');
    exit;
}

// Fetch data
$produkList = $db->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.kategori_id = k.id WHERE p.is_active = 1 ORDER BY p.id DESC")->fetchAll();
$kategoriList = $db->query("SELECT * FROM kategori_produk ORDER BY nama_kategori")->fetchAll();

$editProduk = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM produk WHERE id = :id AND is_active = 1");
    $stmt->execute([':id' => (int)$_GET['edit']]);
    $editProduk = $stmt->fetch();
}
?>

<h1 class="page-title">Data Produk</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['msg'] === 'bulk_deleted') {
            echo ((int)($_GET['n'] ?? 0)) . ' produk dihapus!';
        } else {
            echo ['created' => 'Produk ditambahkan!', 'updated' => 'Produk diupdate!', 'deleted' => 'Produk dihapus!'][$_GET['msg']] ?? 'Berhasil!';
        }
        ?>
    </div>
<?php endif; ?>

<!-- Tabel Produk -->
<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
        <h3 style="margin:0;">Daftar Produk (<?php echo count($produkList); ?> item)</h3>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-secondary" onclick="openKategoriModal()">Kategori</button>
            <button class="btn btn-primary" onclick="openModal()">+ Tambah Produk</button>
        </div>
    </div>

    <!-- Bulk action toolbar (muncul saat ada yang dipilih) -->
    <div id="bulk-toolbar" style="display:none;align-items:center;gap:12px;margin-bottom:12px;padding:10px 14px;background:var(--primary-light);border-radius:8px;">
        <span id="bulk-count" style="font-size:0.88rem;font-weight:600;">0 dipilih</span>
        <button type="submit" form="bulk-form" class="btn btn-sm btn-delete" onclick="return confirmBulkDelete()">🗑️ Hapus Terpilih</button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">Batal</button>
    </div>

    <form method="POST" id="bulk-form">
        <input type="hidden" name="action" value="bulk_delete">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center;"><input type="checkbox" id="check-all" onclick="toggleAll(this)" title="Pilih semua"></th>
                    <th>Foto</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga Beli</th><th>Harga Jual</th><th>Stok</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produkList as $p): ?>
                <tr>
                    <td style="text-align:center;"><input type="checkbox" class="row-check" name="ids[]" value="<?php echo $p['id']; ?>" onclick="updateBulkToolbar()"></td>
                    <td><?php echo $p['foto'] ? '<img src="../assets/produk/'.$p['foto'].'" class="thumb">' : '<span class="no-foto"></span>'; ?></td>
                    <td><a href="#" onclick="openEditModal(<?php echo $p['id']; ?>)" style="color:var(--primary);text-decoration:none;"><code><?php echo htmlspecialchars($p['kode_bunga']); ?></code></a></td>
                    <td><?php echo htmlspecialchars($p['nama_bunga']); ?></td>
                    <td><?php echo htmlspecialchars($p['nama_kategori'] ?? '-'); ?></td>
                    <td>Rp <?php echo number_format($p['harga_beli'], 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($p['harga_jual'], 0, ',', '.'); ?></td>
                    <td><span class="badge <?php echo $p['stok'] <= 5 ? 'badge-danger' : 'badge-ok'; ?>"><?php echo $p['stok']; ?></span></td>
                    <td style="white-space:nowrap;display:flex;gap:5px;">
                        <a href="#" onclick="openEditModal(<?php echo $p['id']; ?>)" class="btn btn-sm btn-edit">✏️</a>
                        <a href="?page=produk&delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus produk ini?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($produkList)): ?>
                <tr><td colspan="9" class="empty">Belum ada produk</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </form>
</section>

<!-- Modal Produk -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title">Tambah Produk Baru</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="form-id" value="">
            <div class="form-grid">
                <div class="form-group"><label>Kode Produk</label><input type="text" name="kode_bunga" id="form-kode" required placeholder="PRD-013"></div>
                <div class="form-group"><label>Nama Produk</label><input type="text" name="nama_bunga" id="form-nama" required placeholder="Nama produk"></div>
                <div class="form-group"><label>Kategori</label>
                    <select name="kategori_id" id="form-kategori" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['nama_kategori']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Harga Beli (Rp)</label><input type="number" name="harga_beli" id="form-beli" required min="0" step="100"></div>
                <div class="form-group"><label>Harga Jual (Rp)</label><input type="number" name="harga_jual" id="form-jual" required min="0" step="100"></div>
                <div class="form-group"><label>Stok</label><input type="number" name="stok" id="form-stok" required min="0" value="0"></div>
                <div class="form-group full-width"><label>Deskripsi</label><textarea name="deskripsi" id="form-desk" rows="3" placeholder="Deskripsi produk..."></textarea></div>
                <div class="form-group full-width">
                    <label>Foto Produk</label>
                    <div id="current-foto-preview" class="current-foto" style="display:none;"><img id="foto-preview-img" src="" width="100"><small>Upload baru untuk mengganti.</small></div>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="form-submit-btn">+ Tambah Produk</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Kategori -->
<div class="modal-overlay" id="kategori-overlay" onclick="if(event.target===this)closeKategoriModal()">
    <div class="modal">
        <div class="modal-header"><h3>Kelola Kategori</h3><button class="modal-close" onclick="closeKategoriModal()">&times;</button></div>
        <form method="POST" style="margin-bottom:16px;">
            <input type="hidden" name="kat_id" id="kat-form-id" value="">
            <div style="display:flex;gap:8px;">
                <input type="text" name="nama_kategori" id="kat-form-nama" required placeholder="Nama kategori..." style="flex:1;padding:9px 12px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.9rem;outline:none;">
                <button type="submit" class="btn btn-primary" id="kat-submit-btn">+ Tambah</button>
                <button type="button" class="btn btn-secondary" id="kat-cancel-btn" style="display:none;" onclick="resetKatForm()">Batal</button>
            </div>
        </form>
        <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>ID</th><th>Nama Kategori</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($kategoriList as $kat): ?>
                    <tr>
                        <td><?php echo $kat['id']; ?></td>
                        <td><?php echo htmlspecialchars($kat['nama_kategori']); ?></td>
                        <td style="white-space:nowrap;display:flex;gap:5px;">
                            <button class="btn btn-sm btn-edit" onclick="editKat(<?php echo $kat['id']; ?>,'<?php echo addslashes($kat['nama_kategori']); ?>')">✏️</button>
                            <a href="?page=produk&delete_kat=<?php echo $kat['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus kategori?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const produkData = <?php echo json_encode($produkList); ?>;

// ===== Bulk delete =====
function getChecked() { return Array.from(document.querySelectorAll('.row-check:checked')); }

function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
    updateBulkToolbar();
}

function updateBulkToolbar() {
    const checks = document.querySelectorAll('.row-check');
    const selected = getChecked().length;
    const toolbar = document.getElementById('bulk-toolbar');
    toolbar.style.display = selected > 0 ? 'flex' : 'none';
    document.getElementById('bulk-count').textContent = selected + ' dipilih';
    const master = document.getElementById('check-all');
    master.checked = selected > 0 && selected === checks.length;
    master.indeterminate = selected > 0 && selected < checks.length;
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    document.getElementById('check-all').checked = false;
    document.getElementById('check-all').indeterminate = false;
    updateBulkToolbar();
}

function confirmBulkDelete() {
    const n = getChecked().length;
    if (n === 0) return false;
    return confirm('Hapus ' + n + ' produk terpilih? Tindakan ini tidak bisa dibatalkan.');
}

function openModal() {
    document.getElementById('modal-title').textContent = 'Tambah Produk Baru';
    document.getElementById('form-submit-btn').textContent = '+ Tambah Produk';
    document.getElementById('form-id').value = '';
    document.getElementById('form-kode').value = '';
    document.getElementById('form-nama').value = '';
    document.getElementById('form-kategori').value = '';
    document.getElementById('form-beli').value = '';
    document.getElementById('form-jual').value = '';
    document.getElementById('form-stok').value = '0';
    document.getElementById('form-desk').value = '';
    document.getElementById('current-foto-preview').style.display = 'none';
    document.getElementById('modal-overlay').classList.add('show');
}

function openEditModal(id) {
    const p = produkData.find(item => item.id == id);
    if (!p) return;
    document.getElementById('modal-title').textContent = 'Edit: ' + p.nama_bunga;
    document.getElementById('form-submit-btn').textContent = 'Update Produk';
    document.getElementById('form-id').value = p.id;
    document.getElementById('form-kode').value = p.kode_bunga;
    document.getElementById('form-nama').value = p.nama_bunga;
    document.getElementById('form-kategori').value = p.kategori_id;
    document.getElementById('form-beli').value = p.harga_beli;
    document.getElementById('form-jual').value = p.harga_jual;
    document.getElementById('form-stok').value = p.stok;
    document.getElementById('form-desk').value = p.deskripsi || '';
    if (p.foto) { document.getElementById('foto-preview-img').src = '../assets/produk/' + p.foto; document.getElementById('current-foto-preview').style.display = 'flex'; }
    else { document.getElementById('current-foto-preview').style.display = 'none'; }
    document.getElementById('modal-overlay').classList.add('show');
}

function closeModal() { document.getElementById('modal-overlay').classList.remove('show'); }
function openKategoriModal() { document.getElementById('kategori-overlay').classList.add('show'); }
function closeKategoriModal() { document.getElementById('kategori-overlay').classList.remove('show'); resetKatForm(); }

function editKat(id, nama) {
    document.getElementById('kat-form-id').value = id;
    document.getElementById('kat-form-nama').value = nama;
    document.getElementById('kat-submit-btn').textContent = 'Update';
    document.getElementById('kat-cancel-btn').style.display = 'inline-block';
}

function resetKatForm() {
    document.getElementById('kat-form-id').value = '';
    document.getElementById('kat-form-nama').value = '';
    document.getElementById('kat-submit-btn').textContent = '+ Tambah';
    document.getElementById('kat-cancel-btn').style.display = 'none';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeKategoriModal(); } });

<?php if ($editProduk): ?>openEditModal(<?php echo $editProduk['id']; ?>);<?php endif; ?>
<?php if (isset($_GET['kat_msg'])): ?>openKategoriModal();<?php endif; ?>
</script>
