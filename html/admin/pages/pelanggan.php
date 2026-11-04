<?php
// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("SELECT is_active FROM users WHERE id = :id AND role = 'kasir'");
    $stmt->execute([':id' => $id]);
    $current = (int)$stmt->fetchColumn();
    $newStatus = $current ? 0 : 1;
    $db->prepare("UPDATE users SET is_active = :status WHERE id = :id AND role = 'kasir'")->execute([':status' => $newStatus, ':id' => $id]);
    header('Location: ?page=pelanggan&msg=toggled');
    exit;
}

// Handle update user detail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $uid = (int)$_POST['edit_user_id'];
    $nama = trim($_POST['nama_lengkap']);
    $telp = trim($_POST['no_telp']);
    $alamat = trim($_POST['alamat']);
    
    $db->prepare("UPDATE users SET nama_lengkap = :nama, no_telp = :telp, alamat = :alamat WHERE id = :id AND role = 'kasir'")
       ->execute([':nama' => $nama, ':telp' => $telp, ':alamat' => $alamat, ':id' => $uid]);
    header('Location: ?page=pelanggan&msg=updated');
    exit;
}

// Handle reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_id'])) {
    $uid = (int)$_POST['reset_password_id'];
    $newPass = $_POST['new_password'] ?? '';
    if (strlen($newPass) >= 4) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = :pass WHERE id = :id AND role = 'kasir'")->execute([':pass' => $hash, ':id' => $uid]);
        header('Location: ?page=pelanggan&msg=password_reset');
        exit;
    }
}

// Fetch all customer users with transaction stats
$customers = $db->query("
    SELECT u.*, 
           COUNT(t.id) as total_transaksi,
           COALESCE(SUM(CASE WHEN t.status != 'batal' THEN t.total ELSE 0 END), 0) as total_belanja,
           MAX(t.created_at) as last_order
    FROM users u
    LEFT JOIN transaksi t ON t.user_id = u.id
    WHERE u.role = 'kasir'
    GROUP BY u.id
    ORDER BY total_belanja DESC
")->fetchAll();

// Edit modal data
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'kasir'");
    $stmt->execute([':id' => (int)$_GET['edit']]);
    $editUser = $stmt->fetch();
}
?>

<h1 class="page-title">Data Pelanggan</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $msgs = ['toggled' => 'Status akun berhasil diubah!', 'updated' => 'Data pelanggan diupdate!', 'password_reset' => 'Password berhasil direset!'];
        echo $msgs[$_GET['msg']] ?? 'Berhasil!';
        ?>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="summary-grid" style="margin-bottom:20px;">
    <div class="card summary-card">
        <div>
            <p class="summary-label">Total Pelanggan</p>
            <p class="summary-value"><?php echo count($customers); ?></p>
        </div>
    </div>
    <div class="card summary-card">
        <div>
            <p class="summary-label">Aktif</p>
            <p class="summary-value"><?php echo count(array_filter($customers, fn($c) => $c['is_active'])); ?></p>
        </div>
    </div>
</div>

<!-- Customer Table -->
<div class="card">
    <h3>Daftar Pelanggan</h3>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Telepon</th>
                    <th>Total Transaksi</th>
                    <th>Total Belanja</th>
                    <th>Order Terakhir</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                <tr style="<?php echo !$c['is_active'] ? 'opacity:0.5;' : ''; ?>">
                    <td><code><?php echo htmlspecialchars($c['username']); ?></code></td>
                    <td><?php echo htmlspecialchars($c['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($c['no_telp'] ?? '-'); ?></td>
                    <td><?php echo $c['total_transaksi']; ?> order</td>
                    <td><strong>Rp <?php echo number_format($c['total_belanja'], 0, ',', '.'); ?></strong></td>
                    <td style="font-size:0.82rem;"><?php echo $c['last_order'] ? date('d/m/Y', strtotime($c['last_order'])) : '-'; ?></td>
                    <td>
                        <span class="badge <?php echo $c['is_active'] ? 'badge-ok' : 'badge-danger'; ?>">
                            <?php echo $c['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;">
                        <a href="#" onclick="openEditCustomer(<?php echo $c['id']; ?>)" class="btn btn-sm btn-edit">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                <tr><td colspan="8" class="empty">Belum ada pelanggan</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title">Edit Pelanggan</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <!-- Edit Detail -->
        <form method="POST" style="margin-bottom:20px;">
            <input type="hidden" name="edit_user_id" id="edit-uid" value="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="edit-username" disabled>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit-nama" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="tel" name="no_telp" id="edit-telp" placeholder="08xxx">
                </div>
                <div class="form-group full-width">
                    <label>Alamat</label>
                    <textarea name="alamat" id="edit-alamat" rows="3" placeholder="Alamat lengkap"></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>

        <hr style="border:none;border-top:1px solid var(--gray-light);margin:20px 0;">

        <!-- Disable/Enable Account -->
        <h3 style="font-size:0.95rem;margin-bottom:12px;">Status Akun</h3>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;">
            <span id="edit-status-text" style="font-size:0.88rem;"></span>
            <a href="#" id="edit-toggle-link" class="btn btn-sm btn-delete" onclick="return confirm('Ubah status akun ini?')">Disable</a>
        </div>

        <hr style="border:none;border-top:1px solid var(--gray-light);margin:20px 0;">

        <!-- Reset Password -->
        <h3 style="font-size:0.95rem;margin-bottom:12px;">Reset Password</h3>
        <form method="POST" style="display:flex;gap:8px;align-items:flex-end;">
            <input type="hidden" name="reset_password_id" id="reset-uid" value="">
            <div class="form-group" style="flex:1;margin:0;">
                <label>Password Baru</label>
                <input type="text" name="new_password" required minlength="4" placeholder="Min. 4 karakter">
            </div>
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Reset password user ini?')">Reset</button>
        </form>
    </div>
</div>

<script>
const customerData = <?php echo json_encode($customers); ?>;

function openEditCustomer(id) {
    const c = customerData.find(item => item.id == id);
    if (!c) return;
    document.getElementById('modal-title').textContent = 'Edit: ' + c.username;
    document.getElementById('edit-uid').value = c.id;
    document.getElementById('reset-uid').value = c.id;
    document.getElementById('edit-username').value = c.username;
    document.getElementById('edit-nama').value = c.nama_lengkap;
    document.getElementById('edit-telp').value = c.no_telp || '';
    document.getElementById('edit-alamat').value = c.alamat || '';
    
    // Toggle status
    const isActive = c.is_active == 1;
    document.getElementById('edit-status-text').textContent = isActive ? 'Akun aktif' : 'Akun nonaktif';
    const toggleLink = document.getElementById('edit-toggle-link');
    toggleLink.textContent = isActive ? 'Disable' : 'Enable';
    toggleLink.href = '?page=pelanggan&toggle=' + c.id;
    toggleLink.className = 'btn btn-sm ' + (isActive ? 'btn-delete' : 'btn-primary');
    
    document.getElementById('modal-overlay').classList.add('show');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('show');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

<?php if ($editUser): ?>
openEditCustomer(<?php echo $editUser['id']; ?>);
<?php endif; ?>
</script>
