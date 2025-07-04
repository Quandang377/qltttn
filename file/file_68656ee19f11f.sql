-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 06:08 PM
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
-- Table structure for table `dotthuctap`
--

CREATE TABLE `dotthuctap` (
  `ID` int(11) NOT NULL,
  `TenDot` varchar(50) DEFAULT NULL,
  `Nam` varchar(5) DEFAULT NULL,
  `Loai` varchar(25) DEFAULT NULL,
  `ThoiGianBatDau` date DEFAULT NULL,
  `ThoiGianKetThuc` date DEFAULT NULL,
  `TrangThai` tinyint(4) DEFAULT NULL,
  `NguoiQuanLy` int(11) DEFAULT NULL,
  `NguoiMoDot` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `Loai` varchar(10) NOT NULL,
  `NgayNop` datetime DEFAULT NULL,
  `Ten` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for dumped tables
--

--
-- Indexes for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `NguoiQuanLy` (`NguoiQuanLy`),
  ADD KEY `NguoiMoDot` (`NguoiMoDot`);

--
-- Indexes for table `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_SV` (`ID_SV`),
  ADD KEY `ID_GVHD` (`ID_GVHD`);

--
-- Indexes for table `tainguyen_dot`
--
ALTER TABLE `tainguyen_dot`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_File` (`ID_File`),
  ADD KEY `ID_Dot` (`ID_Dot`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file`
--
ALTER TABLE `file`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tainguyen_dot`
--
ALTER TABLE `tainguyen_dot`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dotthuctap`
--
ALTER TABLE `dotthuctap`
  ADD CONSTRAINT `fk_nguoimodot` FOREIGN KEY (`NguoiMoDot`) REFERENCES `taikhoan` (`ID_TaiKhoan`),
  ADD CONSTRAINT `fk_nguoiquanlydot` FOREIGN KEY (`NguoiQuanLy`) REFERENCES `taikhoan` (`ID_TaiKhoan`);

--
-- Constraints for table `file`
--
ALTER TABLE `file`
  ADD CONSTRAINT `file_ibfk_1` FOREIGN KEY (`ID_SV`) REFERENCES `sinhvien` (`ID_TaiKhoan`),
  ADD CONSTRAINT `file_ibfk_2` FOREIGN KEY (`ID_GVHD`) REFERENCES `giaovien` (`ID_TaiKhoan`);

--
-- Constraints for table `tainguyen_dot`
--
ALTER TABLE `tainguyen_dot`
  ADD CONSTRAINT `tainguyen_dot_ibfk_1` FOREIGN KEY (`ID_File`) REFERENCES `file` (`ID`),
  ADD CONSTRAINT `tainguyen_dot_ibfk_2` FOREIGN KEY (`ID_Dot`) REFERENCES `dotthuctap` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
