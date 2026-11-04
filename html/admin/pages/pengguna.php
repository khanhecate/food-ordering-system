<?php
// === Handle Pengaturan Toko Update ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_settings') {
        $fields = ['nama_toko', 'alamat_toko', 'no_telp_toko', 'pajak_persen', 'stok_minimum_alert', 'tema_warna', 'mata_uang', 'footer_struk'];
        foreach ($fields as $key) {
            $val = trim($_POST[$key] ?? '');
            $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2")
               ->execute([':k' => $key, ':v' => $val, ':v2' => $val]);
        }
        header('Location: ?page=pengguna&msg=settings_saved');
        exit;
    }

    if ($action === 'upload_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
            if (in_array($_FILES['logo']['type'], $allowed)) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_toko.' . $ext;
                $dest = __DIR__ . '/../../assets/' . $filename;
                move_uploaded_file($_FILES['logo']['tmp_name'], $dest);
                $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('logo_toko', :v) ON DUPLICATE KEY UPDATE setting_value = :v2")
                   ->execute([':v' => $filename, ':v2' => $filename]);
            }
        }
        header('Location: ?page=pengguna&msg=logo_saved');
        exit;
    }

    if ($action === 'upload_hero') {
        if (isset($_FILES['hero_bg']) && $_FILES['hero_bg']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($_FILES['hero_bg']['type'], $allowed)) {
                $ext = pathinfo($_FILES['hero_bg']['name'], PATHINFO_EXTENSION);
                $filename = 'hero_bg.' . $ext;
                $dest = __DIR__ . '/../../assets/' . $filename;
                move_uploaded_file($_FILES['hero_bg']['tmp_name'], $dest);
                $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('hero_bg', :v) ON DUPLICATE KEY UPDATE setting_value = :v2")
                   ->execute([':v' => $filename, ':v2' => $filename]);
            }
        }
        header('Location: ?page=pengguna&msg=hero_saved');
        exit;
    }

    if ($action === 'update_hero_text') {
        $heroTitle = trim($_POST['hero_title'] ?? '');
        $heroSubtitle = trim($_POST['hero_subtitle'] ?? '');
        $heroTextColor = trim($_POST['hero_text_color'] ?? '#c2185b');
        $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('hero_title', :v) ON DUPLICATE KEY UPDATE setting_value = :v2")->execute([':v' => $heroTitle, ':v2' => $heroTitle]);
        $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('hero_subtitle', :v) ON DUPLICATE KEY UPDATE setting_value = :v2")->execute([':v' => $heroSubtitle, ':v2' => $heroSubtitle]);
        $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('hero_text_color', :v) ON DUPLICATE KEY UPDATE setting_value = :v2")->execute([':v' => $heroTextColor, ':v2' => $heroTextColor]);
        header('Location: ?page=pengguna&msg=hero_saved');
        exit;
    }

    if ($action === 'set_hero_gradient') {
        $gradient = $_POST['hero_gradient'] ?? '#fce4ec,#f8bbd0';
        $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('hero_gradient', :v) ON DUPLICATE KEY UPDATE setting_value = :v2")->execute([':v' => $gradient, ':v2' => $gradient]);
        // Set hero_bg to 'gradient' to indicate using gradient instead of image
        $db->prepare("INSERT INTO pengaturan (setting_key, setting_value) VALUES ('hero_bg', 'gradient') ON DUPLICATE KEY UPDATE setting_value = 'gradient'")->execute();
        header('Location: ?page=pengguna&msg=hero_saved');
        exit;
    }

    if ($action === 'add_admin') {
        $username = trim($_POST['new_username'] ?? '');
        $password = $_POST['new_password'] ?? '';
        $nama = trim($_POST['new_nama'] ?? '');
        if ($username && strlen($password) >= 4) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (:u, :p, :n, 'admin')")
               ->execute([':u' => $username, ':p' => $hash, ':n' => $nama ?: $username]);
        }
        header('Location: ?page=pengguna&msg=admin_added');
        exit;
    }

    if ($action === 'toggle_admin') {
        $uid = (int)$_POST['uid'];
        if ($uid != $_SESSION['user']['id']) { // tidak bisa disable diri sendiri
            $stmt = $db->prepare("SELECT is_active FROM users WHERE id = :id");
            $stmt->execute([':id' => $uid]);
            $current = (int)$stmt->fetchColumn();
            $db->prepare("UPDATE users SET is_active = :s WHERE id = :id")->execute([':s' => $current ? 0 : 1, ':id' => $uid]);
        }
        header('Location: ?page=pengguna&msg=admin_toggled');
        exit;
    }

    if ($action === 'reset_admin_pass') {
        $uid = (int)$_POST['uid'];
        $newPass = $_POST['reset_password'] ?? '';
        if (strlen($newPass) >= 4) {
            $db->prepare("UPDATE users SET password = :p WHERE id = :id")->execute([':p' => password_hash($newPass, PASSWORD_DEFAULT), ':id' => $uid]);
        }
        header('Location: ?page=pengguna&msg=pass_reset');
        exit;
    }
}

