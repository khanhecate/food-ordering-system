<?php
$user = $_SESSION['user'];
$msg = '';
$error = '';

// Fetch current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user['id']]);
$userData = $stmt->fetch();

// Handle foto upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($_FILES['foto_profil']['type'], $allowed)) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../../assets/users/' . $filename;
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $dest)) {
            // Delete old photo
            if ($userData['foto_profil']) {
                $oldPath = __DIR__ . '/../../assets/users/' . $userData['foto_profil'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $db->prepare("UPDATE users SET foto_profil = :foto WHERE id = :id")->execute([':foto' => $filename, ':id' => $user['id']]);
            $msg = 'Foto profil berhasil diupdate!';
            $userData['foto_profil'] = $filename;
        }
    } else {
        $error = 'Format foto harus JPG, PNG, atau WEBP!';
    }
}

// Handle POST update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_lengkap'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $nama_penerima = trim($_POST['nama_penerima_default'] ?? '');
    
    if (empty($nama_lengkap)) {
        $error = 'Nama lengkap wajib diisi!';
    } else {
        $db->prepare("UPDATE users SET nama_lengkap = :nama, no_telp = :telp, alamat = :alamat, nama_penerima_default = :penerima WHERE id = :id")
           ->execute([':nama' => $nama_lengkap, ':telp' => $no_telp, ':alamat' => $alamat, ':penerima' => $nama_penerima, ':id' => $user['id']]);
        $_SESSION['user']['nama'] = $nama_lengkap;
        $msg = 'Profil berhasil diupdate!';
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        $userData = $stmt->fetch();
    }
}
?>

<div class="profil-page">
    <h1>Profil Saya</h1>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

    <div class="profil-card">
        <!-- Foto Profil -->
        <div style="text-align:center;margin-bottom:20px;">
            <img src="<?php echo $userData['foto_profil'] ? '/assets/users/' . $userData['foto_profil'] : '/assets/default-avatar.png'; ?>" alt="Avatar" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--gray-light);">
            <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()" style="font-size:0.8rem;">
            </form>
        </div>

        <h3>Data Akun</h3>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                <small>Username tidak bisa diubah</small>
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($userData['nama_lengkap']); ?>" required>
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="tel" name="no_telp" value="<?php echo htmlspecialchars($userData['no_telp'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
            </div>

            <hr class="section-divider">
            <h3>Default Pengiriman</h3>
            <small style="display:block;margin-bottom:14px;color:var(--gray);">Data ini akan otomatis terisi saat checkout.</small>

            <div class="form-group">
                <label>Nama Penerima (default)</label>
                <input type="text" name="nama_penerima_default" value="<?php echo htmlspecialchars($userData['nama_penerima_default'] ?? ''); ?>" placeholder="Nama penerima paket">
                <small>Kosongkan jika sama dengan nama lengkap</small>
            </div>
            <div class="form-group">
                <label>Alamat Pengiriman (default)</label>
                <textarea name="alamat" rows="3" placeholder="Alamat lengkap untuk pengiriman"><?php echo htmlspecialchars($userData['alamat'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
        </form>
    </div>
</div>

<style>
.profil-page { padding: 30px 20px; max-width: 600px; margin: 0 auto; }
.profil-page h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 20px; }
.profil-card { background: var(--white); border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow); }
.profil-card h3 { font-size: 1rem; margin-bottom: 18px; color: var(--dark); }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 5px; }
.form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 1.5px solid var(--gray-light); border-radius: 8px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; font-family: inherit; }
.form-group input:focus, .form-group textarea:focus { border-color: var(--primary); }
.form-group small { display: block; margin-top: 4px; font-size: 0.75rem; color: var(--gray); }
.btn-save { padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 700; cursor: pointer; }
.btn-save:hover { background: var(--primary-dark); }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; }
.alert-success { background: #e8f5e9; color: #2e7d32; }
.alert-error { background: #ffebee; color: #c62828; }
.section-divider { border: none; border-top: 1px solid var(--gray-light); margin: 20px 0; }
</style>
