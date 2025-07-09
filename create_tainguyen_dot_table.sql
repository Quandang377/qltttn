-- Tạo bảng tainguyen_dot để liên kết tài nguyên với đợt thực tập
CREATE TABLE IF NOT EXISTS `tainguyen_dot` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `id_file` int(11) NOT NULL COMMENT 'ID file từ bảng file',
  `id_dot` int(11) NOT NULL COMMENT 'ID đợt thực tập từ bảng dotthuctap',
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày tạo liên kết',
  `TrangThai` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái: 1=Hoạt động, 0=Không hoạt động',
  PRIMARY KEY (`ID`),
  KEY `idx_file` (`id_file`),
  KEY `idx_dot` (`id_dot`),
  FOREIGN KEY (`id_file`) REFERENCES `file` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_dot`) REFERENCES `dotthuctap` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng liên kết tài nguyên với đợt thực tập';

-- Thêm dữ liệu mẫu (nếu cần)
-- INSERT INTO `tainguyen_dot` (`id_file`, `id_dot`, `TrangThai`) VALUES
-- (1, 71, 1),
-- (2, 71, 1);
