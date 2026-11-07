-- Tambah kategori "Makanan" dan 4 produk makanan.
-- Aman dijalankan berulang (tidak membuat duplikat, tidak menyentuh data lain).
--
-- Di server yang sudah terinstall:
--   sudo mysql tokoonline < /var/www/html/tambah-produk.sql
--
-- Foto harus ada di html/assets/produk/ (ikut ter-clone dari git).
-- Harga dan stok di bawah adalah contoh, ubah lewat admin panel (Data Produk).

INSERT IGNORE INTO `kategori_produk` (`nama_kategori`, `deskripsi`)
VALUES ('Makanan', 'Aneka camilan dan makanan siap saji');

INSERT INTO `produk` (`kode_bunga`, `nama_bunga`, `kategori_id`, `harga_beli`, `harga_jual`, `stok`, `foto`, `deskripsi`, `is_active`)
SELECT v.kode, v.nama, k.id, v.beli, v.jual, v.stok, v.foto, v.deskripsi, 1
FROM `kategori_produk` k
JOIN (
  SELECT 'MKN-001' AS kode, 'Bruleebomb' AS nama, 12000.00 AS beli, 20000.00 AS jual, 50 AS stok,
         'bruleebomb.jpg' AS foto, 'Bruleebomb' AS deskripsi
  UNION ALL
  SELECT 'MKN-002', 'Chicken Cordon Bleu', 12000.00, 20000.00, 50,
         'chicken-cordon-bleu.jpg', 'Chicken cordon bleu'
  UNION ALL
  SELECT 'MKN-003', 'Lumpia Kornet Mozzarella', 10000.00, 18000.00, 50,
         'lumpia-kornet-mozzarella.jpg', 'Lumpia isi kornet dan mozzarella'
  UNION ALL
  SELECT 'MKN-004', 'Pizza Springroll', 10000.00, 18000.00, 50,
         'pizza-springroll.jpg', 'Springroll isi pizza'
) v
WHERE k.`nama_kategori` = 'Makanan'
ON DUPLICATE KEY UPDATE
  `nama_bunga` = VALUES(`nama_bunga`),
  `foto` = VALUES(`foto`),
  `kategori_id` = VALUES(`kategori_id`),
  `deskripsi` = VALUES(`deskripsi`),
  `is_active` = 1;
