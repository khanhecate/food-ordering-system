# POS Bunga (Food Ordering System)

Aplikasi toko online dan admin panel berbasis PHP + MariaDB.

- `html/shop/` : halaman pelanggan (katalog, checkout, tracking, profil)
- `html/admin/` : panel admin (dashboard, produk, transaksi, laporan, dll)
- `html/api/` : endpoint API
- `database.sql` : dump database `posbunga`

## Instalasi di Ubuntu (otomatis)

Cukup satu kali jalankan `install.sh`. Script akan menyiapkan semuanya sampai aplikasi bisa diakses.

```bash
git clone https://github.com/khanhecate/food-ordering-system.git
cd food-ordering-system
sudo ./install.sh
```

### Yang dikerjakan script

1. Install Apache2, PHP 8.3 (PPA ondrej) beserta ekstensi yang dibutuhkan, MariaDB, dan Composer
2. Set password root MariaDB (tanpa prompt interaktif)
3. Buat database `posbunga` dan user `posbunga`, lalu import `database.sql`
4. Ubah `DB_HOST` di `html/config/database.php` dari `db` menjadi `localhost`
5. Jalankan `composer install` di folder `html/` (dompdf)
6. Atur ownership dan permission, termasuk folder upload `html/assets/produk`
7. Buat virtual host Apache `posbunga.local` dengan document root `html/`

### Akses aplikasi

| Halaman | URL |
| --- | --- |
| Shop | `http://posbunga.local/shop/` |
| Admin | `http://posbunga.local/admin/` |

Jika diakses dari komputer lain, pakai IP server: `http://<IP-SERVER>/shop/`.
Domain `posbunga.local` hanya ditambahkan ke `/etc/hosts` server itu sendiri.

### Kredensial default

| Item | Nilai |
| --- | --- |
| Password root MariaDB | `posbunga_root123` |
| Database | `posbunga` |
| User database | `posbunga` |
| Password user database | `posbunga123` |

**Penting:** nilai di atas adalah default dan terlihat publik di repo. Untuk server
production, ubah variabel `DB_ROOT_PASS` dan `DB_PASS` di bagian atas `install.sh`,
serta `DB_PASS` di `html/config/database.php`, sebelum menjalankan script.

### Konfigurasi

Variabel yang bisa diubah di bagian atas `install.sh`:

| Variabel | Fungsi |
| --- | --- |
| `DB_NAME`, `DB_USER`, `DB_PASS` | Database dan user aplikasi |
| `DB_ROOT_PASS` | Password root MariaDB yang akan di-set |
| `APP_DOMAIN` | ServerName virtual host Apache |
| `APACHE_PORT` | Port Apache |
| `PHP_VERSION` | Versi PHP yang diinstall |

### Menjalankan ulang

Script aman dijalankan ulang. Jika root MariaDB sudah memakai password yang
dikonfigurasi, langkah set password dilewati. Jika root memakai password lain,
script berhenti dan meminta `DB_ROOT_PASS` disesuaikan.

Perhatian: `database.sql` memakai `DROP TABLE IF EXISTS`, jadi menjalankan ulang
script akan **menimpa data database** dengan isi dump.

## Instalasi manual

1. Install Apache, PHP 8.3 (ekstensi: mysql, mbstring, xml, curl, gd, zip, bcmath), MariaDB, Composer
2. Buat database dan import dump:
   ```bash
   sudo mysql -e "CREATE DATABASE posbunga CHARACTER SET utf8mb4;"
   sudo mysql -e "CREATE USER 'posbunga'@'localhost' IDENTIFIED BY 'posbunga123';"
   sudo mysql -e "GRANT ALL ON posbunga.* TO 'posbunga'@'localhost';"
   sudo mysql posbunga < database.sql
   ```
3. Set `DB_HOST` di `html/config/database.php` ke `localhost`
4. Install dependency: `cd html && composer install`
5. Arahkan document root web server ke folder `html/` dan beri hak tulis ke `html/assets/produk` untuk user web server (`www-data`)

## Instalasi dengan Docker

`html/config/database.php` memakai `DB_HOST = 'db'` (nama service di docker-compose).
Jika memakai Docker, jangan jalankan `install.sh` karena script mengubah host ke `localhost`.
