-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2025 at 12:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thuctapdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `baocao`
--

CREATE TABLE `baocao` (
  `ID` int(11) NOT NULL,
  `IDSV` INT DEFAULT NULL,
  `IdGVHD` INT DEFAULT NULL,
  `Tuan` varchar(10) DEFAULT NULL,
  `CongviecThucHien` text DEFAULT NULL,
  `DanhGia` text DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `canbokhoa`
--

CREATE TABLE `canbokhoa` (
  `ID_TaiKhoan` INT NOT NULL,
  `Ten` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Email` varchar(250) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `canbokhoa`
--

INSERT INTO `canbokhoa` (`ID_TaiKhoan`, `Ten`, `Email`, `TrangThai`) VALUES
('1', 'Lê Viết Hoàng Nguyên', '', 1),
('2', 'Nguyễn Quốc Duy', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cauhoikhaosat`
--

CREATE TABLE `cauhoikhaosat` (
  `ID` int(11) NOT NULL,
  `ID_KhaoSat` int(11) DEFAULT NULL,
  `NoiDung` text DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cauhoikhaosat`
--

INSERT INTO `cauhoikhaosat` (`ID`, `ID_KhaoSat`, `NoiDung`, `TrangThai`) VALUES
(1, 6, 'Ăn cơm chưa?', 1),
(2, 7, 'Ăn cơm chưa?', 1),
(3, 8, 'Ăn cơm chưa?', 1),
(4, 9, 'Ăn cơm chưa?', 1),
(5, 9, 'Lúc mấy giờ?', 1),
(14, 15, 'Uống mấy lít?', 1),
(15, 15, 'Thiệt không', 1),
(16, 16, 'Uống mấy lít?', 1),
(17, 17, 'Uống mấy lít?', 1),
(18, 18, 'Uống mấy lít?', 1),
(19, 18, 'Thiệt không?', 1),
(20, 19, 'Uống mấy lít?', 1),
(21, 19, 'Thiệt không?', 1),
(22, 20, 'có hd chưa?', 1),
(23, 21, 'có hd chưa?', 1),
(24, 22, 'có hd chưa?', 1),
(25, 23, 'có hd chưa?', 1),
(26, 24, 'Ăn cơm chưa?', 1),
(27, 25, 'Ăn cơm chưa?', 1),
(28, 26, 'Ăn cơm chưa?', 1),
(29, 27, 'Ăn cơm chưa?', 1),
(30, 28, 'Ăn cơm chưa?', 1),
(31, 29, 'Ăn cơm chưa?', 1),
(32, 30, 'Ăn cơm chưa?', 1),
(33, 31, 'Ăn cơm chưa?', 1),
(34, 32, 'Ăn cơm chưa?', 1),
(35, 32, 'Uống mấy lít?', 1),
(36, 33, 'Ăn cơm chưa?', 1),
(37, 33, 'Ăn cơm chưa?', 1),
(38, 33, 'Uống mấy lít?', 1),
(39, 33, 'có hd chưa?', 1),
(40, 33, 'Uống mấy lít?', 1),
(41, 33, 'Uống mấy lít?', 1),
(42, 33, 'Ăn cơm chưa?', 1),
(43, 33, 'Uống mấy lít?', 1),
(44, 33, 'Ăn cơm chưa?', 1),
(45, 33, 'Ăn cơm chưa?', 1),
(46, 33, 'Uống mấy lít?', 1),
(47, 33, 'Ăn cơm chưa?', 1),
(48, 33, 'Uống mấy lít?', 1),
(49, 33, 'có hd chưa?', 1),
(50, 33, 'Ăn cơm chưa?', 1),
(51, 33, 'Uống mấy lít?', 1),
(52, 33, 'có hd chưa?', 1),
(53, 35, 'Ăn cơm chưa?', 1),
(54, 36, 'Ăn cơm chưa?', 1),
(55, 37, 'Ăn cơm chưa?', 1),
(56, 38, 'Ăn cơm chưa?', 1),
(57, 39, 'Ăn cơm chưa?', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cautraloi`
--

CREATE TABLE `cautraloi` (
  `ID` int(11) NOT NULL,
  `ID_PhanHoi` int(11) DEFAULT NULL,
  `ID_CauHoi` int(11) DEFAULT NULL,
  `TraLoi` text DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cautraloi`
--

INSERT INTO `cautraloi` (`ID`, `ID_PhanHoi`, `ID_CauHoi`, `TraLoi`, `TrangThai`) VALUES
(1, 2, 1, 'ăn rồi', 1),
(2, 3, 1, 'Chưa ăn', 1),
(3, 4, 4, 'Ăn rồi', 1),
(4, 4, 5, '10h', 1),
(5, 15, 2, 'Chưa ăn', 1),
(6, 16, 14, '10l', 1),
(7, 16, 15, 'không', 1),
(8, 17, 16, '10l', 1),
(9, 18, 20, '1', 1),
(10, 18, 21, 'Thiệt', 1),
(11, 19, 17, '100l', 1),
(12, 20, 17, '100l', 1),
(13, 21, 3, 'Chưa ăn', 1),
(14, 22, 1, 'ăn rồi', 1),
(15, 23, 3, 'ăn rồi', 1),
(16, 24, 17, '100l', 1),
(17, 25, 23, 'dạ chưa', 1),
(18, 26, 24, 'dạ rồi', 1),
(19, 27, 22, 'Có rồi', 1),
(20, 28, 18, '1', 1),
(21, 28, 19, 'thiệt', 1),
(22, 29, 26, 'ok', 1),
(23, 31, 27, 'rồi', 1),
(24, 32, 28, 'ok', 1),
(25, 33, 29, 'q', 1),
(26, 34, 30, 'a', 1),
(27, 36, 32, 'Chưa ăn', 1),
(28, 37, 54, 'a', 1);

-- --------------------------------------------------------

--
-- Table structure for table `congty`
--

CREATE TABLE `congty` (
  `ID` int(11) NOT NULL,
  `MaSoThue` varchar(250) DEFAULT NULL,
  `TenCty` varchar(250) DEFAULT NULL,
  `LinhVuc` varchar(250) DEFAULT NULL,
  `Sdt` varchar(50) DEFAULT NULL,
  `Email` varchar(250) DEFAULT NULL,
  `DiaChi` text DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dotthuctap`
--

CREATE TABLE `dotthuctap` (
  `ID` int(11) NOT NULL,
  `TenDot` varchar(50) DEFAULT NULL,
  `Nam` varchar(5) DEFAULT NULL,
  `Loai` varchar(25) DEFAULT NULL,
  `NguoiQuanLy` varchar(255) DEFAULT NULL,
  `ThoiGianBatDau` date DEFAULT NULL,
  `ThoiGianKetThuc` date DEFAULT NULL,
  `TenNguoiMoDot` varchar(255) DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dotthuctap`
--

INSERT INTO `dotthuctap` (`ID`, `TenDot`, `Nam`, `Loai`, `NguoiQuanLy`, `ThoiGianBatDau`, `ThoiGianKetThuc`, `TenNguoiMoDot`, `TrangThai`) VALUES
(43, 'CĐTH21-1', '2021', 'Cao đẳng ngành', 'Lữ Cao Tiến', '2021-06-16', '2021-07-02', 'Lữ Cao Tiến', 0),
(44, 'CĐTH21-2', '2021', 'Cao đẳng', 'Lữ Cao Tiến', '2021-06-08', '2021-07-08', 'Lữ Cao Tiến', 0),
(45, 'CĐTH22-1', '2022', 'Cao Đẳng', 'Lữ Cao Tiến', '2022-06-08', '2022-07-08', 'Lữ Cao Tiến', 0),
(46, 'CĐTH20-1', '2020', 'Cao Đẳng', 'Nguyễn Thị Thanh Thuận', '2020-06-02', '2020-07-15', 'Lữ Cao Tiến', 0),
(47, 'CĐTH23-1', '2023', 'Cao Đẳng', 'Lữ Cao Tiến', '2023-06-07', '2023-08-17', 'Nguyễn Quốc Duy', 0),
(48, 'CĐTH22-2', '2022', 'Cao Đẳng Ngành', 'Lữ Cao Tiến', '2022-06-15', '2022-07-06', 'Lữ Cao Tiến', 0),
(49, 'CĐTH25-2', '2025', 'Cao Đẳng', 'Lữ Cao Tiến', '2022-06-08', '2022-08-08', 'Lữ Cao Tiến', 0),
(50, 'CĐTH219-1', '2019', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2019-06-05', '2025-07-08', 'Nguyễn Quốc Duy', 2),
(51, 'CĐTH09-1', '2009', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2009-06-14', '2009-07-08', 'Nguyễn Quốc Duy', 0),
(52, 'CĐTH09-2', '2009', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2009-06-03', '2009-07-08', 'Nguyễn Quốc Duy', 0),
(53, 'CĐTH10-1', '2010', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2010-06-05', '2010-07-08', 'Nguyễn Quốc Duy', 0),
(54, 'CĐTH10-2', '2010', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2010-06-11', '2010-07-08', 'Nguyễn Quốc Duy', 0),
(55, 'CĐTH11-1', '2011', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2011-06-17', '2011-07-08', 'Nguyễn Quốc Duy', 0),
(57, 'CĐTH12-1', '2012', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2012-06-03', '2012-07-08', 'Nguyễn Quốc Duy', 0),
(58, 'CĐTH12-2', '2012', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2012-06-10', '2012-07-08', 'Nguyễn Quốc Duy', 0),
(59, 'CĐTH13-1', '2013', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2013-06-17', '2013-07-08', 'Nguyễn Quốc Duy', 0),
(60, 'CĐTH13-2', '2013', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2013-06-10', '2013-07-08', 'Nguyễn Quốc Duy', 0),
(61, 'CĐTH14-1', '2014', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2014-06-02', '2014-07-08', 'Nguyễn Quốc Duy', 0),
(62, 'CĐTH14-2', '2014', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2014-06-09', '2014-07-08', 'Nguyễn Quốc Duy', 0),
(63, 'CĐTH15-1', '2015', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2015-06-09', '2015-07-08', 'Nguyễn Quốc Duy', 0),
(64, 'CĐTH15-2', '2015', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2015-06-09', '2015-07-08', 'Nguyễn Quốc Duy', 0),
(65, 'CĐTH16-1', '2016', 'Cao Đẳng', 'Nguyễn Quốc Duy', '2016-06-09', '2016-07-08', 'Nguyễn Quốc Duy', 0),
(66, 'CĐTH16-2', '2016', 'Cao Đẳng', 'Nguyễn Thị Thanh Thuận', '2016-06-02', '2016-07-08', 'Nguyễn Thị Thanh Thuận', 0),
(67, 'CĐTH17-1', '2017', 'Cao Đẳng', 'Nguyễn Thị Thanh Thuận', '2017-06-09', '2017-07-08', 'Nguyễn Thị Thanh Thuận', 0),
(68, 'CĐTH17-2', '2017', 'Cao Đẳng', 'Nguyễn Thị Thanh Thuận', '2017-06-03', '2017-07-08', 'Nguyễn Thị Thanh Thuận', 0),
(69, 'CĐTH18-1', '2018', 'Cao Đẳng', 'Nguyễn Thị Thanh Thuận', '2018-06-10', '2018-07-08', 'Nguyễn Thị Thanh Thuận', 0),
(70, 'CĐTH19-2', '2019', 'Cao Đẳng', 'Nguyễn Thị Thanh Thuận', '2019-06-10', '2019-07-08', 'Nguyễn Quốc Duy', 0),
(71, 'CĐTH25-1', '2025', 'Cao đẳng ngành', 'Lữ Cao Tiến', '2025-06-20', '2025-08-12', 'Lữ Cao Tiến', 1),
(72, 'CĐTH25-3', '2025', 'Cao đẳng', 'Lữ Cao Tiến', '2025-06-12', '2025-07-12', 'Lữ Cao Tiến', 2),
(73, 'CĐTH25-4', '2025', 'Cao đẳng', 'Lữ Cao Tiến', '2025-06-19', '2025-07-18', 'Lữ Cao Tiến', 1);

-- --------------------------------------------------------

--
-- Table structure for table `file`
--

CREATE TABLE `file` (
  `ID` int(11) NOT NULL,
  `TenFile` varchar(30) DEFAULT NULL,
  `File` blob DEFAULT NULL,
  `ID_SV` INT DEFAULT NULL,
  `ID_GVHD` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giaovien`
--

CREATE TABLE `giaovien` (
  `ID_TaiKhoan` INT NOT NULL,
  `Ten` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Email` varchar(250) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giaovien`
--

INSERT INTO `giaovien` (`ID_TaiKhoan`, `Ten`, `Email`, `TrangThai`) VALUES
('6', 'Lữ Cao Tiến', 'lctien@caothang.edu.vn', 1),
('7', 'Nguyễn Quốc Duy', 'nqduy@caothamg.edu.vn', 1);

-- --------------------------------------------------------

--
-- Table structure for table `giaygioithieu`
--

CREATE TABLE `giaygioithieu` (
  `ID` int(11) NOT NULL,
  `TenCty` varchar(250) DEFAULT NULL,
  `MaSoThue` varchar(250) DEFAULT NULL,
  `LinhVuc` varchar(250) DEFAULT NULL,
  `Sdt` varchar(50) DEFAULT NULL,
  `Email` varchar(250) DEFAULT NULL,
  `DiaChi` text DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `IdSinhVien` INT DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `khaosat`
--

CREATE TABLE `khaosat` (
  `ID` int(11) NOT NULL,
  `TieuDe` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `NguoiNhan` varchar(50) NOT NULL,
  `NguoiTao`INT DEFAULT NULL,
  `ThoiGianTao` datetime DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khaosat`
--

INSERT INTO `khaosat` (`ID`, `TieuDe`, `MoTa`, `NguoiNhan`, `NguoiTao`, `ThoiGianTao`, `TrangThai`) VALUES
(6, 'Khảo sát số 1', 'Không có mô tả', 'Tất cả', '1', '2025-06-12 14:56:39', 1),
(7, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-12 14:57:51', 1),
(8, 'Khảo sát số 1', 'Không có mô tả', 'Giáo viên', '1', '2025-06-12 14:58:02', 1),
(9, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-12 14:59:14', 1),
(15, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên', '1', '2025-06-12 20:08:33', 1),
(16, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên', '1', '2025-06-12 20:10:32', 1),
(17, 'Uống nước chưa?', 'Hỏi Thăm', 'Tất cả', '1', '2025-06-12 20:10:42', 1),
(18, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên', '1', '2025-06-12 20:46:47', 1),
(19, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên', '6', '2025-06-12 20:50:29', 1),
(20, 'hướng dẫn', 'Hỏi Thăm', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 11:22:49', 1),
(21, 'hướng dẫn', 'Hỏi Thăm', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 11:24:46', 1),
(22, 'hướng dẫn', 'Hỏi Thăm', 'Sinh viên', '1', '2025-06-13 11:28:17', 1),
(23, 'hướng dẫn', 'Hỏi Thăm', 'Sinh viên', '1', '2025-06-13 11:49:30', 1),
(24, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 11:50:38', 1),
(25, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:11:17', 1),
(26, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:12:00', 1),
(27, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:14:18', 1),
(28, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:16:01', 1),
(29, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:19:37', 0),
(30, 'Khảo sát số 1', 'Không có mô tả', 'Giáo viên', '1', '2025-06-13 12:19:54', 1),
(31, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:24:43', 1),
(32, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:26:16', 1),
(33, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên', '1', '2025-06-13 12:33:06', 1),
(34, 'Khảo sát số 1', 'Không có mô tả', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 12:39:15', 1),
(35, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 12:45:06', 1),
(36, 'Uống nước chưa?', 'Hỏi Thăm', 'Giáo viên', '1', '2025-06-13 12:45:23', 1),
(37, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 12:45:34', 1),
(38, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 12:47:06', 0),
(39, 'Uống nước chưa?', 'Hỏi Thăm', 'Sinh viên thuộc hướng dẫn', '6', '2025-06-13 13:18:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `phanhoikhaosat`
--

CREATE TABLE `phanhoikhaosat` (
  `ID` int(11) NOT NULL,
  `ID_KhaoSat` int(11) DEFAULT NULL,
  `ID_TaiKhoan` INT DEFAULT NULL,
  `ThoiGianTraLoi` datetime DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phanhoikhaosat`
--

INSERT INTO `phanhoikhaosat` (`ID`, `ID_KhaoSat`, `ID_TaiKhoan`, `ThoiGianTraLoi`, `TrangThai`) VALUES
(2, 6, NULL, '2025-06-12 19:31:22', 1),
(3, 6, '3', '2025-06-12 19:41:28', 1),
(4, 9, '3', '2025-06-12 19:51:14', 1),
(15, 7, '3', '2025-06-12 20:09:28', 1),
(16, 15, '3', '2025-06-12 20:09:50', 1),
(17, 16, '3', '2025-06-12 20:50:56', 1),
(18, 19, '3', '2025-06-12 20:51:10', 1),
(19, 17, '3', '2025-06-12 20:55:19', 1),
(20, 17, '3', '2025-06-12 20:58:10', 1),
(21, 8, '3', '2025-06-12 20:58:20', 1),
(22, 6, '6', '2025-06-12 21:02:30', 1),
(23, 8, '6', '2025-06-12 21:02:36', 1),
(24, 17, '6', '2025-06-12 21:02:44', 1),
(25, 21, '3', '2025-06-13 11:33:34', 1),
(26, 22, '3', '2025-06-13 11:43:16', 1),
(27, 20, '3', '2025-06-13 11:44:54', 1),
(28, 18, '3', '2025-06-13 11:48:22', 1),
(29, 24, '3', '2025-06-13 11:51:23', 1),
(30, 23, '3', '2025-06-13 12:03:04', 1),
(31, 25, '3', '2025-06-13 12:11:26', 1),
(32, 26, '3', '2025-06-13 12:12:08', 1),
(33, 27, '3', '2025-06-13 12:14:33', 1),
(34, 28, '3', '2025-06-13 12:16:09', 1),
(35, 32, '3', '2025-06-13 12:29:26', 1),
(36, 30, '6', '2025-06-13 12:41:10', 1),
(37, 36, '6', '2025-06-13 13:18:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sinhvien`
--

CREATE TABLE `sinhvien` (
  `ID_TaiKhoan` INT NOT NULL,
  `ID_Dot` int(11) DEFAULT NULL,
  `Ten` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `Lop` varchar(50) DEFAULT NULL,
  `XepLoai` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `MSSV` varchar(12) DEFAULT NULL,
  `ID_GVHD` INT DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sinhvien`
--

INSERT INTO `sinhvien` (`ID_TaiKhoan`, `ID_Dot`, `Ten`, `Lop`, `XepLoai`, `MSSV`, `ID_GVHD`, `TrangThai`) VALUES
('10', 71, 'Nguyễn Thanh Sang', 'CĐTH21DĐ', NULL, '0306211365', '7', 1),
('11', 71, 'Nguyễn Hoàng Anh', 'CĐTH21MMT', NULL, '0306211135', NULL, 1),
('12', 71, 'Nguyễn Hoàng văn', 'CĐTH21MMT', NULL, '0306211199', NULL, 1),
('13', 71, 'Lương Văn Khoa', 'CĐTH21MMT', NULL, '0306211188', NULL, 1),
('14', 71, 'Đoàn Di Băng', 'CĐTH21WEBA', NULL, '0306211108', NULL, 1),
('3', 71, 'Đặng Minh Quân', 'CĐTH21DĐ', 'Xuất sắc', '0306211181', '6', 1),
('4', 71, 'Nguyễn Thanh Kiệt', 'CĐTH21DĐ', NULL, '0306211159', NULL, 1),
('5', 71, 'Nguyễn Hoàng Nguyên', 'CĐTH21WEBC', NULL, '0306211153', NULL, 1),
('8', 71, 'Nguyễn Hoàng Nguyên', 'CĐTH21WEBC', NULL, '0306211186', NULL, 0),
('9', 71, 'Nguyễn Văn Lộc', 'CĐTH21WEBC', NULL, '0306211167', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan`
--

CREATE TABLE `taikhoan` (
  `ID_TaiKhoan` int NOT NULL,
  `TaiKhoan` varchar(20) DEFAULT NULL,
  `MatKhau` varchar(16) DEFAULT NULL,
  `VaiTro` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`ID_TaiKhoan`, `TaiKhoan`, `MatKhau`, `VaiTro`, `TrangThai`) VALUES
('1', 'cb1', 'cb1', 'Cán bộ Khoa/Bộ môn', 1),
('10', 'Sv10', 'sv5', 'Sinh viên', 1),
('11', 'Sv11', 'sv5', 'Sinh viên', 1),
('12', 'Sv12', 'sv5', 'Sinh viên', 1),
('13', 'Sv13', 'sv5', 'Sinh viên', 1),
('14', 'Sv14', 'sv5', 'Sinh viên', 1),
('2', 'cb2', 'cb2', 'Cán bộ Khoa/Bộ môn', 1),
('3', 'Sv1', 'Sv1', 'Sinh viên', 1),
('4', 'Sv2', 'Sv2', 'Sinh viên', 1),
('5', 'Sv5', 'sv5', 'Sinh viên', 1),
('6', 'Gv1', 'Gv1', 'Giáo viên', 1),
('7', 'Gv2', 'Gv2', 'Giáo viên', 1),
('8', 'Sv8', 'sv5', 'Sinh viên', 1),
('9', 'Sv9', 'sv5', 'Sinh viên', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tainguyenthuctap`
--

CREATE TABLE `tainguyenthuctap` (
  `ID` int(11) NOT NULL,
  `Ten` varchar(255) DEFAULT NULL,
  `DuongDan` varchar(255) DEFAULT NULL,
  `NguoiDang` varchar(255) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `ID` int(11) NOT NULL,
  `TieuDe` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `NoiDung` text DEFAULT NULL,
  `NgayDang` datetime DEFAULT NULL,
  `ID_TaiKhoan` INT DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`ID`, `TieuDe`, `NoiDung`, `NgayDang`, `ID_TaiKhoan`, `TrangThai`) VALUES
(8, 'DANH SÁCH PHÂN CÔNG HƯỚNG DẪN THỰC TẬP TỐT NGHIỆP CĐ TH 22', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o một số th&ocirc;ng tin về thực tập tốt nghiệp CĐTH 22 như sau:</p>\r\n\r\n<p><strong>- C&aacute;c mốc thời gian thực tập:</strong></p>\r\n\r\n<ul>\r\n	<li>Thời gian thực tập ch&iacute;nh thức:&nbsp;<strong>Từ 24/02/2025&nbsp;đến 20/04/2025</strong></li>\r\n	<li>B&aacute;o c&aacute;o v&agrave; chấm điểm (dự kiến):&nbsp;<strong>Từ 21/04/2025&nbsp;đến 04/05/2025</strong></li>\r\n</ul>\r\n\r\n<p><strong>- Danh s&aacute;ch ph&acirc;n c&ocirc;ng GVHD:</strong></p>\r\n\r\n<ul>\r\n	<li>Danh s&aacute;ch GVHD thực tập:&nbsp;<a href=\"https://tinyurl.com/huongdantt-cdth22\" target=\"_blank\">https://tinyurl.com/huongdantt-cdth22</a></li>\r\n	<li>Danh s&aacute;ch email li&ecirc;n hệ GVHD:&nbsp;<a href=\"https://tinyurl.com/gvhd-email\">https://tinyurl.com/gvhd-email</a></li>\r\n</ul>\r\n\r\n<p><strong>- C&aacute;c t&agrave;i nguy&ecirc;n:</strong></p>\r\n\r\n<ul>\r\n	<li>C&aacute;c t&agrave;i nguy&ecirc;n/ biểu mẫu:&nbsp;<a href=\"https://tinyurl.com/tainguyen-cdth22\" target=\"_blank\">https://tinyurl.com/tainguyen-cdth22</a></li>\r\n	<li>Đăng k&yacute; giấy giới thiệu thực tập:&nbsp;<a href=\"https://tinyurl.com/gthieutt-cdth22\" target=\"_blank\">https://tinyurl.com/gthieutt-cdth22</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Đối với sinh vi&ecirc;n kh&oacute;a trước đăng k&yacute; thực tập (gh&eacute;p) chưa c&oacute; trong danh s&aacute;ch hướng dẫn, sinh vi&ecirc;n li&ecirc;n hệ thầy Nguy&ecirc;n qua email:&nbsp;<a href=\"mailto:lvhnguyen@caothang.edu.vn\" target=\"_blank\">lvhnguyen@caothang.edu.vn</a>&nbsp;để được phổ biến th&ocirc;ng tin hướng dẫn.</p>\r\n\r\n<p>Khoa CNTT ./.</p>\r\n', '2025-06-10 15:35:07', NULL, 1),
(9, 'THỜI KHOÁ BIỂU HỌC KỲ PHỤ HÈ TẬP TRUNG NĂM HỌC 2024-2025', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o thời kho&aacute; biểu học kỳ phụ cho c&aacute;c lớp sau:&nbsp;<strong><a href=\"https://cntt.caothang.edu.vn/uploads/media/HKP/TKB_HKP2425_20250603_CNTT_UpdatedPhong.pdf\">Tại đ&acirc;y</a></strong></p>\r\n\r\n<p>Lưu &yacute;:</p>\r\n\r\n<p>1. Thời gian đăng k&yacute;: từ thời điểm c&ocirc;ng bố thời kho&aacute; biểu đến hết ng&agrave;y 23/6/2025.</p>\r\n\r\n<p>2. C&aacute;ch thức đăng k&yacute;:</p>\r\n\r\n<p>- Bước 1: Xem thời kho&aacute; biểu HKP H&egrave; tập trung tr&ecirc;n website Ph&ograve;ng Đ&agrave;o tạo v&agrave; chọn lớp c&oacute; thời gian ph&ugrave; hợp để tr&aacute;nh tr&ugrave;ng lịch học.</p>\r\n\r\n<p>- Bước 2: Đăng k&yacute; tr&ecirc;n phần mềm &ldquo;HỌC KỲ PHỤ&rdquo; tại m&aacute;y t&iacute;nh Ph&ograve;ng Đ&agrave;o tạo. Đối với sinh vi&ecirc;n đăng k&yacute; học cải thiện điểm số sử dụng phần mềm &ldquo;HỌC CẢI THIỆN ĐIỂM&rdquo;.</p>\r\n\r\n<p>- Bước 3: Đ&oacute;ng kinh ph&iacute; tại Ph&ograve;ng T&agrave;i ch&iacute;nh &ndash; Kế to&aacute;n.</p>\r\n\r\n<p>- Bước 4: Đi học theo thời kho&aacute; biểu.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p><strong>Khoa CNTT ./.</strong></p>\r\n', '2025-06-10 16:31:44', NULL, 1),
(10, ' MỞ LỚP HỌC KỲ PHỤ HÈ TẬP TRUNG NĂM HỌC 2024 - 2025', '<p>Căn cứ số lượng sinh vi&ecirc;n đăng k&yacute; học, Ph&ograve;ng Đ&agrave;o tạo dự kiến mở c&aacute;c lớp học kỳ phụ h&egrave; tập trung năm học 2024 &ndash; 2025 như sau:<br />\r\n<strong>I. Danh s&aacute;ch m&ocirc;n học dự kiến mở học kỳ phụ h&egrave; tập trung</strong></p>\r\n\r\n<table align=\"center\" cellspacing=\"0\" style=\"border-collapse:collapse; width:729.99px\">\r\n	<tbody>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:1px solid black; border-right:1px solid black; border-top:1px solid black; height:10px; text-align:center; vertical-align:middle; width:45px\">\r\n			<p><strong>STT</strong></p>\r\n			</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; white-space:nowrap; width:323px\">\r\n			<p><strong>M&ocirc;n học</strong></p>\r\n			</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; width:189px\"><strong>Khoa, bộ m&ocirc;n phụ tr&aacute;ch</strong></td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; width:189px\">\r\n			<p><strong>Số lớp mở</strong></p>\r\n			</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; width:76px\">\r\n			<p><strong>Ghi ch&uacute;</strong></p>\r\n			</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Cấu tr&uacute;c dữ liệu v&agrave; giải thuật</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">2</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Cơ sở dữ liệu</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐ</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">3</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Hệ quản trị CSDL</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐ</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">4</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Hệ quản trị CSDL</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐN</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">5</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Nhập m&ocirc;n lập tr&igrave;nh</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐ</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">6</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Toán rời rạc và lý thuy&ecirc;́t đ&ocirc;̀ thị</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">7</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">TH Lập tr&igrave;nh hướng đối tượng</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">8</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Thực tập Nhập m&ocirc;n lập tr&igrave;nh</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid #000000; border-left:none; border-right:1px solid #000000; border-top:none; height:20px; text-align:center; width:76px\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">9</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Thực tập Cấu tr&uacute;c dữ liệu v&agrave; giải thuật</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">10</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Thực tập Thiết kế Website</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid #000000; border-left:none; border-right:1px solid #000000; border-top:none; height:20px; text-align:center; width:76px\">&nbsp;</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n\r\n<p><strong>II. Thời gian v&agrave; c&aacute;ch thức đăng k&yacute;</strong></p>\r\n\r\n<p>1. Thời gian đăng k&yacute;: từ thời điểm c&ocirc;ng bố thời kho&aacute; biểu đến hết ng&agrave;y 23/6/2025.</p>\r\n\r\n<p>2. C&aacute;ch thức đăng k&yacute;:</p>\r\n\r\n<p>- Bước 1: Theo d&otilde;i Thời kho&aacute; biểu HKP H&egrave; tập trung tr&ecirc;n website Ph&ograve;ng Đ&agrave;o tạo.</p>\r\n\r\n<p>- Bước 2: Đăng k&yacute; tr&ecirc;n phần mềm &ldquo;HỌC KỲ PHỤ&rdquo; tại m&aacute;y t&iacute;nh Ph&ograve;ng Đ&agrave;o tạo. Đối với sinh vi&ecirc;n đăng k&yacute; học cải thiện điểm số sử dụng phần mềm &ldquo;HỌC CẢI THIỆN ĐIỂM&rdquo;.</p>\r\n\r\n<p>- Bước 3: Đ&oacute;ng kinh ph&iacute; tại Ph&ograve;ng T&agrave;i ch&iacute;nh &ndash; Kế to&aacute;n.</p>\r\n\r\n<p>- Bước 4: Đi học theo thời kho&aacute; biểu.</p>\r\n\r\n<p>Trường hợp c&aacute;c lớp bị huỷ do kh&ocirc;ng đủ số lượng, sinh vi&ecirc;n li&ecirc;n hệ b&agrave;n số 3 Ph&ograve;ng Đ&agrave;o tạo để được hướng dẫn thủ tục ho&agrave;n lại kinh ph&iacute;.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Chi tiết:&nbsp;<a href=\"https://daotao.caothang.edu.vn/Thong-bao-hoc-ky-phu,-hoc-ghep/70-Thong-bao-mo-lop-hoc-ky-phu-he-tap-trung-nam-hoc-2024---2025-f637a2fa74bc83dd069f813e57cb9422.html\">https://daotao.caothang.edu.vn/Thong-bao-hoc-ky-phu,-hoc-ghep/70-Thong-bao-mo-lop-hoc-ky-phu-he-tap-trung-nam-hoc-2024---2025</a></p>\r\n\r\n<p><strong>Khoa CNTT./.</strong></p>\r\n', '2025-06-10 17:13:12', NULL, 1),
(15, 's', '', '2025-06-13 11:12:56', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tongket`
--

CREATE TABLE `tongket` (
  `ID` int(11) NOT NULL,
  `IDSV` INT DEFAULT NULL,
  `ID_GVHD` INT DEFAULT NULL,
  `Diem` float DEFAULT NULL,
  `DanhGia` text DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_taikhoan_ten`
-- (See below for the actual view)
--
CREATE TABLE `view_taikhoan_ten` (
`ID_TaiKhoan` INT
,`VaiTro` varchar(30)
,`TenNguoiDung` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure for view `view_taikhoan_ten`
--
DROP TABLE IF EXISTS `view_taikhoan_ten`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_taikhoan_ten`  AS SELECT `tk`.`ID_TaiKhoan` AS `ID_TaiKhoan`, `tk`.`VaiTro` AS `VaiTro`, coalesce(`sv`.`Ten`,`gv`.`Ten`,`cb`.`Ten`) AS `TenNguoiDung` FROM (((`taikhoan` `tk` left join `sinhvien` `sv` on(`tk`.`ID_TaiKhoan` = `sv`.`ID_TaiKhoan`)) left join `giaovien` `gv` on(`tk`.`ID_TaiKhoan` = `gv`.`ID_TaiKhoan`)) left join `canbokhoa` `cb` on(`tk`.`ID_TaiKhoan` = `cb`.`ID_TaiKhoan`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `baocao`
--
ALTER TABLE `baocao`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDSV` (`IDSV`),
  ADD KEY `IdGVHD` (`IdGVHD`);

--
-- Indexes for table `canbokhoa`
--
ALTER TABLE `canbokhoa`
  ADD PRIMARY KEY (`ID_TaiKhoan`);

--
-- Indexes for table `cauhoikhaosat`
--
ALTER TABLE `cauhoikhaosat`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_KhaoSat` (`ID_KhaoSat`);

--
-- Indexes for table `cautraloi`
--
ALTER TABLE `cautraloi`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_PhanHoi` (`ID_PhanHoi`),
  ADD KEY `ID_CauHoi` (`ID_CauHoi`);

--
-- Indexes for table `congty`
--
ALTER TABLE `congty`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_SV` (`ID_SV`),
  ADD KEY `ID_GVHD` (`ID_GVHD`);

--
-- Indexes for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD PRIMARY KEY (`ID_TaiKhoan`);

--
-- Indexes for table `giaygioithieu`
--
ALTER TABLE `giaygioithieu`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IdSinhVien` (`IdSinhVien`);

--
-- Indexes for table `khaosat`
--
ALTER TABLE `khaosat`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_NguoiTao` (`NguoiTao`),
  ADD KEY `NguoiNhan` (`NguoiNhan`);

--
-- Indexes for table `phanhoikhaosat`
--
ALTER TABLE `phanhoikhaosat`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_KhaoSat` (`ID_KhaoSat`),
  ADD KEY `ID_TaiKhoan` (`ID_TaiKhoan`);

--
-- Indexes for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD PRIMARY KEY (`ID_TaiKhoan`),
  ADD KEY `ID_Dot` (`ID_Dot`),
  ADD KEY `ID_GVHD` (`ID_GVHD`);

--
-- Indexes for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`ID_TaiKhoan`);

--
-- Indexes for table `tainguyenthuctap`
--
ALTER TABLE `tainguyenthuctap`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_TaiKhoan` (`ID_TaiKhoan`);

--
-- Indexes for table `tongket`
--
ALTER TABLE `tongket`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDSV` (`IDSV`),
  ADD KEY `ID_GVHD` (`ID_GVHD`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `baocao`
--
ALTER TABLE `baocao`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cauhoikhaosat`
--
ALTER TABLE `cauhoikhaosat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `cautraloi`
--
ALTER TABLE `cautraloi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `congty`
--
ALTER TABLE `congty`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `file`
--
ALTER TABLE `file`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giaygioithieu`
--
ALTER TABLE `giaygioithieu`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `khaosat`
--
ALTER TABLE `khaosat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `phanhoikhaosat`
--
ALTER TABLE `phanhoikhaosat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `tainguyenthuctap`
--
ALTER TABLE `tainguyenthuctap`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tongket`
--
ALTER TABLE `tongket`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
-- AUTO_INCREMENT for table `tongket`
--
ALTER TABLE `taikhoan`
  MODIFY `ID_TaiKhoan` int(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `baocao`
--
ALTER TABLE `baocao`
  ADD CONSTRAINT `baocao_ibfk_1` FOREIGN KEY (`IDSV`) REFERENCES `sinhvien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `baocao_ibfk_2` FOREIGN KEY (`IdGVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`);

--
-- Constraints for table `canbokhoa`
--
ALTER TABLE `canbokhoa`
  ADD CONSTRAINT `canbokhoa_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `cauhoikhaosat`
--
ALTER TABLE `cauhoikhaosat`
  ADD CONSTRAINT `cauhoikhaosat_ibfk_1` FOREIGN KEY (`ID_KhaoSat`) REFERENCES `khaosat` (`ID`);

--
-- Constraints for table `cautraloi`
--
ALTER TABLE `cautraloi`
  ADD CONSTRAINT `cautraloi_ibfk_1` FOREIGN KEY (`ID_PhanHoi`) REFERENCES `phanhoikhaosat` (`ID`),
  ADD CONSTRAINT `cautraloi_ibfk_2` FOREIGN KEY (`ID_CauHoi`) REFERENCES `cauhoikhaosat` (`ID`);

--
-- Constraints for table `file`
--
ALTER TABLE `file`
  ADD CONSTRAINT `file_ibfk_1` FOREIGN KEY (`ID_SV`) REFERENCES `sinhvien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `file_ibfk_2` FOREIGN KEY (`ID_GVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`);

--
-- Constraints for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD CONSTRAINT `giaovien_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `giaygioithieu`
--
ALTER TABLE `giaygioithieu`
  ADD CONSTRAINT `giaygioithieu_ibfk_1` FOREIGN KEY (`IdSinhVien`) REFERENCES `sinhvien` (`ID_TaiKhoan`);

--
-- Constraints for table `khaosat`
--
ALTER TABLE `khaosat`
  ADD CONSTRAINT `fk_NguoiTao` FOREIGN KEY (`NguoiTao`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `phanhoikhaosat`
--
ALTER TABLE `phanhoikhaosat`
  ADD CONSTRAINT `phanhoikhaosat_ibfk_1` FOREIGN KEY (`ID_KhaoSat`) REFERENCES `khaosat` (`ID`),
  ADD CONSTRAINT `phanhoikhaosat_ibfk_2` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD CONSTRAINT `sinhvien_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`),
  ADD CONSTRAINT `sinhvien_ibfk_2` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`),
  ADD CONSTRAINT `sinhvien_ibfk_3` FOREIGN KEY (`ID_GVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`);

--
-- Constraints for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);
--
-- Constraints for table `tongket`
--
ALTER TABLE `tongket`
  ADD CONSTRAINT `tongket_ibfk_1` FOREIGN KEY (`IDSV`) REFERENCES `sinhvien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `tongket_ibfk_2` FOREIGN KEY (`ID_GVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
CREATE TABLE TuanBaoCao (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_GVHD INT NOT NULL,
    Tuan INT NOT NULL,             
    TrangThai TINYINT NOT NULL,
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);