// Fetch settings
$settings = [];
$rows = $db->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll();
foreach ($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }

// Fetch admin users
$admins = $db->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id")->fetchAll();

// Theme colors
$themeColors = [
    '#e91e63' => 'Pink (Default)',
    '#2196f3' => 'Blue',
    '#4caf50' => 'Green',
    '#ff9800' => 'Orange',
    '#9c27b0' => 'Purple',
    '#607d8b' => 'Gray',
    '#f44336' => 'Red',
    '#009688' => 'Teal',
];
$currentTheme = $settings['tema_warna'] ?? '#e91e63';
?>

<h1 class="page-title">Pengaturan Toko</h1>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $msgs = ['settings_saved'=>'Pengaturan disimpan!', 'logo_saved'=>'Logo diupdate!', 'hero_saved'=>'Hero banner diupdate!', 'admin_added'=>'Admin baru ditambahkan!', 'admin_toggled'=>'Status admin diubah!', 'pass_reset'=>'Password direset!'];
        echo $msgs[$_GET['msg']] ?? 'Berhasil!';
        ?>
    </div>
<?php endif; ?>

<!-- Informasi Toko -->
<div class="card">
    <h3>Informasi Toko</h3>
    <form method="POST">
        <input type="hidden" name="action" value="update_settings">
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Toko</label>
                <input type="text" name="nama_toko" value="<?php echo htmlspecialchars($settings['nama_toko'] ?? ''); ?>" placeholder="Toko Bunga Melati">
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="no_telp_toko" value="<?php echo htmlspecialchars($settings['no_telp_toko'] ?? ''); ?>" placeholder="021-xxx">
            </div>
            <div class="form-group full-width">
                <label>Alamat</label>
                <textarea name="alamat_toko" rows="2" placeholder="Alamat toko"><?php echo htmlspecialchars($settings['alamat_toko'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Pajak (%)</label>
                <input type="number" name="pajak_persen" value="<?php echo $settings['pajak_persen'] ?? '0'; ?>" min="0" max="100" step="0.5">
            </div>
            <div class="form-group">
                <label>Alert Stok Minimum</label>
                <input type="number" name="stok_minimum_alert" value="<?php echo $settings['stok_minimum_alert'] ?? '5'; ?>" min="1">
            </div>
            <div class="form-group">
                <label>Mata Uang</label>
                <input type="text" name="mata_uang" value="<?php echo htmlspecialchars($settings['mata_uang'] ?? 'Rp'); ?>" placeholder="Rp">
            </div>
            <div class="form-group full-width">
                <label>Footer Struk</label>
                <input type="text" name="footer_struk" value="<?php echo htmlspecialchars($settings['footer_struk'] ?? ''); ?>" placeholder="Terima kasih telah berbelanja!">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </div>
    </form>
</div>

<!-- Tema Warna -->
<div class="card">
    <h3>Tema Warna</h3>
    <form method="POST">
        <input type="hidden" name="action" value="update_settings">
        <!-- pass through other settings so they don't get cleared -->
        <?php foreach (['nama_toko','alamat_toko','no_telp_toko','pajak_persen','stok_minimum_alert','mata_uang','footer_struk'] as $k): ?>
        <input type="hidden" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($settings[$k] ?? ''); ?>">
        <?php endforeach; ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px;">
            <?php foreach ($themeColors as $hex => $name): ?>
            <label style="cursor:pointer;text-align:center;">
                <input type="radio" name="tema_warna" value="<?php echo $hex; ?>" <?php echo $currentTheme === $hex ? 'checked' : ''; ?> style="display:none;">
                <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $hex; ?>;border:3px solid <?php echo $currentTheme === $hex ? '#333' : 'transparent'; ?>;"></div>
                <small style="font-size:0.7rem;color:#666;"><?php echo $name; ?></small>
            </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Terapkan Tema</button>
    </form>
</div>

<!-- Logo Toko -->
<div class="card">
    <h3>Logo / Icon Toko</h3>
    <?php if (!empty($settings['logo_toko'])): ?>
        <div style="margin-bottom:12px;">
            <img src="../assets/<?php echo $settings['logo_toko']; ?>" alt="Logo" style="max-height:80px;border-radius:8px;border:1px solid var(--gray-light);">
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_logo">
        <div class="form-group" style="max-width:300px;">
            <label>Upload Logo (JPG/PNG/WEBP/SVG)</label>
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml">
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">Upload</button>
    </form>
</div>

<!-- Hero Banner -->
<div class="card">
    <h3>Hero Banner (Halaman Utama)</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
        <!-- Hero Text -->
        <div>
            <form method="POST">
                <input type="hidden" name="action" value="update_hero_text">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Judul Hero</label>
                    <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Bunga Segar untuk Setiap Momen'); ?>" placeholder="Judul utama banner">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Subtitle Hero</label>
                    <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?>" placeholder="Deskripsi singkat">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Warna Text</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="color" name="hero_text_color" value="<?php echo $settings['hero_text_color'] ?? '#c2185b'; ?>" style="width:40px;height:34px;border:1px solid var(--gray-light);border-radius:6px;cursor:pointer;">
                        <input type="text" name="hero_text_color_hex" value="<?php echo $settings['hero_text_color'] ?? '#c2185b'; ?>" maxlength="7" style="width:80px;padding:7px;border:1.5px solid var(--gray-light);border-radius:6px;font-size:0.85rem;" oninput="this.previousElementSibling.value=this.value" disabled>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Text</button>
            </form>
        </div>
        <!-- Hero Background -->
        <div>
            <p style="font-size:0.82rem;font-weight:600;margin-bottom:8px;">Background</p>
            <?php if (!empty($settings['hero_bg']) && $settings['hero_bg'] !== 'gradient'): ?>
                <img src="../assets/<?php echo $settings['hero_bg']; ?>" alt="Hero BG" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;border:1px solid var(--gray-light);">
            <?php else: ?>
                <?php $heroGradient = $settings['hero_gradient'] ?? '#fce4ec,#f8bbd0'; ?>
                <div style="background:linear-gradient(135deg,<?php echo $heroGradient; ?>);height:80px;border-radius:8px;margin-bottom:8px;"></div>
            <?php endif; ?>

            <!-- Upload Image -->
            <form method="POST" enctype="multipart/form-data" style="margin-bottom:10px;">
                <input type="hidden" name="action" value="upload_hero">
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="file" name="hero_bg" accept="image/jpeg,image/png,image/webp" style="font-size:0.82rem;flex:1;">
                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                </div>
            </form>

            <!-- Or use gradient -->
            <form method="POST" style="margin-top:8px;">
                <input type="hidden" name="action" value="set_hero_gradient">
                <p style="font-size:0.8rem;color:var(--gray);margin-bottom:6px;">Atau gunakan gradient warna:</p>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;">
                    <?php
                    $gradients = [
                        '#fce4ec,#f8bbd0' => 'Pink',
                        '#e3f2fd,#bbdefb' => 'Blue',
                        '#e8f5e9,#c8e6c9' => 'Green',
                        '#fff3e0,#ffe0b2' => 'Orange',
                        '#f3e5f5,#e1bee7' => 'Purple',
                        '#eceff1,#cfd8dc' => 'Gray',
                        '#fff9c4,#fff59d' => 'Yellow',
                        '#e0f7fa,#b2ebf2' => 'Teal',
                    ];
                    $currentGradient = $settings['hero_gradient'] ?? '#fce4ec,#f8bbd0';
                    foreach ($gradients as $grad => $name):
                        $colors = explode(',', $grad);
                        $isActive = ($currentGradient === $grad && (empty($settings['hero_bg']) || $settings['hero_bg'] === 'gradient'));
                    ?>
                    <label style="cursor:pointer;text-align:center;">
                        <input type="radio" name="hero_gradient" value="<?php echo $grad; ?>" <?php echo $isActive ? 'checked' : ''; ?> style="display:none;">
                        <div style="width:36px;height:36px;border-radius:6px;background:linear-gradient(135deg,<?php echo $grad; ?>);border:2px solid <?php echo $isActive ? '#333' : 'transparent'; ?>;"></div>
                        <small style="font-size:0.65rem;color:#666;"><?php echo $name; ?></small>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Gunakan Gradient</button>
            </form>
        </div>
    </div>
</div>

<!-- Manage Admin Users -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
        <h3 style="margin:0;">Admin Users</h3>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('add-admin-form').style.display='block'">+ Tambah Admin</button>
    </div>

    <!-- Add Admin Form (hidden by default) -->
    <form method="POST" id="add-admin-form" style="display:none;margin-bottom:15px;padding:12px;background:#f9f9f9;border-radius:8px;">
        <input type="hidden" name="action" value="add_admin">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;"><label>Username</label><input type="text" name="new_username" required placeholder="username"></div>
            <div class="form-group" style="margin:0;"><label>Password</label><input type="text" name="new_password" required minlength="4" placeholder="min 4 char"></div>
            <div class="form-group" style="margin:0;"><label>Nama</label><input type="text" name="new_nama" placeholder="Nama lengkap"></div>
            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('form').style.display='none'">Batal</button>
        </div>
    </form>

    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Username</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($admins as $adm): ?>
                <tr style="<?php echo !$adm['is_active'] ? 'opacity:0.5;' : ''; ?>">
                    <td><code><?php echo htmlspecialchars($adm['username']); ?></code></td>
                    <td><?php echo htmlspecialchars($adm['nama_lengkap']); ?></td>
                    <td><span class="badge <?php echo $adm['is_active'] ? 'badge-ok' : 'badge-danger'; ?>"><?php echo $adm['is_active'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
                    <td style="white-space:nowrap;display:flex;gap:5px;">
                        <?php if ($adm['id'] != $_SESSION['user']['id']): ?>
                        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="toggle_admin"><input type="hidden" name="uid" value="<?php echo $adm['id']; ?>"><button type="submit" class="btn btn-sm <?php echo $adm['is_active'] ? 'btn-delete' : 'btn-edit'; ?>" onclick="return confirm('Ubah status?')"><?php echo $adm['is_active'] ? 'Disable' : 'Enable'; ?></button></form>
                        <form method="POST" style="display:inline;" onsubmit="var p=prompt('Password baru untuk <?php echo $adm['username']; ?>:');if(!p||p.length<4){alert('Min 4 karakter');return false;}this.querySelector('[name=reset_password]').value=p;"><input type="hidden" name="action" value="reset_admin_pass"><input type="hidden" name="uid" value="<?php echo $adm['id']; ?>"><input type="hidden" name="reset_password" value=""><button type="submit" class="btn btn-sm btn-secondary">Reset Pass</button></form>
                        <?php else: ?>
                        <span style="font-size:0.8rem;color:var(--gray);">(Anda)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
