<?php
// Bắt đầu session an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../template/config.php';
require_once __DIR__ . '/../../middleware/check_role.php';

echo "<h2>DEBUG: Kiểm tra đường dẫn file trong tainguyen2.php</h2>";

// Lấy thông tin sinh viên và đợt thực tập
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
echo "<p><strong>ID Tài khoản:</strong> " . ($idTaiKhoan ?: 'NULL') . "</p>";

if ($idTaiKhoan) {
    $stmt = $conn->prepare("SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $idDotHienTai = $stmt->fetchColumn();
    echo "<p><strong>ID Đợt hiện tại:</strong> " . ($idDotHienTai ?: 'NULL') . "</p>";
    
    if ($idDotHienTai) {
        // Lấy danh sách tài nguyên cho đợt hiện tại
        $stmt = $conn->prepare("
            SELECT 
                f.ID,
                f.TenFile,
                f.TenHienThi,
                f.NgayNop,
                f.DIR,
                dt.TenDot
            FROM file f
            INNER JOIN tainguyen_dot td ON f.ID = td.id_file
            INNER JOIN dotthuctap dt ON td.id_dot = dt.ID
            WHERE f.Loai = 'Tainguyen' 
            AND f.TrangThai = 1 
            AND td.id_dot = ?
            ORDER BY f.NgayNop DESC
        ");
        $stmt->execute([$idDotHienTai]);
        $taiNguyenList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Danh sách tài nguyên (" . count($taiNguyenList) . " files):</h3>";
        
        if (empty($taiNguyenList)) {
            echo "<p>Không có tài nguyên nào!</p>";
        } else {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Tên file</th><th>Tên hiển thị</th><th>Đường dẫn gốc</th><th>Đường dẫn cuối</th><th>Tồn tại?</th><th>Kích thước</th><th>Link download</th></tr>";
            
            foreach ($taiNguyenList as $file) {
                $originalPath = $file['DIR'];
                
                // Kiểm tra nhiều khả năng đường dẫn
                $possiblePaths = [
                    $originalPath, // Đường dẫn gốc
                    __DIR__ . '/../../file/' . basename($originalPath), // Đường dẫn tương đối với tên file
                    __DIR__ . '/../../file/' . $file['TenFile'], // Đường dẫn với tên file gốc
                ];
                
                // Nếu đường dẫn gốc có chứa "file\" hoặc "file/", lấy phần sau đó
                if (strpos($originalPath, 'file\\') !== false || strpos($originalPath, 'file/') !== false) {
                    $fileName = basename($originalPath);
                    $possiblePaths[] = __DIR__ . '/../../file/' . $fileName;
                }
                
                // Tìm đường dẫn file tồn tại
                $foundPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $foundPath = $path;
                        break;
                    }
                }
                
                $finalPath = $foundPath ?: $originalPath;
                $fileExists = file_exists($finalPath);
                $fileSize = $fileExists ? filesize($finalPath) : 0;
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($file['ID']) . "</td>";
                echo "<td>" . htmlspecialchars($file['TenFile']) . "</td>";
                echo "<td>" . htmlspecialchars($file['TenHienThi'] ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($originalPath) . "</td>";
                echo "<td>" . htmlspecialchars($finalPath) . "</td>";
                echo "<td>" . ($fileExists ? 'CÓ' : 'KHÔNG') . "</td>";
                echo "<td>" . ($fileExists ? number_format($fileSize) . ' bytes' : 'N/A') . "</td>";
                echo "<td><a href='download_tainguyen.php?file=" . urlencode($file['ID']) . "&name=" . urlencode($file['TenHienThi'] ?: $file['TenFile']) . "' target='_blank'>Tải xuống</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}
?>
