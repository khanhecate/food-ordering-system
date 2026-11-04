<?php
// Jika sudah login, redirect
if (isset($_SESSION['user'])) {
    header('Location: /shop/?page=katalog');
    exit;
}

$error = '';
$success = '';

// Handle POST register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($nama_lengkap) || empty($password)) {
        $error = 'Username, nama lengkap, dan password wajib diisi!';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter!';
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter!';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Check username exists
        $check = $db->prepare("SELECT id FROM users WHERE username = :username");
        $check->execute([':username' => $username]);
        if ($check->fetch()) {
            $error = 'Username sudah dipakai, pilih yang lain!';
        } else {
            // Insert new customer
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, no_telp, role, is_active) VALUES (:user, :pass, :nama, :telp, 'kasir', 1)");
            $stmt->execute([
                ':user' => $username,
                ':pass' => $hashed,
                ':nama' => $nama_lengkap,
                ':telp' => $no_telp
            ]);

            // Auto-login after register
            $newId = $db->lastInsertId();
            $_SESSION['user'] = [
                'id' => $newId,
                'username' => $username,
                'nama' => $nama_lengkap,
                'role' => 'kasir'
            ];

            $redirect = $_GET['redirect'] ?? 'katalog';
            header('Location: /shop/?page=' . $redirect);
            exit;
        }
    }
}
?>
<div class="login-page">
    <div class="login-card">
        <h2>Daftar Akun</h2>
        <p class="subtitle">Buat akun baru untuk berbelanja</p>

        <?php if ($error): ?>
            <div class="login-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST">
            <div>
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan nama lengkap" required value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
            </div>
            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Pilih username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div>
                <label for="no_telp">No. Telepon (opsional)</label>
                <input type="tel" id="no_telp" name="no_telp" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($_POST['no_telp'] ?? ''); ?>">
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimal 4 karakter" required>
            </div>
            <div>
                <label for="password_confirm">Konfirmasi Password</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn-submit">Daftar</button>
        </form>

        <div class="login-footer">
            <p>Sudah punya akun? <a href="?page=login<?php echo isset($_GET['redirect']) ? '&redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>">Masuk di sini</a></p>
        </div>
    </div>
</div>

<style>
.login-page { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.login-card { background: #fff; border-radius: 16px; padding: 40px 30px; width: 100%; max-width: 380px; box-shadow: 0 20px 60px rgba(0,0,0,0.08); }
.login-card h2 { text-align: center; font-size: 1.3rem; margin-bottom: 5px; color: var(--dark); }
.login-card .subtitle { text-align: center; color: var(--gray); font-size: 0.9rem; margin-bottom: 25px; }
.login-form { display: flex; flex-direction: column; gap: 16px; }
.login-form label { font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; display: block; }
.login-form input { width: 100%; padding: 12px; border: 2px solid var(--gray-light); border-radius: 8px; font-size: 0.95rem; outline: none; transition: border-color 0.2s; }
.login-form input:focus { border-color: var(--primary); }
.login-form .btn-submit { padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; margin-top: 8px; }
.login-form .btn-submit:hover { background: var(--primary-dark); }
.login-error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 8px; font-size: 0.85rem; text-align: center; margin-bottom: 12px; }
.login-footer { text-align: center; margin-top: 20px; font-size: 0.85rem; }
.login-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
</style>
