-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 16, 2025 at 03:04 AM
-- Server version: 11.4.5-MariaDB-cll-lve-log
-- PHP Version: 8.2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siektefuhosting_thuctapdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ID_TaiKhoan` int(11) NOT NULL,
  `Ten` varchar(250) NOT NULL,
  `TrangThai` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ID_TaiKhoan`, `Ten`, `TrangThai`) VALUES
(116, 'Nguyễn Thanh Kiệt', 1);

-- --------------------------------------------------------

--
-- Table structure for table `baocaotongket`
--

CREATE TABLE `baocaotongket` (
  `ID` int(11) NOT NULL,
  `ID_TaiKhoan` int(11) NOT NULL COMMENT 'ID tài khoản giáo viên',
  `ID_Dot` int(11) NOT NULL COMMENT 'ID đợt thực tập',
  `TrangThai` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Đóng nộp, 1: Mở nộp báo cáo tổng kết',
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày tạo bản ghi',
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý trạng thái đóng/mở nộp báo cáo tổng kết cho từng đợt thực tập';

--
-- Dumping data for table `baocaotongket`
--

INSERT INTO `baocaotongket` (`ID`, `ID_TaiKhoan`, `ID_Dot`, `TrangThai`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 113, 71, 0, '2025-07-09 03:24:25', '2025-07-09 03:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `canbokhoa`
--

CREATE TABLE `canbokhoa` (
  `ID_TaiKhoan` int(11) NOT NULL,
  `Ten` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `canbokhoa`
--

INSERT INTO `canbokhoa` (`ID_TaiKhoan`, `Ten`, `TrangThai`) VALUES
(114, 'Lê Viết Hoàng Nguyên', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cauhinh`
--

CREATE TABLE `cauhinh` (
  `ID` int(11) NOT NULL,
  `Ten` varchar(100) NOT NULL,
  `GiaTri` text DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `Loai` varchar(50) DEFAULT 'text'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cauhinh`
--

INSERT INTO `cauhinh` (`ID`, `Ten`, `GiaTri`, `MoTa`, `Loai`) VALUES
(2, 'email_lienhe', 'cntt@caothang.edu.vn', 'Email liên hệ', 'email'),
(4, 'facebook_link', 'https://www.facebook.com/cntt.caothang.edu.vn', 'Liên kết Facebook', 'url'),
(5, 'ten_trang', 'Hệ thống quản lý thực tập', 'Tên trang web', 'text'),
(6, 'logo', '/datn/uploads/Images/logo.jpg', 'Logo trang web', 'file'),
(7, 'footer_text', '© 2025 Khoa Công Nghệ Thông Tin Trường CĐKT Cao Thắng', 'Chữ ở chân trang', 'textarea'),
(8, 'sdt_lienhe', '083 821 2360', 'Số điện thoại liên hệ', 'text'),
(9, 'mau_sac_giaodien', '#248aeb', 'Màu giao diện', 'color'),
(11, 'dia_chi_khoa', 'Lầu 7 - Dãy F, 65 Huỳnh Thúc Kháng, Phường Bến Nghé, Quận 1, TP.HCM, Việt Nam', 'Địa chỉ Khoa', 'textarea'),
(12, 'website_khoa', 'https://cntt.caothang.edu.vn', 'Đường link đến website của Khoa', 'text');

-- --------------------------------------------------------

--
-- Table structure for table `cauhoikhaosat`
--

CREATE TABLE `cauhoikhaosat` (
  `ID` int(11) NOT NULL,
  `ID_KhaoSat` int(11) DEFAULT NULL,
  `NoiDung` text DEFAULT NULL,
  `Loai` varchar(20) DEFAULT NULL,
  `DapAn` text DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cauhoikhaosat`
--

INSERT INTO `cauhoikhaosat` (`ID`, `ID_KhaoSat`, `NoiDung`, `Loai`, `DapAn`, `TrangThai`) VALUES
(1, 1, 'Bạn đã có công ty thực tập hay chưa?', 'choice', 'Có rồi; Chưa có', 1),
(2, 2, 'a', 'multiple', 'a;b;c;d;e;f;g;h', 1),
(3, 3, 'a', 'text', NULL, 1),
(4, 4, 'a', 'text', NULL, 1),
(5, 5, 'a', 'text', NULL, 1),
(6, 6, 'c', 'text', NULL, 1);

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
(1, 1, 1, 'Chưa có', 1),
(2, 2, 2, 'd;g', 1),
(3, 3, 3, 'a', 1),
(4, 4, 4, 'a', 1),
(5, 5, 5, 'a', 1),
(6, 6, 6, 'c', 1);

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

--
-- Dumping data for table `congty`
--

INSERT INTO `congty` (`ID`, `MaSoThue`, `TenCty`, `LinhVuc`, `Sdt`, `Email`, `DiaChi`, `MoTa`, `TrangThai`) VALUES
(1, '0314419070', 'RIOT GAME', 'IT', '0933519887', 'quandang377@gmail.com', 'test1', 'test1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `congviec_baocao`
--

CREATE TABLE `congviec_baocao` (
  `ID` int(11) NOT NULL,
  `IDSV` int(11) NOT NULL,
  `Ngay` date NOT NULL,
  `TenCongViec` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `TienDo` int(11) NOT NULL DEFAULT 0,
  `TrangThai` tinyint(1) DEFAULT 1,
  `ID_Dot` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `congviec_baocao`
--

INSERT INTO `congviec_baocao` (`ID`, `IDSV`, `Ngay`, `TenCongViec`, `MoTa`, `TienDo`, `TrangThai`, `ID_Dot`) VALUES
(1, 3, '2025-07-09', 'a', 'b', 64, 1, 71);

-- --------------------------------------------------------

--
-- Table structure for table `diem_tongket`
--

CREATE TABLE `diem_tongket` (
  `ID` int(11) NOT NULL,
  `ID_SV` int(11) NOT NULL,
  `Diem_BaoCao` float DEFAULT NULL,
  `Diem_ChuyenCan` float DEFAULT NULL,
  `Diem_ChuanNghe` float DEFAULT NULL,
  `Diem_ThucTe` float DEFAULT NULL,
  `GhiChu` text DEFAULT NULL,
  `ID_Dot` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diem_tongket`
--

INSERT INTO `diem_tongket` (`ID`, `ID_SV`, `Diem_BaoCao`, `Diem_ChuyenCan`, `Diem_ChuanNghe`, `Diem_ThucTe`, `GhiChu`, `ID_Dot`) VALUES
(1, 3, 1, 2, 2, 2, 'aaaaaaaaaaaaaaaaaaaaa', 71),
(2, 155, 0, 0, 0, 0, '', 71),
(3, 41, 0, 0, 0, 0, '', 71);

-- --------------------------------------------------------

--
-- Table structure for table `dotthuctap`
--

CREATE TABLE `dotthuctap` (
  `ID` int(11) NOT NULL,
  `TenDot` varchar(50) DEFAULT NULL,
  `Nam` varchar(5) DEFAULT NULL,
  `BacDaoTao` varchar(25) DEFAULT NULL,
  `ThoiGianBatDau` date DEFAULT NULL,
  `ThoiGianKetThuc` date DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL,
  `NguoiQuanLy` int(11) DEFAULT NULL,
  `NguoiMoDot` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dotthuctap`
--

INSERT INTO `dotthuctap` (`ID`, `TenDot`, `Nam`, `BacDaoTao`, `ThoiGianBatDau`, `ThoiGianKetThuc`, `TrangThai`, `NguoiQuanLy`, `NguoiMoDot`) VALUES
(43, 'CĐTH21-1', '2021', 'Cao đẳng ngành', '2021-06-16', '2021-07-02', 0, 114, 114),
(44, 'CĐTH21-2', '2021', 'Cao đẳng', '2021-06-08', '2021-07-08', 0, 114, 114),
(45, 'CĐTH22-1', '2022', 'Cao Đẳng', '2022-06-08', '2022-07-08', 0, 114, 114),
(46, 'CĐTH20-1', '2020', 'Cao Đẳng', '2020-06-02', '2020-07-15', 0, 114, 114),
(47, 'CĐTH23-1', '2023', 'Cao Đẳng', '2023-06-07', '2023-08-17', 0, 114, 2),
(48, 'CĐTH22-2', '2022', 'Cao Đẳng Ngành', '2022-06-15', '2022-07-06', -1, 114, 114),
(49, 'CĐTH25-2', '2025', 'Cao Đẳng', '2022-06-08', '2022-08-08', 0, 114, 114),
(51, 'CĐTH09-1', '2009', 'Cao Đẳng', '2009-06-14', '2009-07-08', 0, 2, 2),
(52, 'CĐTH09-2', '2009', 'Cao Đẳng', '2009-06-03', '2009-07-08', 0, 2, 2),
(53, 'CĐTH10-1', '2010', 'Cao Đẳng', '2010-06-05', '2010-07-08', 0, 2, 2),
(54, 'CĐTH10-2', '2010', 'Cao Đẳng', '2010-06-11', '2010-07-08', 0, 2, 2),
(55, 'CĐTH11-1', '2011', 'Cao Đẳng', '2011-06-17', '2011-07-08', 0, 2, 2),
(57, 'CĐTH12-1', '2012', 'Cao Đẳng', '2012-06-03', '2012-07-08', 0, 2, 2),
(58, 'CĐTH12-2', '2012', 'Cao Đẳng', '2012-06-10', '2012-07-08', 0, 2, 2),
(59, 'CĐTH13-1', '2013', 'Cao Đẳng', '2013-06-17', '2013-07-08', 0, 2, 2),
(60, 'CĐTH13-2', '2013', 'Cao Đẳng', '2013-06-10', '2013-07-08', 0, 2, 2),
(61, 'CĐTH14-1', '2014', 'Cao Đẳng', '2014-06-02', '2014-07-08', 0, 2, 2),
(62, 'CĐTH14-2', '2014', 'Cao Đẳng', '2014-06-09', '2014-07-08', 0, 2, 2),
(63, 'CĐTH15-1', '2015', 'Cao Đẳng', '2015-06-09', '2015-07-08', 0, 2, 2),
(64, 'CĐTH15-2', '2015', 'Cao Đẳng', '2015-06-09', '2015-07-08', 0, 2, 2),
(65, 'CĐTH16-1', '2016', 'Cao Đẳng', '2016-06-09', '2016-07-08', 0, 2, 2),
(66, 'CĐTH16-2', '2016', 'Cao Đẳng', '2016-06-02', '2016-07-08', 0, 114, 114),
(67, 'CĐTH17-1', '2017', 'Cao Đẳng', '2017-06-09', '2017-07-08', 0, 114, 114),
(68, 'CĐTH17-2', '2017', 'Cao Đẳng', '2017-06-03', '2017-07-08', 0, 114, 114),
(69, 'CĐTH18-1', '2018', 'Cao Đẳng', '2018-06-10', '2018-07-08', 0, 114, 114),
(70, 'CĐTH19-2', '2019', 'Cao Đẳng', '2019-06-10', '2019-07-08', 0, 114, 114),
(71, 'CĐTH25-1', '2025', 'Cao đẳng', '2025-07-30', '2025-09-30', 4, 114, 114),
(72, 'CĐTH25-3', '2025', 'Cao đẳng', '2025-06-12', '2025-07-12', 0, 114, 114),
(73, 'CĐTH25-4', '2025', 'Cao đẳng', '2025-06-19', '2025-07-18', 2, 114, 114),
(74, 'CĐTH21-4', '2023', 'Cao đẳng', '2025-07-23', '2025-07-26', 1, 114, 114),
(81, 'CĐTH25-10', '2025', 'Cao đẳng', '2025-06-25', '2025-07-23', 2, 114, 114);

-- --------------------------------------------------------

--
-- Table structure for table `dot_giaovien`
--

CREATE TABLE `dot_giaovien` (
  `ID_Dot` int(11) NOT NULL,
  `ID_GVHD` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dot_giaovien`
--

INSERT INTO `dot_giaovien` (`ID_Dot`, `ID_GVHD`) VALUES
(71, 113),
(74, 113),
(71, 124),
(74, 124),
(71, 126),
(74, 126),
(71, 129),
(74, 129),
(71, 133),
(74, 133),
(71, 134),
(74, 134),
(71, 135),
(74, 135),
(71, 136),
(74, 136),
(71, 137),
(74, 137),
(71, 138),
(74, 138),
(71, 139),
(74, 139),
(71, 140),
(74, 140),
(71, 141),
(74, 141),
(71, 142),
(74, 142),
(71, 143),
(74, 143),
(71, 144),
(74, 144),
(71, 145),
(74, 145),
(71, 146),
(74, 146),
(71, 147),
(74, 147),
(71, 148),
(74, 148),
(71, 149),
(74, 149),
(71, 150),
(74, 150),
(71, 151),
(74, 151),
(71, 152),
(74, 152),
(71, 153),
(74, 153),
(71, 154),
(74, 154);

-- --------------------------------------------------------

--
-- Table structure for table `file`
--

CREATE TABLE `file` (
  `ID` int(11) NOT NULL,
  `TenFile` text DEFAULT NULL,
  `DIR` text DEFAULT NULL,
  `ID_SV` int(11) DEFAULT NULL,
  `ID_GVHD` int(11) DEFAULT NULL,
  `TrangThai` tinyint(1) NOT NULL,
  `Loai` varchar(255) NOT NULL,
  `NgayNop` datetime DEFAULT NULL,
  `TenHienThi` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `file`
--

INSERT INTO `file` (`ID`, `TenFile`, `DIR`, `ID_SV`, `ID_GVHD`, `TrangThai`, `Loai`, `NgayNop`, `TenHienThi`) VALUES
(36, '01_Cong van lien he thuc tap_CNTT (1)_1751596165 (2).docx', 'C:\\xampp\\htdocs\\datn\\file\\01_Cong van lien he thuc tap_CNTT (1)_1751596165 (2).docx', 3, NULL, 1, 'Baocao', '2025-07-04 04:43:40', ''),
(37, '20250619_204716.jpg', 'C:\\xampp\\htdocs\\datn\\file\\20250619_204716.jpg', 3, NULL, 1, 'nhanxet', '2025-07-04 04:43:44', ''),
(38, 'JBcrn-Qoz4ttImcv_KTyK.jpg', 'C:\\xampp\\htdocs\\datn\\file\\JBcrn-Qoz4ttImcv_KTyK.jpg', 3, NULL, 1, 'phieuthuctap', '2025-07-04 04:43:48', ''),
(39, '20250619_204716_1751597032.jpg', 'C:\\xampp\\htdocs\\datn\\file\\20250619_204716_1751597032.jpg', 3, NULL, 1, 'khoasat', '2025-07-04 04:43:52', ''),
(40, '03_phieu_khao_sat_DN_2023.pdf', 'C:\\xampp\\htdocs\\datn\\file\\file_686df412f0c28.pdf', NULL, NULL, 1, 'Tainguyen', '2025-07-09 11:46:10', 'Phiếu Khảo Sát 1.0');

-- --------------------------------------------------------

--
-- Table structure for table `giaovien`
--

CREATE TABLE `giaovien` (
  `ID_TaiKhoan` int(11) NOT NULL,
  `Ten` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giaovien`
--

INSERT INTO `giaovien` (`ID_TaiKhoan`, `Ten`, `TrangThai`) VALUES
(113, 'Lữ Cao Tiến', 1),
(124, 'Nguyễn Thị Thanh Thuận', 1),
(126, 'Đinh Bá Tài', 1),
(129, 'Lục Hán Tường', 1),
(133, 'Hồ Diện Tuấn Anh', 1),
(134, 'Trần Thị Đặng', 1),
(135, 'Nguyễn Đức Duy', 1),
(136, 'Nguyễn Vũ Dzũng', 1),
(137, 'Nguyễn Võ Công Khanh', 1),
(138, 'Tô Vũ Song Phương', 1),
(139, 'Đinh Nguyễn Bá Tài', 1),
(140, 'Vũ Đức Toàn', 1),
(141, 'Nguyễn Tâm Thanh Tùng', 1),
(142, 'Lê Hữu Vinh', 1),
(143, 'Nguyễn Hoàng Việt', 1),
(144, 'Phù Khắc Anh', 1),
(145, 'Võ Thị Vân Anh', 1),
(146, 'Nguyễn Đức Chuẩn', 1),
(147, 'Dương Trọng Đính', 1),
(148, 'Lưu Tuệ Hảo', 1),
(149, 'Nguyễn Thị Ngọc', 1),
(150, 'Vũ Yến Ni', 1),
(151, 'Nguyễn Bá Phúc', 1),
(152, 'Trần Thanh Tuấn', 1),
(153, 'Võ Trúc Vy', 1),
(154, 'Phạm Phú Hoàng Sơn', 1);

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
  `IdSinhVien` int(11) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL,
  `id_dot` int(11) NOT NULL,
  `id_nguoinhan` int(11) DEFAULT NULL,
  `ngay_nhan` date NOT NULL,
  `ghi_chu` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giaygioithieu`
--

INSERT INTO `giaygioithieu` (`ID`, `TenCty`, `MaSoThue`, `LinhVuc`, `Sdt`, `Email`, `DiaChi`, `MoTa`, `IdSinhVien`, `TrangThai`, `id_dot`, `id_nguoinhan`, `ngay_nhan`, `ghi_chu`) VALUES
(4, 'RIOT GAME', '0314419070', 'IT', '0933519887', 'quandang377@gmail.com', 'zzzzz', NULL, 3, 0, 71, NULL, '0000-00-00', ''),
(5, 'RIOT GAME', '0314419070', 'IT', '0933519887', 'quandang377@gmail.com', 'test1', NULL, 3, 1, 71, NULL, '0000-00-00', '');

-- --------------------------------------------------------

--
-- Table structure for table `khaosat`
--

CREATE TABLE `khaosat` (
  `ID` int(11) NOT NULL,
  `TieuDe` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `NguoiNhan` varchar(50) NOT NULL,
  `NguoiTao` int(11) DEFAULT NULL,
  `ID_Dot` int(11) DEFAULT NULL,
  `ThoiGianTao` datetime DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL,
  `ThoiHan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khaosat`
--

INSERT INTO `khaosat` (`ID`, `TieuDe`, `MoTa`, `NguoiNhan`, `NguoiTao`, `ID_Dot`, `ThoiGianTao`, `TrangThai`, `ThoiHan`) VALUES
(1, 'Bạn có tài mà', 'ô kê không', 'Sinh viên', 116, 71, '2025-06-28 15:35:10', 1, NULL),
(2, 'â', 'a', 'Sinh viên', 116, 71, '2025-06-28 15:37:56', 1, NULL),
(3, 'â', 'a', 'Sinh viên', 116, 71, '2025-06-28 15:40:41', 1, NULL),
(4, 'a', 'a', 'Sinh viên', 116, 71, '2025-06-28 15:46:08', 1, NULL),
(5, 'a', 'â', 'Tất cả', 116, 71, '2025-06-28 15:50:11', 1, NULL),
(6, 'c', 'c', 'Sinh viên', 116, 71, '2025-06-28 15:53:53', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `phanhoikhaosat`
--

CREATE TABLE `phanhoikhaosat` (
  `ID` int(11) NOT NULL,
  `ID_KhaoSat` int(11) DEFAULT NULL,
  `ID_TaiKhoan` int(11) DEFAULT NULL,
  `ThoiGianTraLoi` datetime DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phanhoikhaosat`
--

INSERT INTO `phanhoikhaosat` (`ID`, `ID_KhaoSat`, `ID_TaiKhoan`, `ThoiGianTraLoi`, `TrangThai`) VALUES
(1, 1, 155, '2025-06-28 15:36:25', 1),
(2, 2, 155, '2025-06-28 15:38:20', 1),
(3, 3, 155, '2025-06-28 15:41:03', 1),
(4, 4, 155, '2025-06-28 15:46:41', 1),
(5, 5, 155, '2025-06-28 15:50:28', 1),
(6, 6, 155, '2025-06-28 15:54:08', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sinhvien`
--

CREATE TABLE `sinhvien` (
  `ID_TaiKhoan` int(11) NOT NULL,
  `ID_Dot` int(11) DEFAULT NULL,
  `Ten` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `NgaySinh` date DEFAULT NULL,
  `Lop` varchar(50) DEFAULT NULL,
  `XepLoai` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `MSSV` varchar(12) DEFAULT NULL,
  `ID_GVHD` int(11) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sinhvien`
--

INSERT INTO `sinhvien` (`ID_TaiKhoan`, `ID_Dot`, `Ten`, `NgaySinh`, `Lop`, `XepLoai`, `MSSV`, `ID_GVHD`, `TrangThai`) VALUES
(3, 71, 'Đặng Minh Quân', NULL, 'CĐTH21DĐ', 'Xuất sắc', '0306211181', 113, 1),
(16, 71, 'Nguyễn Nhân Đức', NULL, NULL, NULL, '0468201017', 124, 1),
(17, 71, 'Lê Quang Duy', NULL, NULL, NULL, '0468211018', 126, 1),
(18, 71, 'Lý Tấn Lộc', '2003-06-12', 'CĐTH21DĐ', NULL, '0468211037', 129, 1),
(19, 71, 'Phạm Trí Quyền', NULL, NULL, NULL, '0468211059', 133, 1),
(20, 71, 'Nguyễn Minh Bảo', NULL, NULL, NULL, '0468221003', 134, 1),
(21, 71, 'Trần Quốc Cường', NULL, NULL, NULL, '0468221007', 135, 1),
(22, 71, 'Trần Quốc Việt Cường', NULL, NULL, NULL, '0468221008', 136, 1),
(23, 71, 'Phan Huỳnh Thanh Duy', NULL, NULL, NULL, '0468221009', 137, 1),
(24, 71, 'Đặng Quang Dự', NULL, NULL, NULL, '0468221010', 138, 1),
(25, 71, 'Nguyễn Phương Đông', NULL, NULL, NULL, '0468221013', 139, 1),
(26, 71, 'Nguyễn Thế Hào', NULL, NULL, NULL, '0468221017', 140, 1),
(27, 71, 'Nguyễn Ngọc Hải', NULL, NULL, NULL, '0468221018', 141, 1),
(28, 71, 'Nguyễn Công Hiếu', NULL, NULL, NULL, '0468221020', 142, 1),
(29, 71, 'Đào Bảo Khanh', NULL, NULL, NULL, '0468221031', 143, 1),
(30, 71, 'Nguyễn Minh Khiêm', NULL, NULL, NULL, '0468221033', 144, 1),
(31, 71, 'Nguyễn Đình Khôi', NULL, NULL, NULL, '0468221036', 145, 1),
(32, 71, 'Trần Minh Luân', NULL, NULL, NULL, '0468221040', 146, 1),
(33, 71, 'Nguyễn Bảo Nghi', NULL, NULL, NULL, '0468221041', 147, 1),
(34, 71, 'Lê Quốc Nghĩa', NULL, NULL, NULL, '0468221042', 148, 1),
(35, 71, 'Nguyễn Thành Phát', NULL, NULL, NULL, '0468221048', 149, 1),
(36, 71, 'La Vạn Phúc', NULL, NULL, NULL, '0468221053', 150, 1),
(37, 71, 'Mai Hoàng Phúc', NULL, NULL, NULL, '0468221055', 151, 1),
(38, 71, 'Trương Hoàng Sơn', NULL, NULL, NULL, '0468221062', 152, 1),
(39, 71, 'Trần Đức Tài', NULL, NULL, NULL, '0468221065', 153, 1),
(40, 71, 'Nguyễn Hoài Tân', NULL, NULL, NULL, '0468221066', 154, 1),
(41, 71, 'Nguyễn Trần Minh Tân', NULL, NULL, NULL, '0468221067', 113, 1),
(42, 71, 'Phạm Minh Tiến', NULL, NULL, NULL, '0468221078', 124, 1),
(43, 71, 'Trần Ngọc Trung', NULL, NULL, NULL, '0468221081', 126, 1),
(44, 71, 'Bùi Anh Tuấn', NULL, NULL, NULL, '0468221082', 129, 1),
(45, 71, 'Phạm Quốc Vinh', NULL, NULL, NULL, '0468221084', 133, 1),
(46, 71, 'Hồng Vĩnh Lộc', NULL, NULL, NULL, '0468221176', 134, 1),
(155, 71, 'Đặng Minh Quân', NULL, 'CĐTH21DĐ', NULL, '0306211181', 113, 1),
(156, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 135, 1),
(157, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 136, 1),
(158, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 137, 1),
(159, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 138, 1),
(160, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 139, 1),
(161, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 140, 1),
(162, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 141, 1),
(163, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 142, 1),
(164, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 143, 1),
(165, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 144, 1),
(166, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 145, 1),
(167, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 146, 1),
(168, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 147, 1),
(169, 71, 'a', '2005-06-09', 'CĐTH21DĐ', NULL, '0306211150', 148, 1);

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan`
--

CREATE TABLE `taikhoan` (
  `ID_TaiKhoan` int(11) NOT NULL,
  `TaiKhoan` varchar(250) DEFAULT NULL,
  `MatKhau` varchar(250) DEFAULT NULL,
  `VaiTro` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `remember_token` varchar(255) NOT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`ID_TaiKhoan`, `TaiKhoan`, `MatKhau`, `VaiTro`, `remember_token`, `TrangThai`) VALUES
(1, 'cb1', 'cb1', 'Cán bộ Khoa/Bộ môn', '', 0),
(2, 'cb2', 'cb2', 'Cán bộ Khoa/Bộ môn', '', 0),
(3, 'Sv1', 'Sv1', 'Sinh viên', '', 1),
(11, 'Sv11', 'sv5', 'Giáo viên', '', 0),
(12, 'Sv12', 'sv5', 'Giáo viên', '', 1),
(13, 'Sv13', 'sv5', 'Sinh viên', '', 0),
(16, '0468201017@caothang.com', '$2y$10$gUfpTP0vJ', 'Sinh viên', '', 1),
(17, '0468211018@caothang.', '$2y$10$IEEixVZq5', 'Sinh viên', '', 1),
(18, '0468211037@caothang.', '$2y$10$bs1xrUXDC', 'Sinh viên', '', 1),
(19, '0468211059@caothang.', '$2y$10$NN71ARE4x', 'Sinh viên', '', 1),
(20, '0468221003@caothang.', '$2y$10$E7jXTm3sE', 'Sinh viên', '', 1),
(21, '0468221007@caothang.', '$2y$10$m1uPA/SGI', 'Sinh viên', '', 1),
(22, '0468221008@caothang.', '$2y$10$jjASoxLd0', 'Sinh viên', '', 1),
(23, '0468221009@caothang.', '$2y$10$4ViPhbdro', 'Sinh viên', '', 1),
(24, '0468221010@caothang.', '$2y$10$/di4jl69T', 'Sinh viên', '', 1),
(25, '0468221013@caothang.', '$2y$10$pncfIm8n2', 'Sinh viên', '', 1),
(26, '0468221017@caothang.', '$2y$10$AscD7wl2e', 'Sinh viên', '', 1),
(27, '0468221018@caothang.', '$2y$10$YobXX/O67', 'Sinh viên', '', 1),
(28, '0468221020@caothang.', '$2y$10$EcFSlRyxR', 'Sinh viên', '', 1),
(29, '0468221031@caothang.', '$2y$10$x/eTxBG63', 'Sinh viên', '', 1),
(30, '0468221033@caothang.', '$2y$10$zJkshfRUN', 'Sinh viên', '', 1),
(31, '0468221036@caothang.', '$2y$10$Lv6fhK9v2', 'Sinh viên', '', 1),
(32, '0468221040@caothang.', '$2y$10$YXuEO.QiC', 'Sinh viên', '', 1),
(33, '0468221041@caothang.', '$2y$10$su3ey41Yo', 'Sinh viên', '', 1),
(34, '0468221042@caothang.', '$2y$10$uwAHyLU8i', 'Sinh viên', '', 1),
(35, '0468221048@caothang.', '$2y$10$tWmp5M6Tl', 'Sinh viên', '', 1),
(36, '0468221053@caothang.', '$2y$10$T0NXDOlR3', 'Sinh viên', '', 1),
(37, '0468221055@caothang.', '$2y$10$InZDG4r3m', 'Sinh viên', '', 1),
(38, '0468221062@caothang.', '$2y$10$hYx0K7xx0', 'Sinh viên', '', 1),
(39, '0468221065@caothang.', '$2y$10$9khWrZyky', 'Sinh viên', '', 1),
(40, '0468221066@caothang.', '$2y$10$7eITCAITa', 'Sinh viên', '', 1),
(41, '0468221067@caothang.', '$2y$10$9l198VSuM', 'Sinh viên', '', 1),
(42, '0468221078@caothang.', '$2y$10$OSqvtjc94', 'Sinh viên', '', 1),
(43, '0468221081@caothang.', '$2y$10$UYtHov12n', 'Sinh viên', '', 1),
(44, '0468221082@caothang.', '$2y$10$jBYaCyxqc', 'Sinh viên', '', 1),
(45, '0468221084@caothang.', '$2y$10$81xQc0Cnp', 'Sinh viên', '', 1),
(46, '0468221176@caothang.', '$2y$10$nOZL7fIwS', 'Sinh viên', '', 1),
(113, 'gv1', 'gv1', 'Giáo viên', '71b68c863c99eba137610ba6a531a8aa426c3172d2058ec283fe6e2d71533fe7', 1),
(114, 'lvhnguyen@caothang.edu.vn', '$2y$10$6XWeNUeDjmRpmv/1mKSzk.DrltE3cLQ/ndmDBgH4RyTTnpP9DPzlK', 'Cán bộ Khoa/Bộ môn', '', 1),
(115, 'nthiep@caothang.edu.vn', '$2y$10$V32Ac7D8fkIkOwb.10ntM.A7jmEnwccjxZC5snfrVfKZBHH9a8ivW', 'Giáo viên', '', 0),
(116, '0306211159@caothang.edu.vn', '$2y$10$hdLCCV2Lx6CJhwGUGjdOKuCCjXQcEADgE09.bAMMdvzYbKcy2R9aa', 'Admin', '', 1),
(124, 'nttthuan@caothang.edu.vn', '$2y$10$5TdU9aytReFqJFK7YocweugInsT.jln0MPs1UieleJq2C3FTygeRy', 'Giáo viên', '', 1),
(126, 'dbtai@caothang.edu.vn', '$2y$10$ymJ2obxJP7oEjCjuN8UtUePR4y4hpMiZVI0qdzfWUyIAro.FOtwFO', 'Giáo viên', '', 1),
(129, 'luchantuong@caothang.edu.vn', '$2y$10$oLh79rPgu.pcF9DXJw1tfeHWTEeKD0X80ZaYJ07vvhZF8KSjy0dBq', 'Giáo viên', '', 1),
(130, 'vhnguyen@caothang.edu.vn', '$2y$10$qSxChJlXAMwcy3t8qLM0r.SJR8JBYnOCvOqVvza1L4RuIbn4W/3EG', 'Giáo viên', '', 0),
(132, '0306211170@caothang.edu.vn', '$2y$10$c7Rv3S0mIBTh9l1OvYnbE.zapR0569L2cafqVdxHeYe4MLHdtgheC', 'Sinh viên', '', 0),
(133, 'hodientuananh@caothang.edu.vn', '$2y$10$if/xO7uXQi14m6LAKc1a0.avl/e/yi7.N3Yg7ZK4G56DUNLVccMMq', 'Giáo viên', '', 1),
(134, 'ttdang@caothang.edu.vn', '$2y$10$018QWjAHquQZ4pXliqN5Te/MQGRXxrPhCMDk6yY.OLOy1EQPnRSsa', 'Giáo viên', '', 1),
(135, 'ndduy@caothang.edu.vn', '$2y$10$SLIheAlkHc24KnzSKy3YEOrIsfajPkv4hGnHeLitzJ1loJ0DNekFm', 'Giáo viên', '', 1),
(136, 'nvdzung@caothang.edu.vn', '$2y$10$54m7jEk0N760hjQ0LEA35ehFwgESmF9N7VAYl7XDT7UWdSiMn5Zu6', 'Giáo viên', '', 1),
(137, 'nvckhanh@caothang.edu.vn', '$2y$10$Kte.q9oSp8t6MRLKL2fz0OArou7a1uSVXCF589O/swNy/5pYPtRu6', 'Giáo viên', '', 1),
(138, 'tvsphuong@caothang.edu.vn', '$2y$10$4WWrE5vtJX8qlc1EXQPV7.dr3j3curjESh.TN4TNhP7XT2ifuhovS', 'Giáo viên', '', 1),
(139, 'dinhnguyenbatai@caothang.edu.vn', '$2y$10$z19YkK/3A5bWkPxMfOMUh.aU16aI88M.z5iSqMRzddI8CRR.taDIq', 'Giáo viên', '', 1),
(140, 'vdtoan@caothang.edu.vn', '$2y$10$Bhx/QF9K17JwG0eCV3Sx7OQAhmHARsuH87jKHHK7Kg2bu219hO9We', 'Giáo viên', '', 1),
(141, 'nguyentamthanhtung@caothang.edu.vn', '$2y$10$O0zwc0e9eDunnDoa1JAHJujd79j.9HNtYisbNch35JG5.wQ/Tdg4.', 'Giáo viên', '', 1),
(142, 'lhvinh@caothang.edu.vn', '$2y$10$qaOrgZp.z3DmkKKvGpTuy.uFJ/DyXldjBcmXZg7IjkqYKXG61rwcC', 'Giáo viên', '', 1),
(143, 'nguyenhoangviet@caothang.edu.vn', '$2y$10$7J3u3bkWljQKRC5raBTZaOsuG0SV7ZIeuSiC65MkFEnh/BfX6IV..', 'Giáo viên', '', 1),
(144, 'pkanh@caothang.edu.vn', '$2y$10$u5788m6WBl4fM/mXsxFhPO6rUnVGEVxt0IXe9NApoVKhpeqfZFGQ.', 'Giáo viên', '', 1),
(145, 'vothivananh@caothang.edu.vn', '$2y$10$MChYZRdMn8PbW.4WUQK/su/veMsjXQBr1O6EQb5o3pb7rggexXKe6', 'Giáo viên', '', 1),
(146, 'ndchuan@caothang.edu.vn', '$2y$10$ITzj/5r.3W.6.tu8unvap.l/wgJvueSe9izXKDWF9CAkpW5T8Ar3q', 'Giáo viên', '', 1),
(147, 'dtdinh@caothang.edu.vn', '$2y$10$N4fzPxZkEGfN2MV6OGg2JeZN/gF487.gp2ZJq5npa7Jc1iPuqtGZe', 'Giáo viên', '', 1),
(148, 'lthao@caothang.edu.vn', '$2y$10$kf0PiS8EG1m6NzqLpDg3Z.use0nu8G7n1KZP7GUvdidgqx.awyI2a', 'Giáo viên', '', 1),
(149, 'ntngoc@caothang.edu.vn', '$2y$10$tvJDf.8fR3J6FDJDSZoFS.VDkxDWFKWXxRJkmF/eyX0EzJd6QeTOC', 'Giáo viên', '', 1),
(150, 'vyni@caothang.edu.vn', '$2y$10$3c6Q895lGN8nK0QXPuSx.OrKTSUSy240ZD2/UMDbsuxCCPh1f2vuu', 'Giáo viên', '', 1),
(151, 'nbphuc@caothang.edu.vn', '$2y$10$u8uylXOEdDjVzaSn.DXtoO.drK3xWCtXlnWB5ndHzp3UDHMIjsV5i', 'Giáo viên', '', 1),
(152, 'tttuan@caothang.edu.vn', '$2y$10$j8HeNP9t/QaSxYAx9YT14O83XdgZ.jOMFYw/Dl1SVezQNuDNmFR/e', 'Giáo viên', '', 1),
(153, 'vtvy@caothang.edu.vn', '$2y$10$Cg9ALNStbmR7c.1zc/QKtepyoStmNwMB1krM4Ky3ee68/JWQ6qskC', 'Giáo viên', '', 1),
(154, 'phamphuhoangson@caothang.edu.vn', '$2y$10$IKEyRnGFwz4WNU1BHluUKO6KtKAMgfPHyL/ZewRaUemRsRj/cvf9S', 'Giáo viên', '', 1),
(155, 'dmquan@caothang.edu.vn', '$2y$10$/Xo3sUNx6bgEZ/Aic5eopuaDMUdasmU06gYX73r7zXhZnsEKoroqC', 'Sinh viên', '77d8fe707c083a68589211f17796cc924445be59cec612df976411ca14e7c521', 1),
(156, 'Sv1', 'Sv1', 'Sinh viên', '', 0),
(157, 'Sv11', 'sv5', 'Sinh viên', '', 0),
(158, 'Sv12', 'sv5', 'Sinh viên', '', 0),
(159, 'Sv13', 'sv5', 'Sinh viên', '', 0),
(160, '0468201017@caothang.', '$2y$10$gUfpTP0vJ', 'Sinh viên', '', 1),
(161, '0468211018@caothang.', '$2y$10$IEEixVZq5', 'Sinh viên', '', 1),
(162, '0468211037@caothang.', '$2y$10$bs1xrUXDC', 'Sinh viên', '', 1),
(163, '0468211059@caothang.', '$2y$10$NN71ARE4x', 'Sinh viên', '', 1),
(164, '0468221003@caothang.', '$2y$10$E7jXTm3sE', 'Sinh viên', '', 1),
(165, '0468221007@caothang.', '$2y$10$m1uPA/SGI', 'Sinh viên', '', 1),
(166, '0468221008@caothang.', '$2y$10$jjASoxLd0', 'Sinh viên', '', 1),
(167, '0468221009@caothang.', '$2y$10$4ViPhbdro', 'Sinh viên', '', 1),
(168, '0468221010@caothang.', '$2y$10$/di4jl69T', 'Sinh viên', '', 1),
(169, '0468221013@caothang.', '$2y$10$pncfIm8n2', 'Sinh viên', '', 1),
(170, '0468221017@caothang.', '$2y$10$AscD7wl2e', 'Sinh viên', '', 1),
(171, '0468221018@caothang.', '$2y$10$YobXX/O67', 'Sinh viên', '', 1),
(172, '0468221020@caothang.', '$2y$10$EcFSlRyxR', 'Sinh viên', '', 1),
(173, '0468221031@caothang.', '$2y$10$x/eTxBG63', 'Sinh viên', '', 1),
(174, '0468221033@caothang.', '$2y$10$zJkshfRUN', 'Sinh viên', '', 1),
(175, '0468221036@caothang.', '$2y$10$Lv6fhK9v2', 'Sinh viên', '', 1),
(176, '0468221040@caothang.', '$2y$10$YXuEO.QiC', 'Sinh viên', '', 1),
(177, '0468221041@caothang.', '$2y$10$su3ey41Yo', 'Sinh viên', '', 1),
(178, '0468221042@caothang.', '$2y$10$uwAHyLU8i', 'Sinh viên', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tainguyen_dot`
--

CREATE TABLE `tainguyen_dot` (
  `ID` int(11) NOT NULL,
  `ID_File` int(11) NOT NULL,
  `ID_Dot` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tainguyen_dot`
--

INSERT INTO `tainguyen_dot` (`ID`, `ID_File`, `ID_Dot`) VALUES
(13, 40, 81),
(14, 40, 74),
(15, 40, 73),
(16, 40, 72),
(17, 40, 71),
(18, 40, 81),
(19, 40, 74),
(20, 40, 73),
(21, 40, 72),
(22, 40, 71);

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `ID` int(11) NOT NULL,
  `TieuDe` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `NoiDung` text DEFAULT NULL,
  `NgayDang` datetime DEFAULT NULL,
  `ID_TaiKhoan` int(11) DEFAULT NULL,
  `ID_Dot` int(11) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`ID`, `TieuDe`, `NoiDung`, `NgayDang`, `ID_TaiKhoan`, `ID_Dot`, `TrangThai`) VALUES
(8, 'DANH SÁCH PHÂN CÔNG HƯỚNG DẪN THỰC TẬP TỐT NGHIỆP CĐ TH 22', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o một số th&ocirc;ng tin về thực tập tốt nghiệp CĐTH 22 như sau:</p>\r\n\r\n<p><strong>- C&aacute;c mốc thời gian thực tập:</strong></p>\r\n\r\n<ul>\r\n	<li>Thời gian thực tập ch&iacute;nh thức:&nbsp;<strong>Từ 24/02/2025&nbsp;đến 20/04/2025</strong></li>\r\n	<li>B&aacute;o c&aacute;o v&agrave; chấm điểm (dự kiến):&nbsp;<strong>Từ 21/04/2025&nbsp;đến 04/05/2025</strong></li>\r\n</ul>\r\n\r\n<p><strong>- Danh s&aacute;ch ph&acirc;n c&ocirc;ng GVHD:</strong></p>\r\n\r\n<ul>\r\n	<li>Danh s&aacute;ch GVHD thực tập:&nbsp;<a href=\"https://tinyurl.com/huongdantt-cdth22\" target=\"_blank\">https://tinyurl.com/huongdantt-cdth22</a></li>\r\n	<li>Danh s&aacute;ch email li&ecirc;n hệ GVHD:&nbsp;<a href=\"https://tinyurl.com/gvhd-email\">https://tinyurl.com/gvhd-email</a></li>\r\n</ul>\r\n\r\n<p><strong>- C&aacute;c t&agrave;i nguy&ecirc;n:</strong></p>\r\n\r\n<ul>\r\n	<li>C&aacute;c t&agrave;i nguy&ecirc;n/ biểu mẫu:&nbsp;<a href=\"https://tinyurl.com/tainguyen-cdth22\" target=\"_blank\">https://tinyurl.com/tainguyen-cdth22</a></li>\r\n	<li>Đăng k&yacute; giấy giới thiệu thực tập:&nbsp;<a href=\"https://tinyurl.com/gthieutt-cdth22\" target=\"_blank\">https://tinyurl.com/gthieutt-cdth22</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Đối với sinh vi&ecirc;n kh&oacute;a trước đăng k&yacute; thực tập (gh&eacute;p) chưa c&oacute; trong danh s&aacute;ch hướng dẫn, sinh vi&ecirc;n li&ecirc;n hệ thầy Nguy&ecirc;n qua email:&nbsp;<a href=\"mailto:lvhnguyen@caothang.edu.vn\" target=\"_blank\">lvhnguyen@caothang.edu.vn</a>&nbsp;để được phổ biến th&ocirc;ng tin hướng dẫn.</p>\r\n\r\n<p>Khoa CNTT ./.</p>\r\n', '2025-06-10 15:35:07', 1, 73, 1),
(9, 'THỜI KHOÁ BIỂU HỌC KỲ PHỤ HÈ TẬP TRUNG NĂM HỌC 2024-2025', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o thời kho&aacute; biểu học kỳ phụ cho c&aacute;c lớp sau:&nbsp;<strong><a href=\"https://cntt.caothang.edu.vn/uploads/media/HKP/TKB_HKP2425_20250603_CNTT_UpdatedPhong.pdf\">Tại đ&acirc;y</a></strong></p>\r\n\r\n<p>Lưu &yacute;:</p>\r\n\r\n<p>1. Thời gian đăng k&yacute;: từ thời điểm c&ocirc;ng bố thời kho&aacute; biểu đến hết ng&agrave;y 23/6/2025.</p>\r\n\r\n<p>2. C&aacute;ch thức đăng k&yacute;:</p>\r\n\r\n<p>- Bước 1: Xem thời kho&aacute; biểu HKP H&egrave; tập trung tr&ecirc;n website Ph&ograve;ng Đ&agrave;o tạo v&agrave; chọn lớp c&oacute; thời gian ph&ugrave; hợp để tr&aacute;nh tr&ugrave;ng lịch học.</p>\r\n\r\n<p>- Bước 2: Đăng k&yacute; tr&ecirc;n phần mềm &ldquo;HỌC KỲ PHỤ&rdquo; tại m&aacute;y t&iacute;nh Ph&ograve;ng Đ&agrave;o tạo. Đối với sinh vi&ecirc;n đăng k&yacute; học cải thiện điểm số sử dụng phần mềm &ldquo;HỌC CẢI THIỆN ĐIỂM&rdquo;.</p>\r\n\r\n<p>- Bước 3: Đ&oacute;ng kinh ph&iacute; tại Ph&ograve;ng T&agrave;i ch&iacute;nh &ndash; Kế to&aacute;n.</p>\r\n\r\n<p>- Bước 4: Đi học theo thời kho&aacute; biểu.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p><strong>Khoa CNTT ./.</strong></p>\r\n', '2025-06-10 16:31:44', 1, 73, 1),
(10, ' MỞ LỚP HỌC KỲ PHỤ HÈ TẬP TRUNG NĂM HỌC 2024 - 2025', '<p>Căn cứ số lượng sinh vi&ecirc;n đăng k&yacute; học, Ph&ograve;ng Đ&agrave;o tạo dự kiến mở c&aacute;c lớp học kỳ phụ h&egrave; tập trung năm học 2024 &ndash; 2025 như sau:<br />\r\n<strong>I. Danh s&aacute;ch m&ocirc;n học dự kiến mở học kỳ phụ h&egrave; tập trung</strong></p>\r\n\r\n<table align=\"center\" cellspacing=\"0\" style=\"border-collapse:collapse; width:729.99px\">\r\n	<tbody>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:1px solid black; border-right:1px solid black; border-top:1px solid black; height:10px; text-align:center; vertical-align:middle; width:45px\">\r\n			<p><strong>STT</strong></p>\r\n			</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; white-space:nowrap; width:323px\">\r\n			<p><strong>M&ocirc;n học</strong></p>\r\n			</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; width:189px\"><strong>Khoa, bộ m&ocirc;n phụ tr&aacute;ch</strong></td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; width:189px\">\r\n			<p><strong>Số lớp mở</strong></p>\r\n			</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid black; border-left:none; border-right:1px solid black; border-top:1px solid black; height:20px; text-align:center; vertical-align:middle; width:76px\">\r\n			<p><strong>Ghi ch&uacute;</strong></p>\r\n			</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Cấu tr&uacute;c dữ liệu v&agrave; giải thuật</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">2</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Cơ sở dữ liệu</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐ</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">3</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Hệ quản trị CSDL</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐ</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">4</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Hệ quản trị CSDL</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐN</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">5</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Nhập m&ocirc;n lập tr&igrave;nh</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CĐ</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">6</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Toán rời rạc và lý thuy&ecirc;́t đ&ocirc;̀ thị</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">7</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">TH Lập tr&igrave;nh hướng đối tượng</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">8</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Thực tập Nhập m&ocirc;n lập tr&igrave;nh</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid #000000; border-left:none; border-right:1px solid #000000; border-top:none; height:20px; text-align:center; width:76px\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">9</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Thực tập Cấu tr&uacute;c dữ liệu v&agrave; giải thuật</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">10</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:left; white-space:nowrap\">Thực tập Thiết kế Website</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">CNPM</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:#000000; border-left:#000000; border-right:#000000; border-top:#000000; text-align:center\">1</td>\r\n			<td style=\"background-color:#ffffff; border-bottom:1px solid #000000; border-left:none; border-right:1px solid #000000; border-top:none; height:20px; text-align:center; width:76px\">&nbsp;</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n\r\n<p><strong>II. Thời gian v&agrave; c&aacute;ch thức đăng k&yacute;</strong></p>\r\n\r\n<p>1. Thời gian đăng k&yacute;: từ thời điểm c&ocirc;ng bố thời kho&aacute; biểu đến hết ng&agrave;y 23/6/2025.</p>\r\n\r\n<p>2. C&aacute;ch thức đăng k&yacute;:</p>\r\n\r\n<p>- Bước 1: Theo d&otilde;i Thời kho&aacute; biểu HKP H&egrave; tập trung tr&ecirc;n website Ph&ograve;ng Đ&agrave;o tạo.</p>\r\n\r\n<p>- Bước 2: Đăng k&yacute; tr&ecirc;n phần mềm &ldquo;HỌC KỲ PHỤ&rdquo; tại m&aacute;y t&iacute;nh Ph&ograve;ng Đ&agrave;o tạo. Đối với sinh vi&ecirc;n đăng k&yacute; học cải thiện điểm số sử dụng phần mềm &ldquo;HỌC CẢI THIỆN ĐIỂM&rdquo;.</p>\r\n\r\n<p>- Bước 3: Đ&oacute;ng kinh ph&iacute; tại Ph&ograve;ng T&agrave;i ch&iacute;nh &ndash; Kế to&aacute;n.</p>\r\n\r\n<p>- Bước 4: Đi học theo thời kho&aacute; biểu.</p>\r\n\r\n<p>Trường hợp c&aacute;c lớp bị huỷ do kh&ocirc;ng đủ số lượng, sinh vi&ecirc;n li&ecirc;n hệ b&agrave;n số 3 Ph&ograve;ng Đ&agrave;o tạo để được hướng dẫn thủ tục ho&agrave;n lại kinh ph&iacute;.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Chi tiết:&nbsp;<a href=\"https://daotao.caothang.edu.vn/Thong-bao-hoc-ky-phu,-hoc-ghep/70-Thong-bao-mo-lop-hoc-ky-phu-he-tap-trung-nam-hoc-2024---2025-f637a2fa74bc83dd069f813e57cb9422.html\">https://daotao.caothang.edu.vn/Thong-bao-hoc-ky-phu,-hoc-ghep/70-Thong-bao-mo-lop-hoc-ky-phu-he-tap-trung-nam-hoc-2024---2025</a></p>\r\n\r\n<p><strong>Khoa CNTT./.</strong></p>\r\n', '2025-06-10 17:13:12', 2, 73, 1),
(16, 'Uống nước chưa?', '<p>a</p>\r\n', '2025-06-24 10:05:27', 113, 81, 0),
(17, 'a', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o một số th&ocirc;ng tin về thực tập tốt nghiệp CĐTH 22 như sau:</p>\r\n\r\n<p><strong>- C&aacute;c mốc thời gian thực tập:</strong></p>\r\n\r\n<ul>\r\n	<li>Thời gian thực tập ch&iacute;nh thức:&nbsp;<strong>Từ 24/02/2025&nbsp;đến 20/04/2025</strong></li>\r\n	<li>B&aacute;o c&aacute;o v&agrave; chấm điểm (dự kiến):&nbsp;<strong>Từ 21/04/2025&nbsp;đến 04/05/2025</strong></li>\r\n</ul>\r\n\r\n<p><strong>- Danh s&aacute;ch ph&acirc;n c&ocirc;ng GVHD:</strong></p>\r\n\r\n<ul>\r\n	<li>Danh s&aacute;ch GVHD thực tập:&nbsp;<a href=\"https://tinyurl.com/huongdantt-cdth22\" target=\"_blank\">https://tinyurl.com/huongdantt-cdth22</a></li>\r\n	<li>Danh s&aacute;ch email li&ecirc;n hệ GVHD:&nbsp;<a href=\"https://tinyurl.com/gvhd-email\">https://tinyurl.com/gvhd-email</a></li>\r\n</ul>\r\n\r\n<p><strong>- C&aacute;c t&agrave;i nguy&ecirc;n:</strong></p>\r\n\r\n<ul>\r\n	<li>C&aacute;c t&agrave;i nguy&ecirc;n/ biểu mẫu:&nbsp;<a href=\"https://tinyurl.com/tainguyen-cdth22\" target=\"_blank\">https://tinyurl.com/tainguyen-cdth22</a></li>\r\n	<li>Đăng k&yacute; giấy giới thiệu thực tập:&nbsp;<a href=\"https://tinyurl.com/gthieutt-cdth22\" target=\"_blank\">https://tinyurl.com/gthieutt-cdth22</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Đối với sinh vi&ecirc;n kh&oacute;a trước đăng k&yacute; thực tập (gh&eacute;p) chưa c&oacute; trong danh s&aacute;ch hướng dẫn, sinh vi&ecirc;n li&ecirc;n hệ thầy Nguy&ecirc;n qua email:&nbsp;<a href=\"mailto:lvhnguyen@caothang.edu.vn\" target=\"_blank\">lvhnguyen@caothang.edu.vn</a>&nbsp;để được phổ biến th&ocirc;ng tin hướng dẫn.</p>\r\n\r\n<p>Khoa CNTT ./.</p>\r\n', '2025-06-24 10:57:09', 113, 73, 0),
(18, 'a', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o một số th&ocirc;ng tin về thực tập tốt nghiệp CĐTH 22 như sau:</p>\r\n\r\n<p><strong>- C&aacute;c mốc thời gian thực tập:</strong></p>\r\n\r\n<ul>\r\n	<li>Thời gian thực tập ch&iacute;nh thức:&nbsp;<strong>Từ 24/02/2025&nbsp;đến 20/04/2025</strong></li>\r\n	<li>B&aacute;o c&aacute;o v&agrave; chấm điểm (dự kiến):&nbsp;<strong>Từ 21/04/2025&nbsp;đến 04/05/2025</strong></li>\r\n</ul>\r\n\r\n<p><strong>- Danh s&aacute;ch ph&acirc;n c&ocirc;ng GVHD:</strong></p>\r\n\r\n<ul>\r\n	<li>Danh s&aacute;ch GVHD thực tập:&nbsp;<a href=\"https://tinyurl.com/huongdantt-cdth22\" target=\"_blank\">https://tinyurl.com/huongdantt-cdth22</a></li>\r\n	<li>Danh s&aacute;ch email li&ecirc;n hệ GVHD:&nbsp;<a href=\"https://tinyurl.com/gvhd-email\">https://tinyurl.com/gvhd-email</a></li>\r\n</ul>\r\n\r\n<p><strong>- C&aacute;c t&agrave;i nguy&ecirc;n:</strong></p>\r\n\r\n<ul>\r\n	<li>C&aacute;c t&agrave;i nguy&ecirc;n/ biểu mẫu:&nbsp;<a href=\"https://tinyurl.com/tainguyen-cdth22\" target=\"_blank\">https://tinyurl.com/tainguyen-cdth22</a></li>\r\n	<li>Đăng k&yacute; giấy giới thiệu thực tập:&nbsp;<a href=\"https://tinyurl.com/gthieutt-cdth22\" target=\"_blank\">https://tinyurl.com/gthieutt-cdth22</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Đối với sinh vi&ecirc;n kh&oacute;a trước đăng k&yacute; thực tập (gh&eacute;p) chưa c&oacute; trong danh s&aacute;ch hướng dẫn, sinh vi&ecirc;n li&ecirc;n hệ thầy Nguy&ecirc;n qua email:&nbsp;<a href=\"mailto:lvhnguyen@caothang.edu.vn\" target=\"_blank\">lvhnguyen@caothang.edu.vn</a>&nbsp;để được phổ biến th&ocirc;ng tin hướng dẫn.</p>\r\n\r\n<p>Khoa CNTT ./.</p>\r\n', '2025-06-24 10:57:45', 113, 74, 0),
(19, 'Uống nước chưa?', '<p>OK</p>\r\n', '2025-06-26 16:40:58', 116, 71, 0),
(20, 'b', '<p><strong>b</strong></p>\r\n', '2025-06-29 15:29:10', 116, 71, 0),
(21, 'Đăng thông báo', '<p><strong>Đợt thực tập:</strong>&nbsp;CĐTH25-4</p>\r\n\r\n<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o một số th&ocirc;ng tin về thực tập tốt nghiệp CĐTH 22 như sau:</p>\r\n\r\n<p><strong>- C&aacute;c mốc thời gian thực tập:</strong></p>\r\n\r\n<ul>\r\n	<li>Thời gian thực tập ch&iacute;nh thức:&nbsp;<strong>Từ 24/02/2025&nbsp;đến 20/04/2025</strong></li>\r\n	<li>B&aacute;o c&aacute;o v&agrave; chấm điểm (dự kiến):&nbsp;<strong>Từ 21/04/2025&nbsp;đến 04/05/2025</strong></li>\r\n</ul>\r\n\r\n<p><strong>- Danh s&aacute;ch ph&acirc;n c&ocirc;ng GVHD:</strong></p>\r\n\r\n<ul>\r\n	<li>Danh s&aacute;ch GVHD thực tập:&nbsp;<a href=\"https://tinyurl.com/huongdantt-cdth22\" target=\"_blank\">https://tinyurl.com/huongdantt-cdth22</a></li>\r\n	<li>Danh s&aacute;ch email li&ecirc;n hệ GVHD:&nbsp;<a href=\"https://tinyurl.com/gvhd-email\">https://tinyurl.com/gvhd-email</a></li>\r\n</ul>\r\n\r\n<p><strong>- C&aacute;c t&agrave;i nguy&ecirc;n:</strong></p>\r\n\r\n<ul>\r\n	<li>C&aacute;c t&agrave;i nguy&ecirc;n/ biểu mẫu:&nbsp;<a href=\"https://tinyurl.com/tainguyen-cdth22\" target=\"_blank\">https://tinyurl.com/tainguyen-cdth22</a></li>\r\n	<li>Đăng k&yacute; giấy giới thiệu thực tập:&nbsp;<a href=\"https://tinyurl.com/gthieutt-cdth22\" target=\"_blank\">https://tinyurl.com/gthieutt-cdth22</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Đối với sinh vi&ecirc;n kh&oacute;a trước đăng k&yacute; thực tập (gh&eacute;p) chưa c&oacute; trong danh s&aacute;ch hướng dẫn, sinh vi&ecirc;n li&ecirc;n hệ thầy Nguy&ecirc;n qua email:&nbsp;<a href=\"mailto:lvhnguyen@caothang.edu.vn\" target=\"_blank\">lvhnguyen@caothang.edu.vn</a>&nbsp;để được phổ biến th&ocirc;ng tin hướng dẫn.</p>\r\n\r\n<p>Khoa CNTT ./.</p>\r\n', '2025-07-01 15:18:19', 116, 71, 0),
(22, 'DANH SÁCH PHÂN CÔNG HƯỚNG DẪN THỰC TẬP TỐT NGHIỆP CĐ TH 25', '<p>Khoa C&ocirc;ng nghệ th&ocirc;ng tin xin th&ocirc;ng b&aacute;o một số th&ocirc;ng tin về thực tập tốt nghiệp CĐTH 22 như sau:</p>\r\n\r\n<p><strong>- C&aacute;c mốc thời gian thực tập:</strong></p>\r\n\r\n<ul>\r\n	<li>Thời gian thực tập ch&iacute;nh thức:&nbsp;<strong>Từ 24/02/2025&nbsp;đến 20/04/2025</strong></li>\r\n	<li>B&aacute;o c&aacute;o v&agrave; chấm điểm (dự kiến):&nbsp;<strong>Từ 21/04/2025&nbsp;đến 04/05/2025</strong></li>\r\n</ul>\r\n\r\n<p><strong>- Danh s&aacute;ch ph&acirc;n c&ocirc;ng GVHD:</strong></p>\r\n\r\n<ul>\r\n	<li>Danh s&aacute;ch GVHD thực tập:&nbsp;<a href=\"https://tinyurl.com/huongdantt-cdth22\" target=\"_blank\">https://tinyurl.com/huongdantt-cdth22</a></li>\r\n	<li>Danh s&aacute;ch email li&ecirc;n hệ GVHD:&nbsp;<a href=\"https://tinyurl.com/gvhd-email\">https://tinyurl.com/gvhd-email</a></li>\r\n</ul>\r\n\r\n<p><strong>- C&aacute;c t&agrave;i nguy&ecirc;n:</strong></p>\r\n\r\n<ul>\r\n	<li>C&aacute;c t&agrave;i nguy&ecirc;n/ biểu mẫu:&nbsp;<a href=\"https://tinyurl.com/tainguyen-cdth22\" target=\"_blank\">https://tinyurl.com/tainguyen-cdth22</a></li>\r\n	<li>Đăng k&yacute; giấy giới thiệu thực tập:&nbsp;<a href=\"https://tinyurl.com/gthieutt-cdth22\" target=\"_blank\">https://tinyurl.com/gthieutt-cdth22</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Đối với sinh vi&ecirc;n kh&oacute;a trước đăng k&yacute; thực tập (gh&eacute;p) chưa c&oacute; trong danh s&aacute;ch hướng dẫn, sinh vi&ecirc;n li&ecirc;n hệ thầy Nguy&ecirc;n qua email:&nbsp;<a href=\"mailto:lvhnguyen@caothang.edu.vn\" target=\"_blank\">lvhnguyen@caothang.edu.vn</a>&nbsp;để được phổ biến th&ocirc;ng tin hướng dẫn.</p>\r\n\r\n<p>Khoa CNTT ./.</p>\r\n', '2025-07-01 15:26:44', 116, 71, 1);

-- --------------------------------------------------------

--
-- Table structure for table `thongbao_xem`
--

CREATE TABLE `thongbao_xem` (
  `ID_TaiKhoan` int(11) NOT NULL,
  `ID_ThongBao` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thongbao_xem`
--

INSERT INTO `thongbao_xem` (`ID_TaiKhoan`, `ID_ThongBao`) VALUES
(155, 19),
(155, 21);

-- --------------------------------------------------------

--
-- Table structure for table `tongket`
--

CREATE TABLE `tongket` (
  `ID` int(11) NOT NULL,
  `IDSV` int(11) DEFAULT NULL,
  `ID_GVHD` int(11) DEFAULT NULL,
  `Diem` float DEFAULT NULL,
  `DanhGia` text DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ID_TaiKhoan`);

--
-- Indexes for table `baocaotongket`
--
ALTER TABLE `baocaotongket`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `unique_giaovien_dot` (`ID_TaiKhoan`,`ID_Dot`),
  ADD KEY `idx_id_taikhoan` (`ID_TaiKhoan`),
  ADD KEY `idx_id_dot` (`ID_Dot`),
  ADD KEY `idx_trangthai` (`TrangThai`);

--
-- Indexes for table `canbokhoa`
--
ALTER TABLE `canbokhoa`
  ADD PRIMARY KEY (`ID_TaiKhoan`);

--
-- Indexes for table `cauhinh`
--
ALTER TABLE `cauhinh`
  ADD PRIMARY KEY (`ID`);

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
-- Indexes for table `congviec_baocao`
--
ALTER TABLE `congviec_baocao`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDSV` (`IDSV`),
  ADD KEY `ID_Dot` (`ID_Dot`);

--
-- Indexes for table `diem_tongket`
--
ALTER TABLE `diem_tongket`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_diem_sv` (`ID_SV`),
  ADD KEY `fk_diem_dot` (`ID_Dot`);

--
-- Indexes for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `NguoiQuanLy` (`NguoiQuanLy`),
  ADD KEY `NguoiMoDot` (`NguoiMoDot`);

--
-- Indexes for table `dot_giaovien`
--
ALTER TABLE `dot_giaovien`
  ADD PRIMARY KEY (`ID_Dot`,`ID_GVHD`),
  ADD KEY `fk_dot_giaovien_gv` (`ID_GVHD`);

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
  ADD KEY `IdSinhVien` (`IdSinhVien`),
  ADD KEY `fk_giaygioithieu_dotthuctap` (`id_dot`),
  ADD KEY `fk_sinhvien_nguoinhan` (`id_nguoinhan`);

--
-- Indexes for table `khaosat`
--
ALTER TABLE `khaosat`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_NguoiTao` (`NguoiTao`),
  ADD KEY `NguoiNhan` (`NguoiNhan`),
  ADD KEY `ID_Dot` (`ID_Dot`);

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
-- Indexes for table `tainguyen_dot`
--
ALTER TABLE `tainguyen_dot`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_File` (`ID_File`),
  ADD KEY `ID_Dot` (`ID_Dot`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_TaiKhoan` (`ID_TaiKhoan`),
  ADD KEY `ID_Dot` (`ID_Dot`);

--
-- Indexes for table `thongbao_xem`
--
ALTER TABLE `thongbao_xem`
  ADD PRIMARY KEY (`ID_TaiKhoan`,`ID_ThongBao`),
  ADD KEY `ID_ThongBao` (`ID_ThongBao`);

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
-- AUTO_INCREMENT for table `baocaotongket`
--
ALTER TABLE `baocaotongket`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cauhinh`
--
ALTER TABLE `cauhinh`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cauhoikhaosat`
--
ALTER TABLE `cauhoikhaosat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cautraloi`
--
ALTER TABLE `cautraloi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `congty`
--
ALTER TABLE `congty`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `congviec_baocao`
--
ALTER TABLE `congviec_baocao`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `diem_tongket`
--
ALTER TABLE `diem_tongket`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `file`
--
ALTER TABLE `file`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `giaygioithieu`
--
ALTER TABLE `giaygioithieu`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `khaosat`
--
ALTER TABLE `khaosat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `phanhoikhaosat`
--
ALTER TABLE `phanhoikhaosat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `ID_TaiKhoan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `tainguyen_dot`
--
ALTER TABLE `tainguyen_dot`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tongket`
--
ALTER TABLE `tongket`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

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
-- Constraints for table `congviec_baocao`
--
ALTER TABLE `congviec_baocao`
  ADD CONSTRAINT `congviec_baocao_ibfk_1` FOREIGN KEY (`IDSV`) REFERENCES `sinhvien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `congviec_baocao_ibfk_2` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`);

--
-- Constraints for table `diem_tongket`
--
ALTER TABLE `diem_tongket`
  ADD CONSTRAINT `fk_diem_dot` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diem_sv` FOREIGN KEY (`ID_SV`) REFERENCES `sinhvien` (`ID_TaiKhoan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  ADD CONSTRAINT `fk_nguoimodot` FOREIGN KEY (`NguoiMoDot`) REFERENCES `taikhoan` (`ID_TaiKhoan`),
  ADD CONSTRAINT `fk_nguoiquanlydot` FOREIGN KEY (`NguoiQuanLy`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `dot_giaovien`
--
ALTER TABLE `dot_giaovien`
  ADD CONSTRAINT `fk_dot_giaovien_dot` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dot_giaovien_gv` FOREIGN KEY (`ID_GVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `file`
--
ALTER TABLE `file`
  ADD CONSTRAINT `file_ibfk_1` FOREIGN KEY (`ID_SV`) REFERENCES `sinhvien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `file_ibfk_2` FOREIGN KEY (`ID_GVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `fk_file_sinhvien` FOREIGN KEY (`ID_SV`) REFERENCES `sinhvien` (`ID_TaiKhoan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD CONSTRAINT `giaovien_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `giaygioithieu`
--
ALTER TABLE `giaygioithieu`
  ADD CONSTRAINT `fk_giaygioithieu_dotthuctap` FOREIGN KEY (`id_dot`) REFERENCES `dotthuctap` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sinhvien_nguoinhan` FOREIGN KEY (`id_nguoinhan`) REFERENCES `sinhvien` (`ID_TaiKhoan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `giaygioithieu_ibfk_1` FOREIGN KEY (`IdSinhVien`) REFERENCES `sinhvien` (`ID_TaiKhoan`);

--
-- Constraints for table `khaosat`
--
ALTER TABLE `khaosat`
  ADD CONSTRAINT `fk_NguoiTao` FOREIGN KEY (`NguoiTao`) REFERENCES `taikhoan` (`ID_TaiKhoan`),
  ADD CONSTRAINT `fk_khaosat_dot` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`);

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
-- Constraints for table `tainguyen_dot`
--
ALTER TABLE `tainguyen_dot`
  ADD CONSTRAINT `tainguyen_dot_ibfk_1` FOREIGN KEY (`ID_File`) REFERENCES `file` (`ID`),
  ADD CONSTRAINT `tainguyen_dot_ibfk_2` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`);

--
-- Constraints for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `fk_ID_Dot` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`),
  ADD CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `thongbao_xem`
--
ALTER TABLE `thongbao_xem`
  ADD CONSTRAINT `thongbao_xem_ibfk_1` FOREIGN KEY (`ID_TaiKhoan`) REFERENCES `taikhoan` (`ID_TaiKhoan`) ON DELETE CASCADE,
  ADD CONSTRAINT `thongbao_xem_ibfk_2` FOREIGN KEY (`ID_ThongBao`) REFERENCES `thongbao` (`ID`) ON DELETE CASCADE;

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
