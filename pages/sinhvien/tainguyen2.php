<?php
// Bắt đầu session an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../middleware/check_role.php';
require_once __DIR__ . '/../../template/config.php';

// Lấy thông tin sinh viên và đợt thực tập
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$stmt = $conn->prepare("SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDotHienTai = $stmt->fetchColumn();

// Debug info (tạm thời)
$debugInfo = [
    'ID_TaiKhoan' => $idTaiKhoan,
    'ID_Dot' => $idDotHienTai,
    'User_Role' => $_SESSION['user']['VaiTro'] ?? 'N/A',
    'Session_Valid' => !empty($_SESSION['user'])
];

// Chỉ sử dụng đợt hiện tại
$selectedDot = $idDotHienTai;

if (!$selectedDot) {
    $errorMsg = "Bạn chưa được phân vào đợt thực tập nào!";
    $taiNguyenList = [];
    $dotInfo = null;
} else {
    // Lấy thông tin đợt hiện tại
    $stmt = $conn->prepare("SELECT TenDot, Nam, ThoiGianBatDau, ThoiGianKetThuc FROM dotthuctap WHERE ID = ?");
    $stmt->execute([$selectedDot]);
    $dotInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy danh sách tài nguyên cho đợt hiện tại (loại bỏ duplicate)
    $stmt = $conn->prepare("
    SELECT DISTINCT
        f.ID,
        f.TenFile,
        f.TenHienThi,
        f.NgayNop,
        f.DIR,
        dt.TenDot
    FROM file f
    LEFT JOIN tainguyen_dot td ON f.ID = td.id_file
    LEFT JOIN dotthuctap dt ON td.id_dot = dt.ID
    WHERE f.Loai = 'Tainguyen' 
      AND f.TrangThai = 1
      AND (td.id_dot = ? OR td.id_dot IS NULL)
    ORDER BY f.NgayNop DESC
    ");
    $stmt->execute([$selectedDot]);
    $taiNguyenList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Xử lý đường dẫn file để đảm bảo tính chính xác
    foreach ($taiNguyenList as &$taiNguyen) {
        $originalPath = $taiNguyen['DIR'];
        
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
            __DIR__ . '/../../file/' . $taiNguyen['TenFile'], // Đường dẫn với tên file gốc
        ];
        
        // Loại bỏ path trùng lặp
        $possiblePaths = array_unique($possiblePaths);
        
        // Tìm đường dẫn file tồn tại
        $foundPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $foundPath = $path;
                break;
            }
        }
        
        // Cập nhật đường dẫn
        $taiNguyen['DIR'] = $foundPath ?: $correctedPath;
        
        // Thêm thông tin file status để debugging
        $taiNguyen['file_exists'] = ($foundPath !== null);
        $taiNguyen['file_size'] = $foundPath ? filesize($foundPath) : 0;
    }
    unset($taiNguyen); // Unset reference
}

// Hàm kiểm tra absolute path
function is_absolute_path($path) {
    return (strpos($path, '/') === 0) || (preg_match('/^[A-Za-z]:/', $path));
}

// Hàm lấy icon theo extension
function getFileIcon($fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf':
            return 'fa-file-pdf-o';
        case 'doc':
        case 'docx':
            return 'fa-file-word-o';
        case 'xls':
        case 'xlsx':
            return 'fa-file-excel-o';
        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint-o';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
            return 'fa-file-image-o';
        case 'zip':
        case 'rar':
        case '7z':
            return 'fa-file-archive-o';
        case 'mp4':
        case 'avi':
        case 'mkv':
            return 'fa-file-video-o';
        default:
            return 'fa-file-o';
    }
}

