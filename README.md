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

Dari folder mana pun script dijalankan, aplikasi selalu diinstall di **`/var/www/html`**
(repo di-clone ke sana). Folder hasil clone awal boleh dihapus setelah instalasi selesai.
Apache hanya melayani subfolder `/var/www/html/html`, sehingga `.git`, `database.sql`
dan `install.sh` tidak bisa diakses dari web.

### Yang dikerjakan script

1. Install Apache2, PHP 8.3 (PPA ondrej) beserta ekstensi yang dibutuhkan, MariaDB, dan Composer
2. Set password root MariaDB (tanpa prompt interaktif)
3. Buat database `tokoonline` dan user `tokoonline`, lalu import `database.sql`
4. Ubah `DB_HOST` di `html/config/database.php` dari `db` menjadi `localhost`
5. Jalankan `composer install` di folder `html/` (dompdf)
6. Atur ownership dan permission, termasuk folder upload `html/assets/produk`
7. Buat virtual host Apache `tokoonline.local` dengan document root `/var/www/html/html`

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
| `APP_DIR` | Lokasi instalasi project (default `/var/www/html`) |
| `PHP_VERSION` | Versi PHP yang diinstall |

### Update / menjalankan ulang

Script aman dijalankan ulang untuk meng-update aplikasi:

- Jika `/var/www/html` sudah berisi project, script menjalankan `git pull` (kode di-update).
- Jika `/var/www/html` kosong atau hanya berisi halaman default Apache, project di-clone dari GitHub.
- Jika sebelumnya kamu menjalankan script dari folder lain (misalnya di home), file upload di
  `html/assets` dari folder lama disalin ke `/var/www/html/html/assets` tanpa menimpa file yang ada.
- Jika database `tokoonline` sudah berisi tabel, `database.sql` **tidak di-import ulang**, jadi data aman.
- Jika root MariaDB sudah memakai password yang dikonfigurasi, langkah set password dilewati.
  Jika root memakai password lain, script berhenti dan meminta `DB_ROOT_PASS` disesuaikan.
- Lokasi instalasi bisa diubah lewat variabel `APP_DIR` di bagian atas `install.sh`.

Untuk sengaja menimpa database dengan isi `database.sql` (**semua data lama hilang**):

```bash
sudo FORCE_DB_IMPORT=1 ./install.sh
```

## Menambah produk contoh ke server yang sudah terinstall

`install.sh` tidak mengimpor ulang `database.sql` kalau database sudah berisi data.
Untuk menambah kategori "Makanan" dan 4 produk contoh (Bruleebomb, Chicken Cordon Bleu,
Lumpia Kornet Mozzarella, Pizza Springroll) tanpa menyentuh data lain:

```bash
cd /var/www/html && sudo git -c safe.directory=/var/www/html pull
sudo mysql tokoonline < /var/www/html/tambah-produk.sql
```

File ini aman dijalankan berulang. Harga dan stok awalnya contoh, ubah lewat admin panel (Data Produk).

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
5. Letakkan project di `/var/www/html`, arahkan document root web server ke subfolder `html/` (`/var/www/html/html`) dan beri hak tulis ke `html/assets/produk` untuk user web server (`www-data`)

## Catatan

Aplikasi ini generik untuk jualan produk apapun (bukan khusus bunga/makanan).
Beberapa nama kolom di skema database (`produk.nama_bunga`, `produk.kode_bunga`)
masih memakai penamaan lama dari versi awal, tapi secara fungsi berlaku sebagai
nama dan kode produk biasa — tidak memengaruhi penggunaan aplikasi.

## Instalasi dengan Docker

`html/config/database.php` memakai `DB_HOST = 'db'` (nama service di docker-compose).
Jika memakai Docker, jangan jalankan `install.sh` karena script mengubah host ke `localhost`.

Untuk development lokal, pakai `docker-compose.yml`:

```bash
docker compose up -d
```

| Layanan | URL |
| --- | --- |
| Shop | `http://localhost:8080/shop/` |
| Admin | `http://localhost:8080/admin/` |
| phpMyAdmin | `http://localhost:8081` (root / `root123`) |

`database.sql` otomatis diimpor saat volume database pertama kali dibuat. Kalau database
sudah ada dan perlu data baru, jalankan `tambah-produk.sql`, atau reset dengan
`docker compose down -v` (**menghapus semua data database**).

Catatan: `docker-compose.yml` ini untuk development. Port database (3306) dan phpMyAdmin
(8081) terbuka dengan password default, jangan dipakai di server publik. Laporan PDF
(dompdf) tidak jalan di mode ini karena tidak ada composer/gd/zip.
