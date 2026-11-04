/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: posbunga
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB-ubu2204

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `detail_pembelian_stok`
--

DROP TABLE IF EXISTS `detail_pembelian_stok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_pembelian_stok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pembelian_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_beli` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pembelian_id` (`pembelian_id`),
  KEY `produk_id` (`produk_id`),
  CONSTRAINT `detail_pembelian_stok_ibfk_1` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelian_stok` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_pembelian_stok_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_pembelian_stok`
--

LOCK TABLES `detail_pembelian_stok` WRITE;
/*!40000 ALTER TABLE `detail_pembelian_stok` DISABLE KEYS */;
INSERT INTO `detail_pembelian_stok` VALUES
(1,1,7,100,25000.00,2500000.00),
(2,1,6,100,25000.00,2500000.00),
(3,1,12,2000,3000.00,6000000.00);
/*!40000 ALTER TABLE `detail_pembelian_stok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_transaksi`
--

DROP TABLE IF EXISTS `detail_transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaksi_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `harga_jual` decimal(12,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `diskon_item` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksi_id` (`transaksi_id`),
  KEY `produk_id` (`produk_id`),
  CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_transaksi`
--

LOCK TABLES `detail_transaksi` WRITE;
/*!40000 ALTER TABLE `detail_transaksi` DISABLE KEYS */;
INSERT INTO `detail_transaksi` VALUES
(1,1,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(2,2,10,'Anggrek Bulan Putih',180000.00,1,0.00,180000.00),
(3,2,14,'Mawar Hijau',5000.00,1,0.00,5000.00),
(4,3,14,'Mawar Hijau',5000.00,10,0.00,50000.00),
(5,4,16,'Mawar Layu',50000.00,1,0.00,50000.00),
(6,5,10,'Anggrek Bulan Putih',180000.00,1,0.00,180000.00),
(7,5,11,'Baby Breath',12000.00,1,0.00,12000.00),
(8,6,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(9,7,10,'Anggrek Bulan Putih',180000.00,1,0.00,180000.00),
(10,7,11,'Baby Breath',12000.00,1,0.00,12000.00),
(11,7,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(12,7,4,'Lily Putih',30000.00,1,0.00,30000.00),
(13,7,12,'Kertas Wrapping',8000.00,1,0.00,8000.00),
(14,7,8,'Buket Mawar Merah',150000.00,1,0.00,150000.00),
(15,7,5,'Lily Stargazer',40000.00,1,0.00,40000.00),
(16,7,17,'Mawar Hi',5000.00,1,0.00,5000.00),
(17,7,14,'Mawar Hijau',5000.00,1,0.00,5000.00),
(18,7,3,'Mawar Pink',17000.00,1,0.00,17000.00),
(19,7,1,'Mawar Merah',15000.00,1,0.00,15000.00),
(20,7,16,'Mawar Layu',50000.00,1,0.00,50000.00),
(21,8,10,'Anggrek Bulan Putih',180000.00,1,0.00,180000.00),
(22,8,11,'Baby Breath',12000.00,1,0.00,12000.00),
(23,8,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(24,9,11,'Baby Breath',12000.00,1,0.00,12000.00),
(25,10,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(26,11,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(27,11,11,'Baby Breath',12000.00,1,0.00,12000.00),
(28,12,8,'Buket Mawar Merah',150000.00,1,0.00,150000.00),
(29,12,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(30,13,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(31,14,8,'Buket Mawar Merah',150000.00,1,0.00,150000.00),
(32,14,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(33,15,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(34,16,11,'Baby Breath',12000.00,1,0.00,12000.00),
(35,16,9,'Buket Campuran',120000.00,1,0.00,120000.00),
(36,16,8,'Buket Mawar Merah',150000.00,1,0.00,150000.00);
/*!40000 ALTER TABLE `detail_transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_produk`
--

DROP TABLE IF EXISTS `kategori_produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori_produk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_produk`
--

LOCK TABLES `kategori_produk` WRITE;
/*!40000 ALTER TABLE `kategori_produk` DISABLE KEYS */;
INSERT INTO `kategori_produk` VALUES
(1,'Mawar','Berbagai jenis bunga mawar','2026-07-16 14:04:44'),
(2,'Lily','Bunga lily segar','2026-07-16 14:04:44'),
(3,'Tulip','Bunga tulip import','2026-07-16 14:04:44'),
(4,'Buket','Rangkaian buket campuran','2026-07-16 14:04:44'),
(5,'Anggrek','Bunga anggrek pot dan potong','2026-07-16 14:04:44'),
(6,'Aksesoris','Vas, pita, kertas wrapping, dll','2026-07-16 14:04:44');
/*!40000 ALTER TABLE `kategori_produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `laporan_artifacts`
--

DROP TABLE IF EXISTS `laporan_artifacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `laporan_artifacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `tab` varchar(50) NOT NULL,
  `periode_label` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `laporan_artifacts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `laporan_artifacts`
--

LOCK TABLES `laporan_artifacts` WRITE;
/*!40000 ALTER TABLE `laporan_artifacts` DISABLE KEYS */;
INSERT INTO `laporan_artifacts` VALUES
(2,'Laporan_Overview_20260720_091956.pdf','Laporan Penjualan - July 2026','overview','July 2026',9854,1,1,'2026-07-20 09:19:56');
/*!40000 ALTER TABLE `laporan_artifacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pelanggan`
--

DROP TABLE IF EXISTS `pelanggan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pelanggan`
--

LOCK TABLES `pelanggan` WRITE;
/*!40000 ALTER TABLE `pelanggan` DISABLE KEYS */;
/*!40000 ALTER TABLE `pelanggan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembelian_stok`
--

DROP TABLE IF EXISTS `pembelian_stok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembelian_stok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_pembelian` varchar(30) NOT NULL,
  `tanggal` timestamp NULL DEFAULT current_timestamp(),
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_pembelian` (`no_pembelian`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pembelian_stok_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`),
  CONSTRAINT `pembelian_stok_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembelian_stok`
--

LOCK TABLES `pembelian_stok` WRITE;
/*!40000 ALTER TABLE `pembelian_stok` DISABLE KEYS */;
INSERT INTO `pembelian_stok` VALUES
(1,'PO-20260717-001','2026-07-17 09:36:35',3,1,11000000.00,'','2026-07-17 09:36:35');
/*!40000 ALTER TABLE `pembelian_stok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan`
--

DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan`
--

LOCK TABLES `pengaturan` WRITE;
/*!40000 ALTER TABLE `pengaturan` DISABLE KEYS */;
INSERT INTO `pengaturan` VALUES
(1,'nama_toko','Toko Bunga Melati','2026-07-17 10:37:28'),
(2,'alamat_toko','Jl. Bunga Indah No. 123, Jakarta','2026-07-16 14:04:44'),
(3,'no_telp_toko','021-12345678','2026-07-16 14:04:44'),
(4,'pajak_persen','30','2026-07-17 10:39:22'),
(5,'stok_minimum_alert','5','2026-07-16 14:04:44'),
(6,'logo_toko','logo_toko.png','2026-07-17 10:03:28'),
(12,'tema_warna','','2026-07-17 10:37:19'),
(13,'mata_uang','Rp','2026-07-17 10:37:19'),
(14,'footer_struk','','2026-07-17 10:37:19'),
(31,'hero_title','Bunga Segar untuk Setiap Momen','2026-07-17 11:09:30'),
(32,'hero_subtitle','Bunga segar dipetik dari belakang rumah','2026-07-17 11:22:20'),
(33,'hero_bg','hero_bg.jpeg','2026-07-17 11:22:01'),
(40,'hero_text_color','#ffffff','2026-07-17 11:25:54');
/*!40000 ALTER TABLE `pengaturan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produk`
--

DROP TABLE IF EXISTS `produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `produk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_bunga` varchar(50) DEFAULT NULL,
  `nama_bunga` varchar(100) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `harga_beli` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_bunga` (`kode_bunga`),
  KEY `kategori_id` (`kategori_id`),
  CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_produk` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produk`
--

LOCK TABLES `produk` WRITE;
/*!40000 ALTER TABLE `produk` DISABLE KEYS */;
INSERT INTO `produk` VALUES
(1,'BNG-001','Mawar Merah',1,8000.00,15000.00,49,'produk_1784279814_6a59f3067392b.webp','Mawar merah segar per tangkai',1,'2026-07-16 14:04:44','2026-07-17 09:16:54'),
(2,'BNG-002','Mawar Putih',1,8000.00,15000.00,30,'produk_1784279791_6a59f2ef11668.png','Mawar putih per tangkai',1,'2026-07-16 14:04:44','2026-07-17 09:16:31'),
(3,'BNG-003_DEL_1784279689','Mawar Pink',1,9000.00,17000.00,24,NULL,'Mawar pink per tangkai',0,'2026-07-16 14:04:44','2026-07-17 09:14:49'),
(4,'BNG-004','Lily Putih',2,15000.00,30000.00,9,'produk_1784279560_6a59f2082da96.webp','Lily putih per tangkai',1,'2026-07-16 14:04:44','2026-07-17 09:12:40'),
(5,'BNG-005','Lily Stargazer',2,20000.00,40000.00,7,'produk_1784279676_6a59f27ca3d9f.webp','Lily stargazer per tangkai',1,'2026-07-16 14:04:44','2026-07-17 09:14:36'),
(6,'BNG-006','Tulip Kuning',3,25000.00,50000.00,110,'produk_1784279751_6a59f2c7cd10a.png','Tulip kuning import dari Rawamangun',1,'2026-07-16 14:04:44','2026-07-17 09:36:35'),
(7,'BNG-007','Tulip Merah',3,25000.00,50000.00,107,'produk_1784279715_6a59f2a33db03.jpeg','Tulip merah import',1,'2026-07-16 14:04:44','2026-07-17 09:36:35'),
(8,'BNG-008','Buket Mawar Merah',4,50000.00,150000.00,6,'Buket Mawar Merah .webp','Buket 20 tangkai mawar merah + wrapping',1,'2026-07-16 14:04:44','2026-07-24 12:45:26'),
(9,'BNG-009','Buket Campuran',4,45000.00,120000.00,7,'Buket Campuran .webp','Buket campuran lily + mawar + baby breath',1,'2026-07-16 14:04:44','2026-07-24 12:45:26'),
(10,'BNG-010','Anggrek Bulan Putih',5,75000.00,180000.00,47,'Anggrek Bulan Putih .jpg','Anggrek bulan putih dalam pot',1,'2026-07-16 14:04:44','2026-07-17 09:17:23'),
(11,'BNG-011','Baby Breath',6,5000.00,12000.00,34,'Baby Breath .jpg','Baby breath per ikat',1,'2026-07-16 14:04:44','2026-07-24 12:45:26'),
(12,'BNG-012','Kertas Wrapping',6,3000.00,8000.00,2099,'Kertas Wrapping.webp','Kertas wrapping premium per lembar',1,'2026-07-16 14:04:44','2026-07-17 09:36:35'),
(13,'BNG-101_DEL_1784214426','Mawar Hijau',5,3000.00,5000.00,20,NULL,'',0,'2026-07-16 14:54:14','2026-07-16 15:07:06'),
(14,'BNG-100_DEL_1784279684','Mawar Hijau',1,2000.00,5000.00,18,NULL,'',0,'2026-07-16 14:54:50','2026-07-17 09:14:44'),
(16,'BNG-505','Mawar Layu',1,3000.00,50000.00,30,'produk_1784214213_6a58f2c595140.png','',1,'2026-07-16 15:03:33','2026-07-17 10:25:56'),
(17,'BNG-101_DEL_1784279681','Mawar Hi',1,3000.00,5000.00,29,NULL,'',0,'2026-07-16 15:08:33','2026-07-17 09:14:41');
/*!40000 ALTER TABLE `produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating_produk`
--

DROP TABLE IF EXISTS `rating_produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rating_produk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produk_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`produk_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `rating_produk_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  CONSTRAINT `rating_produk_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rating_produk`
--

LOCK TABLES `rating_produk` WRITE;
/*!40000 ALTER TABLE `rating_produk` DISABLE KEYS */;
INSERT INTO `rating_produk` VALUES
(1,16,2,5,'2026-07-16 15:03:50'),
(2,10,2,5,'2026-07-16 15:08:48'),
(3,11,2,5,'2026-07-16 17:52:30'),
(4,9,2,1,'2026-07-17 11:43:35'),
(5,8,1,5,'2026-07-17 12:25:02'),
(6,7,1,3,'2026-07-17 12:38:49'),
(7,6,1,2,'2026-07-17 12:38:51'),
(8,2,1,4,'2026-07-17 12:38:53'),
(9,1,1,5,'2026-07-17 12:38:54');
/*!40000 ALTER TABLE `rating_produk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kontak_person` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier`
--

LOCK TABLES `supplier` WRITE;
/*!40000 ALTER TABLE `supplier` DISABLE KEYS */;
INSERT INTO `supplier` VALUES
(1,'PT Flora Indah','021-5551234','flora@indah.com','Jl. Kebun Bunga No. 10, Bandung','Pak Dedi','2026-07-17 09:33:06','2026-07-17 09:33:06'),
(2,'CV Segar Bunga Nusantara','0812-9876-5432','segar@bunganusantara.id','Jl. Pasar Bunga Rawa Belong, Jakarta','Ibu Ratna','2026-07-17 09:33:06','2026-07-17 09:33:06'),
(3,'UD Tulip Sejahtera','0856-1122-3344','tulipsejahtera@gmail.com','Jl. Import Bunga No. 5, Surabaya','Pak Herman','2026-07-17 09:33:06','2026-07-17 09:33:06'),
(4,'CV Bunga Layu Abadi','0811111111','email@email.com','Samping pagar dekat tiang listrik','Pot depan rumah','2026-07-17 11:53:12','2026-07-17 11:53:12');
/*!40000 ALTER TABLE `supplier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(30) NOT NULL,
  `tanggal` timestamp NULL DEFAULT current_timestamp(),
  `pelanggan_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pajak_persen` decimal(5,2) NOT NULL DEFAULT 0.00,
  `pajak_nominal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `diskon` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metode_bayar` enum('cash','qris','transfer','creditcard') NOT NULL DEFAULT 'cash',
  `jumlah_bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kembalian` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','paid','packing','shipping','delivered','done','batal') NOT NULL DEFAULT 'pending',
  `nama_penerima` varchar(100) DEFAULT NULL,
  `telp_penerima` varchar(20) DEFAULT NULL,
  `alamat_pengiriman` text DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  KEY `pelanggan_id` (`pelanggan_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`),
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
INSERT INTO `transaksi` VALUES
(1,'TRX-20260716-001','2026-07-16 14:07:50',NULL,2,120000.00,0.00,0.00,0.00,120000.00,'creditcard',120000.00,0.00,'samping pot','done','Gerbang depan','08888888','Depan gerbang','PAY-7D5429EEDC','2026-07-16 14:07:50','2026-07-16 14:31:13'),
(2,'TRX-20260716-002','2026-07-16 14:55:37',NULL,2,185000.00,0.00,0.00,0.00,185000.00,'creditcard',185000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-A1A355D80C','2026-07-16 14:55:37','2026-07-16 15:01:29'),
(3,'TRX-20260716-003','2026-07-16 15:00:19',NULL,2,50000.00,0.00,0.00,0.00,50000.00,'creditcard',50000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-9EDBBD8E3D','2026-07-16 15:00:19','2026-07-16 15:01:06'),
(4,'TRX-20260716-004','2026-07-16 15:03:56',NULL,2,50000.00,0.00,0.00,0.00,50000.00,'creditcard',50000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-F95F64AE42','2026-07-16 15:03:56','2026-07-16 15:08:12'),
(5,'TRX-20260716-005','2026-07-16 15:13:46',NULL,2,192000.00,0.00,0.00,0.00,192000.00,'creditcard',192000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-2566A74D0C','2026-07-16 15:13:46','2026-07-16 17:53:27'),
(6,'TRX-20260716-006','2026-07-16 17:03:57',NULL,2,120000.00,0.00,0.00,0.00,120000.00,'creditcard',120000.00,0.00,'','batal','Gerbang','0888888888','Gerbang Depan','PAY-7678E2D549','2026-07-16 17:03:57','2026-07-16 17:53:39'),
(7,'TRX-20260716-007','2026-07-16 17:53:12',NULL,2,632000.00,0.00,0.00,0.00,632000.00,'creditcard',632000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-AD89CA0256','2026-07-16 17:53:12','2026-07-16 17:53:50'),
(8,'TRX-20260717-001','2026-07-17 09:17:23',NULL,2,312000.00,0.00,0.00,0.00,312000.00,'creditcard',312000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-5FDCF53A26','2026-07-17 09:17:23','2026-07-17 11:45:59'),
(9,'TRX-20260717-002','2026-07-17 10:12:58',NULL,2,12000.00,0.00,0.00,0.00,12000.00,'creditcard',12000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-966ECD2C22','2026-07-17 10:12:58','2026-07-17 10:32:00'),
(10,'TRX-20260717-003','2026-07-17 10:30:30',NULL,2,120000.00,0.00,0.00,0.00,120000.00,'creditcard',120000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-B41C3692F8','2026-07-17 10:30:30','2026-07-17 11:46:06'),
(11,'TRX-20260717-004','2026-07-17 11:39:39',NULL,2,132000.00,30.00,39600.00,0.00,171600.00,'creditcard',171600.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-B25BB5C073','2026-07-17 11:39:39','2026-07-17 11:46:14'),
(12,'TRX-20260717-005','2026-07-17 11:40:03',NULL,2,270000.00,30.00,81000.00,0.00,351000.00,'creditcard',351000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-4B4E8E0E14','2026-07-17 11:40:03','2026-07-17 11:46:22'),
(13,'TRX-20260717-006','2026-07-17 11:44:12',NULL,2,120000.00,30.00,36000.00,0.00,156000.00,'creditcard',156000.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-24753E6CC6','2026-07-17 11:44:12','2026-07-17 11:44:54'),
(14,'TRX-20260717-007','2026-07-17 12:25:19',NULL,1,270000.00,30.00,81000.00,0.00,351000.00,'creditcard',351000.00,0.00,'','done','Administrator','081111','pintu belakang','PAY-1137932156','2026-07-17 12:25:19','2026-07-17 12:25:58'),
(15,'TRX-20260720-001','2026-07-20 09:54:12',NULL,1,120000.00,30.00,36000.00,0.00,156000.00,'creditcard',156000.00,0.00,'','done','Sandal','08888888','Gerbang belakang','PAY-18440F7E48','2026-07-20 09:54:12','2026-07-24 12:49:59'),
(16,'TRX-20260724-001','2026-07-24 12:45:26',NULL,2,282000.00,30.00,84600.00,0.00,366600.00,'creditcard',366600.00,0.00,'','done','Gerbang','0888888888','Gerbang Depan','PAY-626598D5D6','2026-07-24 12:45:26','2026-07-24 12:49:56');
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `role` enum('admin','kasir') NOT NULL DEFAULT 'kasir',
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_penerima_default` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'sandal','$2y$10$ukkV1O5lWLBarhjYJUuuIubA6Vh77KBBRA3xCuc.ZD30EGTLDYz/q','Administrator',NULL,'admin',NULL,NULL,NULL,1,'2026-07-16 14:04:44','2026-07-16 14:04:44'),
(2,'user','$2y$10$4TA8iZKIVowd80KNyxI8r.H0XHTZ5tjDeQyMq06OjrqhWZo5q2kfy','user',NULL,'kasir','0888888888','Gerbang Depan','Gerbang',1,'2026-07-16 14:04:44','2026-07-17 09:26:46'),
(3,'kayu','$2y$10$EribnVNRbYI935RiedP5o.VR6xrRlj1AiVpUxXjOWOPmM.LAlpCl.','kayu',NULL,'kasir','08888111111','Belakang','Kebon',1,'2026-07-25 05:41:14','2026-07-25 05:41:33');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10  7:36:17
