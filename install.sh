#!/usr/bin/env bash
#
# install.sh — Installer untuk project "tokoonline" (toko online / POS) di Ubuntu
#
# Menginstall Apache2, PHP, MariaDB, Composer, membuat database dari database.sql,
# dan mengatur virtual host agar project langsung bisa diakses lewat browser.
#
# Jalankan dari root project (sejajar dengan database.sql):
#   chmod +x install.sh
#   sudo ./install.sh
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Konfigurasi (sesuaikan bila perlu)
# ---------------------------------------------------------------------------
DB_NAME="tokoonline"
DB_USER="tokoonline"
DB_PASS="tokoonline123"
DB_ROOT_PASS="tokoonline_root123"   # password root MariaDB yang akan DI-SET oleh script ini

APP_DOMAIN="tokoonline.local"  # ServerName virtual host Apache
APACHE_PORT="80"

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOC_ROOT="${PROJECT_ROOT}/html"
SQL_FILE="${PROJECT_ROOT}/database.sql"

PHP_VERSION="8.3"

# ---------------------------------------------------------------------------
# Helper
# ---------------------------------------------------------------------------
log()  { echo -e "\033[1;32m[install]\033[0m $*"; }
warn() { echo -e "\033[1;33m[warn]\033[0m $*"; }
err()  { echo -e "\033[1;31m[error]\033[0m $*" >&2; }

if [[ "${EUID}" -ne 0 ]]; then
  err "Script ini harus dijalankan sebagai root. Coba: sudo ./install.sh"
  exit 1
fi

if [[ ! -f "${SQL_FILE}" ]]; then
  err "Tidak menemukan database.sql di ${SQL_FILE}"
  exit 1
fi

# ---------------------------------------------------------------------------
# 1. Update sistem & install dependency dasar
# ---------------------------------------------------------------------------
log "Update package list..."
apt-get update -y

log "Install software-properties-common & curl..."
apt-get install -y software-properties-common curl ca-certificates lsb-release apt-transport-https gnupg2

# Repo ondrej/php untuk versi PHP yang lebih baru (Ubuntu default kadang tidak sesuai)
if ! grep -rq "ondrej/php" /etc/apt/sources.list.d/ 2>/dev/null; then
  log "Menambahkan repository ondrej/php..."
  add-apt-repository -y ppa:ondrej/php
  apt-get update -y
fi

# ---------------------------------------------------------------------------
# 2. Install Apache + PHP + ekstensi yang dibutuhkan
# ---------------------------------------------------------------------------
log "Install Apache2 & PHP ${PHP_VERSION} beserta ekstensinya..."
apt-get install -y \
  apache2 \
  php${PHP_VERSION} \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-gd \
  php${PHP_VERSION}-zip \
  php${PHP_VERSION}-bcmath \
  libapache2-mod-php${PHP_VERSION}

a2enmod php${PHP_VERSION} rewrite
a2dismod mpm_event 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# ---------------------------------------------------------------------------
# 3. Install MariaDB Server
# ---------------------------------------------------------------------------
log "Install MariaDB server..."
apt-get install -y mariadb-server mariadb-client

systemctl enable mariadb
systemctl start mariadb

# ---------------------------------------------------------------------------
# 4. Install Composer
# ---------------------------------------------------------------------------
if ! command -v composer >/dev/null 2>&1; then
  log "Install Composer..."
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
else
  log "Composer sudah terinstall, skip."
fi

# ---------------------------------------------------------------------------
# 5. Buat database & user, import database.sql
# ---------------------------------------------------------------------------
log "Set password root MariaDB..."

# File kredensial sementara agar password tidak muncul di daftar proses (ps)
MYSQL_CNF="$(mktemp)"
chmod 600 "${MYSQL_CNF}"
trap 'rm -f "${MYSQL_CNF}"' EXIT
cat > "${MYSQL_CNF}" <<CNF
[client]
user=root
password=${DB_ROOT_PASS}
CNF

# Login root: coba dengan password dulu (kalau script dijalankan ulang),
# kalau gagal pakai unix_socket (default fresh-install Ubuntu, tanpa password).
if mysql --defaults-extra-file="${MYSQL_CNF}" -e "SELECT 1" >/dev/null 2>&1; then
  log "Root MariaDB sudah memakai password yang dikonfigurasi, skip set password."
  MYSQL_ROOT_CMD=(mysql --defaults-extra-file="${MYSQL_CNF}")
