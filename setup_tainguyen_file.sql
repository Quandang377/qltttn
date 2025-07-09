-- Script tạo bảng tainguyen_file và foreign key
-- Kiểm tra và tạo bảng tainguyen_file nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS `tainguyen_file` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `id_file` INT(11) NOT NULL COMMENT 'Foreign key tới bảng file',
    `id_dot` INT(11) NOT NULL COMMENT 'Foreign key tới bảng dotthuctap',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_file_dot` (`id_file`, `id_dot`),
    KEY `idx_id_file` (`id_file`),
    KEY `idx_id_dot` (`id_dot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm foreign key constraints
ALTER TABLE `tainguyen_file` 
ADD CONSTRAINT `fk_tainguyen_file_file` 
FOREIGN KEY (`id_file`) REFERENCES `file` (`ID`) 
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tainguyen_file` 
ADD CONSTRAINT `fk_tainguyen_file_dot` 
FOREIGN KEY (`id_dot`) REFERENCES `dotthuctap` (`ID`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Chèn dữ liệu mẫu nếu cần (uncomment các dòng dưới nếu muốn)
-- INSERT IGNORE INTO `tainguyen_file` (`id_file`, `id_dot`) 
-- SELECT f.ID, 1 FROM `file` f 
-- WHERE f.Loai = 'Tainguyen' AND f.TrangThai = 1 
-- AND NOT EXISTS (SELECT 1 FROM `tainguyen_file` tf WHERE tf.id_file = f.ID);
