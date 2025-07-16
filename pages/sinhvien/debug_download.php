<?php
// Bắt đầu session an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../template/config.php';
require_once __DIR__ . '/../../middleware/check_role.php';

echo "<h2>DEBUG: Kiểm tra tải file tainguyen</h2>";

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
            echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Tên file</th><th>Tên hiển thị</th><th>Đường dẫn DB</th><th>Đường dẫn thực tế</th><th>Tồn tại?</th><th>Kích thước</th><th>Link download</th><th>Test download</th></tr>";
            
            foreach ($taiNguyenList as $file) {
                $originalPath = $file['DIR'];
                
                // Chuyển đổi đường dẫn database thành đường dẫn phù hợp với hosting
                $correctedPath = null;
                
                // Nếu đường dẫn chứa "C:\xampp\htdocs\datn\file\" (localhost), chuyển thành đường dẫn tương đối
                if (strpos($originalPath, 'C:\\xampp\\htdocs\\datn\\file\\') !== false) {
                    $fileName = basename($originalPath);
                    $correctedPath = __DIR__ . '/../../file/' . $fileName;
                }
                // Nếu đường dẫn chứa "file\" hoặc "file/" trong bất kỳ vị trí nào
                else if (strpos($originalPath, 'file\\') !== false || strpos($originalPath, 'file/') !== false) {
                    $fileName = basename($originalPath);
                    $correctedPath = __DIR__ . '/../../file/' . $fileName;
                }
                // Nếu đường dẫn bắt đầu bằng "file/"
                else if (strpos($originalPath, 'file/') === 0) {
                    $correctedPath = __DIR__ . '/../../' . $originalPath;
                }
                // Nếu đường dẫn chỉ là tên file
                else if (strpos($originalPath, '/') === false && strpos($originalPath, '\\') === false) {
                    $correctedPath = __DIR__ . '/../../file/' . $originalPath;
                }
                // Nếu đường dẫn đã đúng định dạng tương đối
                else {
                    $correctedPath = $originalPath;
                }
                
                // Kiểm tra nhiều khả năng đường dẫn
                $possiblePaths = [
                    $correctedPath, // Đường dẫn đã chuyển đổi
                    __DIR__ . '/../../file/' . basename($originalPath), // Đường dẫn tương đối với tên file
                    __DIR__ . '/../../file/' . $file['TenFile'], // Đường dẫn với tên file gốc
                    $originalPath, // Đường dẫn gốc (fallback)
                ];
                
                // Tìm đường dẫn file tồn tại
                $foundPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $foundPath = $path;
                        break;
                    }
                }
                
                $finalPath = $foundPath ?: $correctedPath;
                $fileExists = file_exists($finalPath);
                $fileSize = $fileExists ? filesize($finalPath) : 0;
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($file['ID']) . "</td>";
                echo "<td>" . htmlspecialchars($file['TenFile']) . "</td>";
                echo "<td>" . htmlspecialchars($file['TenHienThi'] ?: 'N/A') . "</td>";
                echo "<td style='font-size: 12px; max-width: 200px; word-break: break-all;'>" . htmlspecialchars($originalPath) . "</td>";
                echo "<td style='font-size: 12px; max-width: 200px; word-break: break-all;'>" . htmlspecialchars($finalPath) . "</td>";
                echo "<td style='color: " . ($fileExists ? 'green' : 'red') . ";'>" . ($fileExists ? 'CÓ' : 'KHÔNG') . "</td>";
                echo "<td>" . ($fileExists ? number_format($fileSize) . ' bytes' : 'N/A') . "</td>";
                echo "<td><a href='download_tainguyen.php?file=" . urlencode($file['ID']) . "&name=" . urlencode($file['TenHienThi'] ?: $file['TenFile']) . "' target='_blank'>Download</a></td>";
                echo "<td><a href='test_download.php?id=" . urlencode($file['ID']) . "' target='_blank'>Test</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

// Kiểm tra thư mục file
echo "<h3>Kiểm tra thư mục file:</h3>";
$fileDir = __DIR__ . '/../../file/';
echo "<p><strong>Đường dẫn thư mục:</strong> " . $fileDir . "</p>";
echo "<p><strong>Thư mục tồn tại:</strong> " . (is_dir($fileDir) ? 'CÓ' : 'KHÔNG') . "</p>";

if (is_dir($fileDir)) {
    echo "<p><strong>Quyền thư mục:</strong> " . substr(sprintf('%o', fileperms($fileDir)), -4) . "</p>";
    echo "<h4>Danh sách file trong thư mục:</h4>";
    $files = scandir($fileDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $fileDir . $file;
            $fileSize = is_file($fullPath) ? filesize($fullPath) : 0;
            $fileType = is_file($fullPath) ? 'FILE' : 'DIR';
            echo "<li><strong>" . htmlspecialchars($file) . "</strong> - " . $fileType . " - " . number_format($fileSize) . " bytes</li>";
        }
    }
    echo "</ul>";
}
?>