elif mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
  # Pertahankan unix_socket (sudo mysql tetap jalan) DAN aktifkan login via password
  mysql -u root <<SQL
ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING PASSWORD('${DB_ROOT_PASS}');
FLUSH PRIVILEGES;
SQL
  MYSQL_ROOT_CMD=(mysql --defaults-extra-file="${MYSQL_CNF}")
  log "Password root MariaDB berhasil di-set."
else
  err "Tidak bisa login ke MariaDB sebagai root (bukan via socket, dan password '${DB_ROOT_PASS}' salah)."
  err "Root sudah punya password lain. Isi DB_ROOT_PASS di install.sh dengan password root yang benar, lalu jalankan ulang."
  exit 1
fi

log "Membuat database '${DB_NAME}' dan user '${DB_USER}'..."
"${MYSQL_ROOT_CMD[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

log "Import database.sql ke database '${DB_NAME}'..."
"${MYSQL_ROOT_CMD[@]}" "${DB_NAME}" < "${SQL_FILE}"

# ---------------------------------------------------------------------------
# 6. Sesuaikan config/database.php agar host = localhost (bukan 'db' ala docker)
# ---------------------------------------------------------------------------
DB_CONFIG_FILE="${DOC_ROOT}/config/database.php"
if [[ -f "${DB_CONFIG_FILE}" ]]; then
  log "Menyesuaikan DB_HOST di ${DB_CONFIG_FILE} menjadi 'localhost'..."
  sed -i "s/define('DB_HOST', 'db');/define('DB_HOST', 'localhost');/" "${DB_CONFIG_FILE}"
fi

# ---------------------------------------------------------------------------
# 7. Install dependency composer (dompdf, dll)
# ---------------------------------------------------------------------------
if [[ -f "${DOC_ROOT}/composer.json" ]]; then
  log "Menjalankan composer install di ${DOC_ROOT}..."
  (cd "${DOC_ROOT}" && composer install --no-interaction --prefer-dist)
fi

# ---------------------------------------------------------------------------
# 8. Permission folder assets (upload produk)
# ---------------------------------------------------------------------------
log "Mengatur ownership & permission..."
chown -R www-data:www-data "${PROJECT_ROOT}"
find "${DOC_ROOT}" -type d -exec chmod 755 {} \;
find "${DOC_ROOT}" -type f -exec chmod 644 {} \;
chmod -R 775 "${DOC_ROOT}/assets/produk"

# ---------------------------------------------------------------------------
# 9. Setup Apache Virtual Host
# ---------------------------------------------------------------------------
VHOST_FILE="/etc/apache2/sites-available/tokoonline.conf"
log "Membuat virtual host Apache di ${VHOST_FILE}..."

cat > "${VHOST_FILE}" <<VHOST
<VirtualHost *:${APACHE_PORT}>
    ServerName ${APP_DOMAIN}
    DocumentRoot ${DOC_ROOT}

    <Directory ${DOC_ROOT}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/tokoonline-error.log
    CustomLog \${APACHE_LOG_DIR}/tokoonline-access.log combined
</VirtualHost>
VHOST

a2ensite tokoonline.conf
a2dissite 000-default.conf 2>/dev/null || true

systemctl restart apache2

# Tambahkan domain lokal ke /etc/hosts bila belum ada
if ! grep -q "${APP_DOMAIN}" /etc/hosts; then
  log "Menambahkan ${APP_DOMAIN} ke /etc/hosts..."
  echo "127.0.0.1    ${APP_DOMAIN}" >> /etc/hosts
fi

# ---------------------------------------------------------------------------
# Selesai
# ---------------------------------------------------------------------------
log "Instalasi selesai!"
echo ""
echo "  Akses aplikasi (shop)  : http://${APP_DOMAIN}/shop/"
echo "  Akses aplikasi (admin) : http://${APP_DOMAIN}/admin/"
echo "  Database               : ${DB_NAME} (user: ${DB_USER}, pass: ${DB_PASS})"
echo "  MariaDB root password  : ${DB_ROOT_PASS}"
echo "  Login root MariaDB     : mysql -u root -p   (atau: sudo mysql)"
echo ""
echo "  Jika domain '${APP_DOMAIN}' tidak bisa diakses dari luar server,"
echo "  gunakan IP server langsung, misal: http://<IP-SERVER>/shop/"