// Hàm định dạng kích thước file
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Nguyên Thực Tập</title>
    <?php require_once __DIR__ . '/../../template/head.php'; ?>
    <style>
        body {
            background: #667eea;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #2c3e50;
            font-size: 16px;
            line-height: 1.6;
            font-weight: 400;
        }
        
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            margin: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-top: 20px;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 5px;
            background: #667eea;
            border-radius: 10px;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            color: #2c3e50;
            margin: 20px 0 15px 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }
        
        .page-header .subtitle {
            font-size: 1.25rem;
            color: #495057;
            margin-bottom: 25px;
            font-weight: 300;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            line-height: 1.5;
        }
        
        .dot-info {
            background: #4facfe;
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 16px rgba(79, 172, 254, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .dot-info h3 {
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
            line-height: 1.3;
        }
        
        .dot-info .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .dot-info .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.4;
        }
        
        .dot-info .info-item i {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .resources-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .resources-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .resources-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.3;
        }
        
        .resources-count {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.2;
        }
        
        .resource-item {
            background: #ffffff;
            border: 1px solid rgba(52, 152, 219, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .resource-item:hover {
            background: #f8f9fa;
            border-color: #3498db;
            box-shadow: 0 4px 16px rgba(52, 152, 219, 0.15);
        }
        
        .resource-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .file-icon {
            font-size: 3rem;
            color: #3498db;
            min-width: 60px;
            text-align: center;
        }
        
        .resource-info {
            flex: 1;
        }
        
        .resource-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
            word-break: break-word;
            line-height: 1.4;
        }
        
        .resource-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .resource-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .resource-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            line-height: 1.4;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .btn-success {
            background: #00b894;
            color: white;
        }
        
        .btn-success:hover {
            background: #00a085;
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.2);
        }
        
        .stats-bar {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .stats-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #495057;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.4;
        }
        
        .stats-item i {
            font-size: 1.2rem;
            color: #667eea;
        }
        
        .stats-number {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.05rem;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
            line-height: 1.6;
        }
        
        .no-results h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .no-results p {
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #adb5bd;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        @media (max-width: 768px) {
            body {
                font-size: 15px;
            }
            
            #page-wrapper {
                padding: 15px;
            }
            
            .page-header h1 {
                font-size: 2.2rem;
                line-height: 1.1;
            }
            
            .page-header .subtitle {
                font-size: 1.1rem;
                line-height: 1.5;
            }
            
            .dot-info h3 {
                font-size: 1.3rem;
            }
            
            .resources-title {
                font-size: 1.3rem;
            }
            
            .resource-title {
                font-size: 1.15rem;
            }
            
            .dot-info .info-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .resources-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .resource-header {
                flex-direction: column;
                text-align: center;
            }
            
            .resource-meta {
                justify-content: center;
            }
            
            .resource-actions {
                justify-content: center;
            }
            
            .stats-bar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            body {
                font-size: 14px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-header .subtitle {
                font-size: 1rem;
            }
            
            .resource-title {
                font-size: 1.1rem;
            }
            
            .resource-meta {
                font-size: 0.85rem;
            }
            
            .stats-item {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once __DIR__ . '/../../template/slidebar_Sinhvien.php'; ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1><i class="fa fa-download"></i> Tài Nguyên Thực Tập</h1>
                    <p class="subtitle">Tài liệu và tài nguyên hỗ trợ cho quá trình thực tập</p>
                </div>

                <!-- Debug info (tạm thời) -->
                <?php if (isset($_GET['debug'])): ?>
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px; font-size: 14px; font-family: monospace;">
                        <strong>Debug Info:</strong><br>
                        ID Tài khoản: <?php echo $debugInfo['ID_TaiKhoan']; ?><br>
                        ID Đợt: <?php echo $debugInfo['ID_Dot']; ?><br>
                        Vai trò: <?php echo $debugInfo['User_Role']; ?><br>
                        Session hợp lệ: <?php echo $debugInfo['Session_Valid'] ? 'Yes' : 'No'; ?><br>
                        Số tài nguyên: <?php echo count($taiNguyenList); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger" style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #721c24;">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $errorMsg; ?>
                    </div>
                <?php else: ?>
                    <!-- Thông tin đợt thực tập -->
                    <div class="dot-info">
                        <h3><i class="fa fa-info-circle"></i> Thông Tin Đợt Thực Tập Hiện Tại</h3>
                        <div class="info-row">
                            <div class="info-item">
                                <i class="fa fa-tag"></i>
                                <span><strong>Tên đợt:</strong> <?php echo htmlspecialchars($dotInfo['TenDot']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fa fa-calendar"></i>
                                <span><strong>Năm:</strong> <?php echo htmlspecialchars($dotInfo['Nam']); ?></span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-item">
                                <i class="fa fa-calendar-o"></i>
                                <span><strong>Thời gian:</strong> <?php echo date('d/m/Y', strtotime($dotInfo['ThoiGianBatDau'])); ?> - <?php echo date('d/m/Y', strtotime($dotInfo['ThoiGianKetThuc'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thanh thống kê -->
                    <div class="stats-bar">
                        <div class="stats-item">
                            <i class="fa fa-folder"></i>
                            <span>Tài nguyên: <span class="stats-number"><?php echo count($taiNguyenList); ?></span></span>
                        </div>
                        <div class="stats-item">
                            <i class="fa fa-calendar"></i>
                            <span>Đợt thực tập: <span class="stats-number"><?php echo htmlspecialchars($dotInfo['TenDot']); ?></span></span>
                        </div>
                        <div class="stats-item">
                            <i class="fa fa-star"></i>
                            <span>Trạng thái: <span class="stats-number">Đang hoạt động</span></span>
                        </div>
                    </div>

                    <!-- Container tài nguyên -->
                    <div class="resources-container">
                        <div class="resources-header">
                            <div class="resources-title">
                                <i class="fa fa-folder-open"></i>
                                Danh Sách Tài Nguyên
                            </div>
                            <div class="resources-count">
                                <?php echo count($taiNguyenList); ?> tài nguyên
                            </div>
                        </div>

                        <?php if (empty($taiNguyenList)): ?>
                            <div class="no-results">
                                <i class="fa fa-folder-open"></i>
                                <h3>Chưa có tài nguyên nào</h3>
                                <p>Hiện tại chưa có tài nguyên nào được chia sẻ cho đợt thực tập này.<br>
                                Vui lòng kiểm tra lại sau hoặc liên hệ với giáo viên hướng dẫn.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($taiNguyenList as $taiNguyen): ?>
                                <div class="resource-item">
                                    <div class="resource-header">
                                        <div class="file-icon">
                                            <i class="fa <?php echo getFileIcon($taiNguyen['TenFile']); ?>"></i>
                                        </div>
                                        <div class="resource-info">
                                            <div class="resource-title">
                                                <?php echo htmlspecialchars($taiNguyen['TenHienThi'] ?: pathinfo($taiNguyen['TenFile'], PATHINFO_FILENAME)); ?>
                                            </div>
                                            <div class="resource-meta">
                                                <div class="meta-item">
                                                    <i class="fa fa-file"></i>
                                                    <span><?php echo htmlspecialchars($taiNguyen['TenFile']); ?></span>
                                                </div>
                                                <?php if ($taiNguyen['file_exists']): ?>
                                                    <div class="meta-item">
                                                        <i class="fa fa-hdd-o"></i>
                                                        <span><?php echo formatFileSize($taiNguyen['file_size']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="meta-item">
                                                        <i class="fa fa-exclamation-triangle" style="color: #e74c3c;"></i>
                                                        <span style="color: #e74c3c;">File không tồn tại</span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="meta-item">
                                                    <i class="fa fa-clock-o"></i>
                                                    <span>Ngày tải lên: <?php echo date('d/m/Y H:i', strtotime($taiNguyen['NgayNop'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="resource-actions">
                                        <!-- Nút xem trước -->
                                        <?php 
                                        $ext = strtolower(pathinfo($taiNguyen['TenFile'], PATHINFO_EXTENSION));
                                        $previewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                                        ?>
                                        
                                        <?php if (!$taiNguyen['file_exists']): ?>
                                            <div class="alert alert-danger" style="margin: 10px 0; padding: 10px; font-size: 0.9rem;">
                                                <i class="fa fa-exclamation-triangle"></i> File không tồn tại hoặc đã bị xóa
                                            </div>
                                        <?php else: ?>
                                            <?php if (in_array($ext, $previewableTypes)): ?>
                                                <a href="./pages/sinhvien/download_tainguyen.php?file=<?php echo urlencode($taiNguyen['ID']); ?>&preview=1" 
                                                   target="_blank" 
                                                   class="btn btn-primary"
                                                   title="Xem trước file">
                                                    <i class="fa fa-eye"></i> Xem trước
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Nút tải xuống -->
                                            <a href="./pages/sinhvien/download_tainguyen.php?file=<?php echo urlencode($taiNguyen['ID']); ?>&name=<?php echo urlencode($taiNguyen['TenHienThi'] ?: $taiNguyen['TenFile']); ?>" 
                                               class="btn btn-success"
                                               title="Tải xuống file">
                                                <i class="fa fa-download"></i> Tải xuống
                                                <?php if ($taiNguyen['file_size'] > 10485760): ?>
                                                    <small>(<?php echo formatFileSize($taiNguyen['file_size']); ?>)</small>
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../access/js/jquery.min.js"></script>
    <script src="../../access/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Tối ưu hóa sidebar navigation
            $('#side-menu li > a').on('click', function(e) {
                var $submenu = $(this).siblings('ul');
                if ($submenu.length > 0) {
                    e.preventDefault();
                    $submenu.slideToggle(200);
                }
            });
            
            // Tối ưu hóa button click
            $('.btn-primary').on('click', function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html('<i class="fa fa-spinner fa-spin"></i> Đang mở...');
                setTimeout(function() {
                    $btn.html(originalText);
                }, 1500);
            });
        });
    </script>
</body>
</html>
