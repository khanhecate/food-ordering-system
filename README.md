# Toko Online (POS + Online Shop)

Aplikasi toko online dan admin panel berbasis PHP + MariaDB.

- `html/shop/` : halaman pelanggan (katalog, checkout, tracking, profil)
- `html/admin/` : panel admin (dashboard, produk, transaksi, laporan, dll)
- `html/api/` : endpoint API
- `database.sql` : dump database `tokoonline`

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
3. Buat database `tokoonline` dan user `tokoonline`, lalu import `database.sql`
4. Ubah `DB_HOST` di `html/config/database.php` dari `db` menjadi `localhost`
5. Jalankan `composer install` di folder `html/` (dompdf)
6. Atur ownership dan permission, termasuk folder upload `html/assets/produk`
7. Buat virtual host Apache `tokoonline.local` dengan document root `html/`

### Akses aplikasi

| Halaman | URL |
| --- | --- |
| Shop | `http://tokoonline.local/shop/` |
| Admin | `http://tokoonline.local/admin/` |

Jika diakses dari komputer lain, pakai IP server: `http://<IP-SERVER>/shop/`.
Domain `tokoonline.local` hanya ditambahkan ke `/etc/hosts` server itu sendiri.

### Kredensial default

| Item | Nilai |
| --- | --- |
| Password root MariaDB | `tokoonline_root123` |
| Database | `tokoonline` |
| User database | `tokoonline` |
| Password user database | `tokoonline123` |

**Penting:** nilai di atas adalah default dan terlihat publik di repo. Untuk server
production, ubah variabel `DB_ROOT_PASS` dan `DB_PASS` di bagian atas `install.sh`,
serta `DB_PASS` di `html/config/database.php`, sebelum menjalankan script.

### Login default aplikasi (dari data contoh `database.sql`)

| Username | Password | Role |
| --- | --- | --- |
| `sandal` | `sandal` | admin |
| `user` | `user` | kasir |

Login admin di `http://tokoonline.local/admin/`, login kasir/pelanggan di
`http://tokoonline.local/shop/?page=login`. **Ganti password ini** setelah
instalasi (menu Ganti Password di admin panel), terutama untuk server production.

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
   sudo mysql -e "CREATE DATABASE tokoonline CHARACTER SET utf8mb4;"
   sudo mysql -e "CREATE USER 'tokoonline'@'localhost' IDENTIFIED BY 'tokoonline123';"
   sudo mysql -e "GRANT ALL ON tokoonline.* TO 'tokoonline'@'localhost';"
   sudo mysql tokoonline < database.sql
   ```
3. Set `DB_HOST` di `html/config/database.php` ke `localhost`
4. Install dependency: `cd html && composer install`
5. Arahkan document root web server ke folder `html/` dan beri hak tulis ke `html/assets/produk` untuk user web server (`www-data`)

## Catatan

Aplikasi ini generik untuk jualan produk apapun (bukan khusus bunga/makanan).
Beberapa nama kolom di skema database (`produk.nama_bunga`, `produk.kode_bunga`)
masih memakai penamaan lama dari versi awal, tapi secara fungsi berlaku sebagai
nama dan kode produk biasa — tidak memengaruhi penggunaan aplikasi.

## Instalasi dengan Docker

`html/config/database.php` memakai `DB_HOST = 'db'` (nama service di docker-compose).
Jika memakai Docker, jangan jalankan `install.sh` karena script mengubah host ke `localhost`.
