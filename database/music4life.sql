-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2026 at 03:39 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `music4life`
--

-- --------------------------------------------------------

--
-- Table structure for table `dangkysukien`
--

CREATE TABLE `dangkysukien` (
  `ID` int(11) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `ID_SuKien` int(11) NOT NULL,
  `NgayDangKy` datetime NOT NULL DEFAULT current_timestamp(),
  `TrangThai` enum('da_dang_ky','da_huy') NOT NULL DEFAULT 'da_dang_ky',
  `MaVe` varchar(100) NOT NULL,
  `TrangThaiCheckIn` enum('chua_checkin','da_checkin') NOT NULL DEFAULT 'chua_checkin',
  `ThoiGianCheckIn` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dangkysukien`
--

INSERT INTO `dangkysukien` (`ID`, `ID_NguoiDung`, `ID_SuKien`, `NgayDangKy`, `TrangThai`, `MaVe`, `TrangThaiCheckIn`, `ThoiGianCheckIn`) VALUES
(1, 1, 2, '2026-11-10 09:15:51', 'da_dang_ky', 'c6aecf638af317e7313f52544ca2d054', 'chua_checkin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `danhgia`
--

CREATE TABLE `danhgia` (
  `ID` int(11) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `ID_SuKien` int(11) NOT NULL,
  `SoSao` tinyint(4) NOT NULL,
  `NoiDung` text DEFAULT NULL,
  `NgayDanhGia` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `danhmuc`
--

CREATE TABLE `danhmuc` (
  `ID` int(11) NOT NULL,
  `TenDanhMuc` varchar(100) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `TrangThai` enum('hoat_dong','an') NOT NULL DEFAULT 'hoat_dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danhmuc`
--

INSERT INTO `danhmuc` (`ID`, `TenDanhMuc`, `MoTa`, `TrangThai`) VALUES
(1, 'Cong nghe', 'Hoi thao va su kien cong nghe', 'hoat_dong'),
(2, 'Am nhac', 'Chuong trinh am nhac va bieu dien', 'hoat_dong'),
(3, 'Giao duc', 'Hoi thao va khoa hoc', 'hoat_dong'),
(4, 'The thao', 'Cac hoat dong the thao', 'hoat_dong');

-- --------------------------------------------------------

--
-- Table structure for table `datlai_matkhau`
--

CREATE TABLE `datlai_matkhau` (
  `ID` int(11) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `MaXacNhan` varchar(255) NOT NULL,
  `HetHan` datetime NOT NULL,
  `SoLanSai` tinyint(4) NOT NULL DEFAULT 0,
  `DaDung` tinyint(1) NOT NULL DEFAULT 0,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `datlai_matkhau`
--

INSERT INTO `datlai_matkhau` (`ID`, `ID_NguoiDung`, `MaXacNhan`, `HetHan`, `SoLanSai`, `DaDung`, `NgayTao`) VALUES
(1, 1, '$2y$12$lrg8NQI9TKj4HPnjLndAwuK5zKKKUCi6A2lZblkHlOtgcVQFWZVuu', '2026-11-07 14:29:25', 0, 1, '2026-11-07 14:19:25'),
(2, 1, '$2y$12$eBj5kMJvdo/fiO6sGPNjxOJGtFBqur22XtBKn/oZqLMg9xdWX6hS2', '2026-11-07 14:34:24', 0, 1, '2026-11-07 14:24:24'),
(3, 1, '$2y$12$0yi3.3Oo6nYk.jbtkX.lReoPq25DFNqJcSwc.yV2sVCZH8Q2V21d2', '2026-11-07 14:36:20', 0, 1, '2026-11-07 14:26:20'),
(4, 1, '$2y$12$iof2hLd/ud205bKKDCZVEeHA.4J4vvshWm/7pTMiq4UCIo7tnDRKe', '2026-11-08 11:25:34', 0, 1, '2026-11-08 11:15:34'),
(5, 1, '$2y$12$1lpGAxUdmzrMb4gQCqd9yu65yUSkt8UGAZZ27Y8e9jnlEFBiGNEIm', '2026-11-08 11:36:02', 0, 1, '2026-11-08 11:26:02'),
(6, 1, '$2y$12$BCgFs5VFi.ZAJZzCQY7Tr.cp5C48FdRSKM1oQr.kB1ioSHoKvQ9hW', '2026-11-08 11:38:25', 0, 1, '2026-11-08 11:28:25'),
(7, 1, '$2y$12$R5E4CYQ5uaeenn2rY.ovu.d535xWpgQwvRY94dRrMxI.nbEVPfPSa', '2026-11-08 11:41:14', 0, 1, '2026-11-08 11:31:14'),
(8, 1, '$2y$12$LWbGroNd8GGml8Scw0zFWOqo4bpTykWe0fPQyhuZ2pdlPOcnLmhyu', '2026-11-08 11:47:40', 0, 1, '2026-11-08 11:37:40'),
(9, 1, '$2y$12$bf5gi6953JbwYYocv0LWVuYVA/8HgU5qYbBXUq/pcPCh671EsjlG.', '2026-11-08 11:54:50', 0, 1, '2026-11-08 11:44:50'),
(10, 1, '$2y$12$jVoRdfC/UDdAxdvT.hn/D.111Ix1IKNAJu05JgVjKQdUyKfU3RKz6', '2026-11-08 11:58:36', 0, 1, '2026-11-08 11:48:36'),
(11, 1, '$2y$12$pukvW.2ycrB7n7SWu3ZDxeSkPWzdKvHXDNP0P2Y6llk7Uno5uIVGC', '2026-11-09 21:07:40', 0, 1, '2026-11-09 20:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `nguoidung`
--

CREATE TABLE `nguoidung` (
  `ID` int(11) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `MatKhau` varchar(255) NOT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `VaiTro` enum('user','organizer','admin') NOT NULL DEFAULT 'user',
  `TrangThai` enum('hoat_dong','khoa') NOT NULL DEFAULT 'hoat_dong',
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nguoidung`
--

INSERT INTO `nguoidung` (`ID`, `HoTen`, `Email`, `MatKhau`, `SoDienThoai`, `VaiTro`, `TrangThai`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 'Nguyen thi thanh truc', 'thaophuongtruc@gmail.com', '$2y$12$1uByPwUsZyDslMXY67XbVePQbmdLJx5SA4g5idFdrxgU5xN5yuqzu', NULL, 'organizer', 'hoat_dong', '2026-11-07 13:31:04', '2026-11-09 20:58:21'),
(2, 'Quan tri vien', 'admin@music4life.com', '$2y$12$ilej5itea2w5aXK827D23eXt113b0NreXM9hPNutt5ktndn5Mt.oO', NULL, 'admin', 'hoat_dong', '2026-11-07 13:47:54', '2026-11-07 13:47:54'),
(3, 'thuthu', 'thuthu@gmail.com', '$2y$12$KpthEcxcpMKuRg2sTBeJyu.UibxPbYoUNKzDKIrIvWzVa3xyWdmSO', NULL, 'user', 'khoa', '2026-11-09 19:36:47', '2026-11-09 20:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `nhatky_hoatdong`
--

CREATE TABLE `nhatky_hoatdong` (
  `ID` bigint(20) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `HanhDong` varchar(50) NOT NULL,
  `MoTa` varchar(255) DEFAULT NULL,
  `DiaChiIP` varchar(45) DEFAULT NULL,
  `ThoiGian` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nhatky_hoatdong`
--

INSERT INTO `nhatky_hoatdong` (`ID`, `ID_NguoiDung`, `HanhDong`, `MoTa`, `DiaChiIP`, `ThoiGian`) VALUES
(1, 3, 'bi_khoa', 'Thực hiện bởi admin ID 2', '::1', '2026-11-09 20:17:15'),
(2, 3, 'duoc_mo_khoa', 'Thực hiện bởi admin ID 2', '::1', '2026-11-09 20:21:59'),
(3, 3, 'bi_khoa', 'Thực hiện bởi admin ID 2', '::1', '2026-11-09 20:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `nhatky_truycap`
--

CREATE TABLE `nhatky_truycap` (
  `ID` bigint(20) NOT NULL,
  `ID_NguoiDung` int(11) DEFAULT NULL,
  `DiaChiIP` varchar(45) NOT NULL,
  `Trang` varchar(255) NOT NULL,
  `PhuongThuc` varchar(10) NOT NULL,
  `TrinhDuyet` varchar(255) DEFAULT NULL,
  `ThoiGian` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nhatky_truycap`
--

INSERT INTO `nhatky_truycap` (`ID`, `ID_NguoiDung`, `DiaChiIP`, `Trang`, `PhuongThuc`, `TrinhDuyet`, `ThoiGian`) VALUES
(1, 2, '::1', '/Quanlysukien/pages/admin/duyetdoivaitro.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:16:24'),
(2, 2, '::1', '/Quanlysukien/pages/admin/nguoidung.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:19:41'),
(3, 2, '::1', '/Quanlysukien/pages/admin/duyetdoivaitro.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:20:24'),
(4, 2, '::1', '/Quanlysukien/pages/admin/nguoidung.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:20:25'),
(5, 2, '::1', '/Quanlysukien/pages/admin/duyetdoivaitro.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:21:47'),
(6, 2, '::1', '/Quanlysukien/pages/admin/nguoidung.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:21:48'),
(7, 2, '::1', '/Quanlysukien/pages/admin/nguoidung.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:22:09'),
(8, 2, '::1', '/Quanlysukien/pages/admin/duyetdoivaitro.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:22:16'),
(9, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:22:18'),
(10, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'POST', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:22:27'),
(11, 1, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:44:04'),
(12, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:44:06'),
(13, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:44:24'),
(14, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:00'),
(15, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:01'),
(16, 1, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:02'),
(17, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:03'),
(18, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:04'),
(19, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:07'),
(20, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:08'),
(21, 1, '::1', '/Quanlysukien/pages/taikhoan/thongbao.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:36'),
(22, 1, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:46:41'),
(23, 1, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:47:59'),
(24, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:48:03'),
(25, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:48:11'),
(26, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:48:12'),
(27, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:48:19'),
(28, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:48:38'),
(29, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:49:05'),
(30, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:49:06'),
(31, 1, '::1', '/Quanlysukien/pages/taikhoan/doimatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:51:31'),
(32, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:51:35'),
(33, 1, '::1', '/Quanlysukien/pages/taikhoan/doimatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:51:38'),
(34, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:51:40'),
(35, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:52:07'),
(36, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:52:08'),
(37, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-11-09 20:52:52'),
(38, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:53:21'),
(39, 1, '::1', '/Quanlysukien/pages/taikhoan/doimatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:53:26'),
(40, 1, '::1', '/Quanlysukien/pages/taikhoan/doimatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:54:39'),
(41, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:54:43'),
(42, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:55:08'),
(43, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:55:28'),
(44, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:55:42'),
(45, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:55:49'),
(46, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:56:38'),
(47, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:56:51'),
(48, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:56:56'),
(49, 1, '::1', '/Quanlysukien/pages/taikhoan/doimatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:57:01'),
(50, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:57:03'),
(51, NULL, '::1', '/Quanlysukien/pages/taikhoan/quenmatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:57:06'),
(52, NULL, '::1', '/Quanlysukien/pages/taikhoan/quenmatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:57:31'),
(53, NULL, '::1', '/Quanlysukien/pages/taikhoan/datlaimatkhau.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:57:40'),
(54, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:58:21'),
(55, 1, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:58:31'),
(56, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 20:59:32'),
(57, 1, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:01:05'),
(58, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:01:07'),
(59, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:01:10'),
(60, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:16'),
(61, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:30'),
(62, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:30'),
(63, NULL, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:32'),
(64, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:35'),
(65, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangky.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:36'),
(66, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-09 21:02:39'),
(67, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:10:24'),
(68, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:10:32'),
(69, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:11:30'),
(70, 2, '::1', '/Quanlysukien/pages/admin/duyetdoivaitro.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:11:38'),
(71, 2, '::1', '/Quanlysukien/pages/admin/nguoidung.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:11:45'),
(72, 2, '::1', '/Quanlysukien/pages/admin/nguoidung.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:11:56'),
(73, 2, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:13:12'),
(74, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:13:15'),
(75, NULL, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:13:19'),
(76, NULL, '::1', '/Quanlysukien/pages/muave/muave.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:14:28'),
(77, NULL, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:14:32'),
(78, NULL, '::1', '/Quanlysukien/pages/taikhoan/dangnhap.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:14:36'),
(79, 1, '::1', '/Quanlysukien/pages/sukien/sukien.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:15:25'),
(80, 1, '::1', '/Quanlysukien/pages/taikhoan/kiem_thu_dang_ky.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:15:45'),
(81, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:15:51'),
(82, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:18:41'),
(83, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:20:18'),
(84, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:20:18'),
(85, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:22:15'),
(86, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:25:43'),
(87, 1, '::1', '/Quanlysukien/pages/taikhoan/ve_cua_toi.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:26:38'),
(88, 1, '::1', '/Quanlysukien/pages/taikhoan/taikhoan.php', 'GET', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 09:26:45');

-- --------------------------------------------------------

--
-- Table structure for table `sukien`
--

CREATE TABLE `sukien` (
  `ID` int(11) NOT NULL,
  `ID_DanhMuc` int(11) NOT NULL,
  `ID_NguoiTao` int(11) DEFAULT NULL,
  `TenSuKien` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `DiaDiem` varchar(255) NOT NULL,
  `ThoiGianBatDau` datetime NOT NULL,
  `ThoiGianKetThuc` datetime NOT NULL,
  `SoLuongToiDa` int(11) NOT NULL,
  `AnhSuKien` varchar(255) DEFAULT NULL,
  `TrangThai` enum('sap_dien_ra','dang_dien_ra','da_ket_thuc','da_huy') NOT NULL DEFAULT 'sap_dien_ra',
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sukien`
--

INSERT INTO `sukien` (`ID`, `ID_DanhMuc`, `ID_NguoiTao`, `TenSuKien`, `MoTa`, `DiaDiem`, `ThoiGianBatDau`, `ThoiGianKetThuc`, `SoLuongToiDa`, `AnhSuKien`, `TrangThai`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 1, NULL, 'Hoi thao Cong nghe 2026', 'Gioi thieu xu huong cong nghe moi', 'Can Tho', '2026-12-10 08:00:00', '2026-12-10 11:00:00', 100, NULL, 'sap_dien_ra', '2026-11-06 10:34:06', '2026-11-06 10:34:06'),
(2, 2, NULL, 'Dem nhac sinh vien', 'Chuong trinh giao luu am nhac', 'Can Tho', '2026-12-15 18:00:00', '2026-12-15 21:00:00', 200, NULL, 'sap_dien_ra', '2026-11-06 10:34:06', '2026-11-06 10:34:06');

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `ID` int(11) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `TieuDe` varchar(200) NOT NULL,
  `NoiDung` text NOT NULL,
  `LoaiThongBao` varchar(50) NOT NULL,
  `DaDoc` tinyint(1) NOT NULL DEFAULT 0,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`ID`, `ID_NguoiDung`, `TieuDe`, `NoiDung`, `LoaiThongBao`, `DaDoc`, `NgayTao`) VALUES
(1, 1, 'Yêu cầu làm nhà tổ chức đã được duyệt', 'Chúc mừng! Tài khoản của bạn đã trở thành nhà tổ chức. Bạn có thể bắt đầu đăng sự kiện.', 'organizer_duyet', 1, '2026-11-09 19:30:40'),
(2, 3, 'Yêu cầu làm nhà tổ chức bị từ chối', 'Rất tiếc, yêu cầu của bạn chưa được chấp nhận. Bạn có thể gửi lại yêu cầu mới với thông tin đầy đủ hơn.', 'organizer_tuchoi', 1, '2026-11-09 19:40:56');

-- --------------------------------------------------------

--
-- Table structure for table `yeucau_organizer`
--

CREATE TABLE `yeucau_organizer` (
  `ID` int(11) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `LyDo` text DEFAULT NULL,
  `TrangThai` enum('cho_duyet','da_duyet','tu_choi') NOT NULL DEFAULT 'cho_duyet',
  `NgayGui` datetime NOT NULL DEFAULT current_timestamp(),
  `NgayXuLy` datetime DEFAULT NULL,
  `ID_NguoiDuyet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `yeucau_organizer`
--

INSERT INTO `yeucau_organizer` (`ID`, `ID_NguoiDung`, `LyDo`, `TrangThai`, `NgayGui`, `NgayXuLy`, `ID_NguoiDuyet`) VALUES
(1, 1, 'Tao buoi fan meeting', 'tu_choi', '2026-11-09 18:31:41', '2026-11-09 18:37:31', 2),
(2, 1, '................', 'da_duyet', '2026-11-09 19:27:26', '2026-11-09 19:30:40', 2),
(3, 3, 'We are m4l with love, aim to make everyone happy with music', 'tu_choi', '2026-11-09 19:37:55', '2026-11-09 19:40:56', 2);

-- --------------------------------------------------------

--
-- Table structure for table `yeuthich`
--

CREATE TABLE `yeuthich` (
  `ID` int(11) NOT NULL,
  `ID_NguoiDung` int(11) NOT NULL,
  `ID_SuKien` int(11) NOT NULL,
  `NgayThem` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dangkysukien`
--
ALTER TABLE `dangkysukien`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `MaVe` (`MaVe`),
  ADD UNIQUE KEY `uq_nguoi_sukien` (`ID_NguoiDung`,`ID_SuKien`),
  ADD KEY `idx_dangky_sukien` (`ID_SuKien`),
  ADD KEY `idx_dangky_trangthai` (`TrangThai`),
  ADD KEY `idx_mave` (`MaVe`);

--
-- Indexes for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_danhgia` (`ID_NguoiDung`,`ID_SuKien`),
  ADD KEY `fk_danhgia_sukien` (`ID_SuKien`);

--
-- Indexes for table `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `TenDanhMuc` (`TenDanhMuc`);

--
-- Indexes for table `datlai_matkhau`
--
ALTER TABLE `datlai_matkhau`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_dl_nguoidung` (`ID_NguoiDung`,`DaDung`);

--
-- Indexes for table `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `nhatky_hoatdong`
--
ALTER TABLE `nhatky_hoatdong`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_nd_tg` (`ID_NguoiDung`,`ThoiGian`);

--
-- Indexes for table `nhatky_truycap`
--
ALTER TABLE `nhatky_truycap`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_nd_tg` (`ID_NguoiDung`,`ThoiGian`),
  ADD KEY `idx_tg` (`ThoiGian`);

--
-- Indexes for table `sukien`
--
ALTER TABLE `sukien`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_ten_sukien` (`TenSuKien`),
  ADD KEY `idx_dia_diem` (`DiaDiem`),
  ADD KEY `idx_thoi_gian` (`ThoiGianBatDau`),
  ADD KEY `idx_danh_muc` (`ID_DanhMuc`),
  ADD KEY `idx_trang_thai_sukien` (`TrangThai`),
  ADD KEY `idx_nguoi_tao` (`ID_NguoiTao`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_thongbao_nguoidung` (`ID_NguoiDung`,`DaDoc`);

--
-- Indexes for table `yeucau_organizer`
--
ALTER TABLE `yeucau_organizer`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_yc_nguoidung` (`ID_NguoiDung`,`TrangThai`),
  ADD KEY `idx_yc_trangthai` (`TrangThai`),
  ADD KEY `fk_yc_nguoiduyet` (`ID_NguoiDuyet`);

--
-- Indexes for table `yeuthich`
--
ALTER TABLE `yeuthich`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_yeuthich` (`ID_NguoiDung`,`ID_SuKien`),
  ADD KEY `fk_yeuthich_sukien` (`ID_SuKien`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dangkysukien`
--
ALTER TABLE `dangkysukien`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `danhgia`
--
ALTER TABLE `danhgia`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `datlai_matkhau`
--
ALTER TABLE `datlai_matkhau`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nhatky_hoatdong`
--
ALTER TABLE `nhatky_hoatdong`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nhatky_truycap`
--
ALTER TABLE `nhatky_truycap`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `sukien`
--
ALTER TABLE `sukien`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `yeucau_organizer`
--
ALTER TABLE `yeucau_organizer`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `yeuthich`
--
ALTER TABLE `yeuthich`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dangkysukien`
--
ALTER TABLE `dangkysukien`
  ADD CONSTRAINT `fk_dangky_nguoidung` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dangky_sukien` FOREIGN KEY (`ID_SuKien`) REFERENCES `sukien` (`ID`) ON UPDATE CASCADE;

--
-- Constraints for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD CONSTRAINT `fk_danhgia_nguoidung` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_danhgia_sukien` FOREIGN KEY (`ID_SuKien`) REFERENCES `sukien` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `datlai_matkhau`
--
ALTER TABLE `datlai_matkhau`
  ADD CONSTRAINT `fk_dl_nguoidung` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nhatky_hoatdong`
--
ALTER TABLE `nhatky_hoatdong`
  ADD CONSTRAINT `nhatky_hoatdong_ibfk_1` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `nhatky_truycap`
--
ALTER TABLE `nhatky_truycap`
  ADD CONSTRAINT `nhatky_truycap_ibfk_1` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE SET NULL;

--
-- Constraints for table `sukien`
--
ALTER TABLE `sukien`
  ADD CONSTRAINT `fk_sukien_danhmuc` FOREIGN KEY (`ID_DanhMuc`) REFERENCES `danhmuc` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sukien_nguoitao` FOREIGN KEY (`ID_NguoiTao`) REFERENCES `nguoidung` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `fk_thongbao_nguoidung` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `yeucau_organizer`
--
ALTER TABLE `yeucau_organizer`
  ADD CONSTRAINT `fk_yc_nguoidung` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_yc_nguoiduyet` FOREIGN KEY (`ID_NguoiDuyet`) REFERENCES `nguoidung` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `yeuthich`
--
ALTER TABLE `yeuthich`
  ADD CONSTRAINT `fk_yeuthich_nguoidung` FOREIGN KEY (`ID_NguoiDung`) REFERENCES `nguoidung` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_yeuthich_sukien` FOREIGN KEY (`ID_SuKien`) REFERENCES `sukien` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
