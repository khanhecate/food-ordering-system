<?php
$msg = '';
$error = '';

// Fetch admin data
$adminData = $db->prepare("SELECT * FROM users WHERE id = :id")->execute([':id' => $_SESSION['user']['id']]);
$adminData = $db->prepare("SELECT * FROM users WHERE id = :id");
$adminData->execute([':id' => $_SESSION['user']['id']]);
$adminData = $adminData->fetch();

// Handle foto upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($_FILES['foto_profil']['type'], $allowed)) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $_SESSION['user']['id'] . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../../assets/users/' . $filename;
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $dest)) {
            if ($adminData['foto_profil']) {
                $oldPath = __DIR__ . '/../../assets/users/' . $adminData['foto_profil'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $db->prepare("UPDATE users SET foto_profil = :foto WHERE id = :id")->execute([':foto' => $filename, ':id' => $_SESSION['user']['id']]);
            $msg = 'Foto profil berhasil diupdate!';
            $adminData['foto_profil'] = $filename;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user']['id']]);
    $hash = $stmt->fetchColumn();
    
    if (!password_verify($current, $hash)) { $error = 'Password lama salah!'; }
    elseif (strlen($new) < 4) { $error = 'Password baru minimal 4 karakter!'; }
    elseif ($new !== $confirm) { $error = 'Konfirmasi password tidak cocok!'; }
    else {
        $db->prepare("UPDATE users SET password = :pass WHERE id = :id")->execute([':pass' => password_hash($new, PASSWORD_DEFAULT), ':id' => $_SESSION['user']['id']]);
        $msg = 'Password berhasil diganti!';
    }
}
?>

<h1 class="page-title">Profil Admin</h1>

<?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert" style="background:#ffebee;color:#c62828;border:1px solid #ffcdd2;"><?php echo $error; ?></div><?php endif; ?>

<!-- Foto Profil -->
<div class="card" style="max-width:450px;margin-bottom:20px;">
    <h3>Foto Profil</h3>
    <div style="display:flex;align-items:center;gap:15px;">
        <img src="<?php echo $adminData['foto_profil'] ? '/assets/users/' . $adminData['foto_profil'] : '/assets/default-avatar.png'; ?>" alt="Avatar" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid var(--gray-light);">
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()" style="font-size:0.82rem;">
        </form>
    </div>
</div>

<!-- Ganti Password -->
<div class="card" style="max-width:450px;">
    <form method="POST">
        <div class="form-group" style="margin-bottom:14px;"><label>Password Lama</label><input type="password" name="current_password" required></div>
        <div class="form-group" style="margin-bottom:14px;"><label>Password Baru</label><input type="password" name="new_password" required minlength="4"></div>
        <div class="form-group" style="margin-bottom:18px;"><label>Konfirmasi Password Baru</label><input type="password" name="confirm_password" required minlength="4"></div>
        <button type="submit" class="btn btn-primary">Simpan Password</button>
    </form>
</div>
