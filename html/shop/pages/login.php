<?php
// Jika sudah login, redirect
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: /admin/');
    } else {
        header('Location: /shop/?page=katalog');
    }
    exit;
}

$error = '';

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama' => $user['nama_lengkap'],
            'role' => $user['role']
        ];
        
        if ($user['role'] === 'admin') {
            header('Location: /admin/');
        } else {
            $redirect = $_GET['redirect'] ?? 'katalog';
            header('Location: /shop/?page=' . $redirect);
        }
        exit;
    } else {
        $error = 'Username atau password salah! <a href="?page=register' . (isset($_GET['redirect']) ? '&redirect=' . htmlspecialchars($_GET['redirect']) : '') . '">Belum punya akun? Daftar di sini</a>';
    }
}
?>
<div class="login-page">
    <div class="login-card">
        <h2><?php echo htmlspecialchars($_namaToko); ?></h2>
        <p class="subtitle">Masuk ke akun Anda</p>
        
        <?php if ($error): ?>
            <div class="login-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="POST">
            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>
        
        <div class="demo-info">
            <p><strong>Demo Login:</strong></p>
            <p>Admin: <code>sandal</code> / <code>sandal</code></p>
            <p>User: <code>user</code> / <code>user</code></p>
        </div>
        
        <div class="login-footer">
            <p>Belum punya akun? <a href="?page=register<?php echo isset($_GET['redirect']) ? '&redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>">Daftar di sini</a></p>
            <a href="?page=katalog" style="display:inline-block;margin-top:8px;font-size:0.8rem;">Kembali ke Katalog</a>
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
.login-error a { color: var(--primary); font-weight: 600; text-decoration: none; }
.login-error a:hover { text-decoration: underline; }
.login-footer { text-align: center; margin-top: 20px; font-size: 0.8rem; }
.login-footer a { color: var(--primary); text-decoration: none; }
.demo-info { background: #e3f2fd; padding: 12px; border-radius: 8px; font-size: 0.8rem; margin-top: 15px; }
.demo-info p { margin-bottom: 4px; }
.demo-info code { background: #bbdefb; padding: 1px 5px; border-radius: 3px; }
</style>
