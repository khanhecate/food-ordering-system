<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Load toko settings
$_settings = [];
$_sRows = $db->query("SELECT setting_key, setting_value FROM pengaturan")->fetchAll();
foreach ($_sRows as $_r) { $_settings[$_r['setting_key']] = $_r['setting_value']; }
$_namaToko = $_settings['nama_toko'] ?? 'Toko Bunga Melati';
$_logoToko = $_settings['logo_toko'] ?? '';

// Router
$page = $_GET['page'] ?? 'katalog';
$allowedPages = ['katalog', 'login', 'register', 'logout', 'checkout', 'tracking', 'profil'];

if (!in_array($page, $allowedPages)) {
    $page = 'katalog';
}

// Handle logout directly
if ($page === 'logout') {
    session_destroy();
    header('Location: /shop/?page=katalog');
    exit;
}

// Pages that require login
$authPages = ['checkout', 'tracking', 'profil'];
if (in_array($page, $authPages) && !isset($_SESSION['user'])) {
    header('Location: /shop/?page=login&redirect=' . $page);
    exit;
}

// Process page content (buffer for redirects)
$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) { $page = 'katalog'; $pageFile = __DIR__ . '/pages/katalog.php'; }

ob_start();
include $pageFile;
$pageContent = ob_get_clean();

// Extra CSS per page
$extraCSS = '';
if ($page === 'checkout') $extraCSS = '<link rel="stylesheet" href="css/checkout.css">';
if ($page === 'tracking') $extraCSS = '<link rel="stylesheet" href="css/tracking.css">';

$currentPage = $page;

// Theme color from settings
$themeColor = '#e91e63';
if (!empty($_settings['tema_warna']) && preg_match('/^#[0-9a-fA-F]{6}$/', $_settings['tema_warna'])) {
    $themeColor = $_settings['tema_warna'];
}
function fe_hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}
function fe_darken($hex, $percent) {
    [$r, $g, $b] = fe_hexToRgb($hex);
    $f = 1 - ($percent / 100);
    return sprintf('#%02x%02x%02x', round($r * $f), round($g * $f), round($b * $f));
}
function fe_lighten($hex, $percent) {
    [$r, $g, $b] = fe_hexToRgb($hex);
    $f = $percent / 100;
    return sprintf('#%02x%02x%02x', round($r + (255 - $r) * $f), round($g + (255 - $g) * $f), round($b + (255 - $b) * $f));
}
$themeDark = fe_darken($themeColor, 20);
$themeLight = fe_lighten($themeColor, 88);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_namaToko); ?></title>
    <link rel="stylesheet" href="css/style.css?v=11">
    <?php echo $extraCSS; ?>
    <style>
        :root {
            --primary: <?php echo $themeColor; ?>;
            --primary-dark: <?php echo $themeDark; ?>;
            --primary-light: <?php echo $themeLight; ?>;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-inner">
            <a href="?page=katalog" class="logo">
                <?php if ($_logoToko): ?>
                    <img src="/assets/<?php echo $_logoToko; ?>" alt="Logo" class="logo-img">
                <?php endif; ?>
                <?php echo htmlspecialchars($_namaToko); ?>
            </a>
            <nav class="nav">
                <a href="?page=katalog" class="nav-link <?php echo $currentPage === 'katalog' ? 'active' : ''; ?>">Katalog</a>
                <?php if (isset($_SESSION['user'])): ?>
                    <?php
                    $avatarStmt = $db->prepare("SELECT foto_profil FROM users WHERE id = :id");
                    $avatarStmt->execute([':id' => $_SESSION['user']['id']]);
                    $userAvatar = $avatarStmt->fetchColumn();
                    $avatarSrc = $userAvatar ? '/assets/users/' . $userAvatar : '/assets/default-avatar.png';
                    ?>
                    <div class="user-dropdown">
                        <button class="nav-link dropdown-toggle" onclick="toggleDropdown()">
                            <img src="<?php echo $avatarSrc; ?>" alt="" class="nav-avatar">
                            <?php echo htmlspecialchars($_SESSION['user']['username']); ?> ▾
                        </button>
                        <div class="dropdown-menu" id="user-dropdown-menu">
                            <a href="?page=profil" class="dropdown-item">Pengaturan Profil</a>
                            <a href="?page=tracking" class="dropdown-item">Pesanan Saya</a>
                            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="/admin/" class="dropdown-item">Admin Panel</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="?page=logout" class="dropdown-item dropdown-logout">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="?page=login" class="nav-link">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Page Content -->
    <?php echo $pageContent; ?>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2026 <?php echo htmlspecialchars($_namaToko); ?></p>
        <p><?php echo htmlspecialchars($_settings['no_telp_toko'] ?? ''); ?></p>
    </footer>

    <script src="js/app.js?v=2"></script>
    <script>
    function toggleDropdown() {
        const menu = document.getElementById('user-dropdown-menu');
        menu.classList.toggle('show');
    }
    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            document.getElementById('user-dropdown-menu')?.classList.remove('show');
        }
    });
    </script>
</body>
</html>
