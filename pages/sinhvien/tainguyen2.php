<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Lấy thông tin sinh viên và đợt thực tập
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$stmt = $conn->prepare("SELECT ID_Dot FROM SinhVien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDotHienTai = $stmt->fetchColumn();

// Lấy tham số tìm kiếm
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$dotSearch = isset($_GET['dot_search']) ? trim($_GET['dot_search']) : '';

// Lấy tất cả các đợt thực tập với tìm kiếm
$dotCondition = '';
$dotParams = [];
if (!empty($dotSearch)) {
    $dotCondition = "AND (TenDot LIKE ? OR Nam LIKE ?)";
    $dotParams[] = "%$dotSearch%";
    $dotParams[] = "%$dotSearch%";
}

$stmt = $conn->prepare("SELECT ID, TenDot, Nam, ThoiGianBatDau, ThoiGianKetThuc FROM dotthuctap WHERE TrangThai = 1 $dotCondition ORDER BY ID DESC");
$stmt->execute($dotParams);
$allDots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy đợt được chọn từ URL hoặc mặc định là đợt hiện tại
$selectedDot = isset($_GET['dot']) ? (int)$_GET['dot'] : $idDotHienTai;

if (!$selectedDot) {
    $errorMsg = "Bạn chưa được phân vào đợt thực tập nào!";
    $taiNguyenList = [];
    $dotInfo = null;
} else {
    // Lấy thông tin đợt được chọn
    $stmt = $conn->prepare("SELECT TenDot, Nam, ThoiGianBatDau, ThoiGianKetThuc FROM DotThucTap WHERE ID = ?");
    $stmt->execute([$selectedDot]);
    $dotInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy danh sách tài nguyên cho đợt được chọn với tìm kiếm
    $searchCondition = '';
    $searchParams = [$selectedDot];
    
    if (!empty($searchKeyword)) {
        $searchCondition = "AND (f.TenFile LIKE ? OR f.TenHienThi LIKE ?)";
        $searchParams[] = "%$searchKeyword%";
        $searchParams[] = "%$searchKeyword%";
    }
    
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
        $searchCondition
        ORDER BY f.NgayNop DESC
    ");
    $stmt->execute($searchParams);
    $taiNguyenList = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #2c3e50;
            font-size: 16px;
            line-height: 1.6;
            font-weight: 400;
            letter-spacing: 0.01em;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="1" fill="rgba(255,255,255,0.03)"/><circle cx="10" cy="50" r="1" fill="rgba(255,255,255,0.03)"/><circle cx="90" cy="30" r="1" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
            z-index: -1;
        }
        
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            margin: 20px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            color: #ffffff;
            margin: 20px 0 15px 0;
            text-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
            line-height: 1.1;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
        
        .page-header .subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 25px;
            font-weight: 300;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            line-height: 1.5;
            letter-spacing: 0.2px;
        }
        
        .dot-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(79, 172, 254, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .dot-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23dots)"/></svg>') repeat;
            pointer-events: none;
        }
        
        .dot-info h3 {
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
            line-height: 1.3;
            letter-spacing: 0.3px;
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
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 16px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
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
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
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
            letter-spacing: 0.2px;
        }
        
        .resources-count {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.1px;
            line-height: 1.2;
        }
        
        .resource-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid rgba(52, 152, 219, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .resource-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .resource-item:hover::before {
            left: 100%;
        }
        
        .resource-item:hover {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-color: #3498db;
            box-shadow: 0 10px 40px rgba(52, 152, 219, 0.2);
            transform: translateY(-3px);
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
            filter: drop-shadow(0 2px 4px rgba(52, 152, 219, 0.3));
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
            letter-spacing: 0.1px;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            line-height: 1.4;
            letter-spacing: 0.2px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #00a085 0%, #00b894 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 184, 148, 0.3);
        }
        
        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid transparent;
            background: linear-gradient(white, white) padding-box, linear-gradient(90deg, #667eea, #764ba2) border-box;
            border-image: linear-gradient(90deg, #667eea, #764ba2) 1;
        }
        
        .filter-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            line-height: 1.3;
            letter-spacing: 0.2px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(52, 152, 219, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .filter-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .filter-card:hover::before {
            transform: scaleX(1);
        }
        
        .filter-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
            border-color: #3498db;
        }
        
        .filter-card h4 {
            margin: 0 0 18px 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.3;
            letter-spacing: 0.1px;
        }
        
        .search-input-group {
            position: relative;
        }
        
        .search-input-group input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f8f9fa;
            font-weight: 500;
            line-height: 1.4;
            letter-spacing: 0.1px;
        }
        
        .search-input-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .search-input-group .search-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        
        .search-input-group input:focus + .search-icon {
            color: #667eea;
        }
        
        .dot-select-wrapper {
            position: relative;
        }
        
        .dot-select-wrapper select {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            appearance: none;
            font-weight: 500;
            line-height: 1.4;
            letter-spacing: 0.1px;
        }
        
        .dot-select-wrapper select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .dot-select-wrapper::after {
            content: '\f107';
            font-family: FontAwesome;
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        
        .dot-select-wrapper:focus-within::after {
            color: #667eea;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-modern {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            line-height: 1.4;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-search:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }
        
        .btn-reset:hover {
            background: linear-gradient(135deg, #0984e3 0%, #74b9ff 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(116, 185, 255, 0.4);
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #ff7675 0%, #d63031 100%);
            color: white;
        }
        
        .btn-clear:hover {
            background: linear-gradient(135deg, #d63031 0%, #ff7675 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 118, 117, 0.4);
        }
        
        .stats-bar {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.9) 100%);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
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
            letter-spacing: 0.1px;
        }
        
        .active-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .filter-tag {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            line-height: 1.3;
            letter-spacing: 0.1px;
        }
        
        .filter-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .filter-tag .remove-filter {
            cursor: pointer;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .filter-tag .remove-filter:hover {
            color: #ff7675;
        }
        
        .current-dot-badge {
            background: #e74c3c;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
            letter-spacing: 0.1px;
            line-height: 1.2;
        }
        
        .search-results-info {
            background: rgba(248, 249, 250, 0.9);
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 0.9rem;
            color: #495057;
            line-height: 1.5;
            font-weight: 500;
            letter-spacing: 0.1px;
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
            letter-spacing: 0.1px;
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
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
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
            
            .filter-title {
                font-size: 1.25rem;
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
            
            .download-progress {
                top: 10px;
                right: 10px;
                left: 10px;
                text-align: center;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .filter-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-modern {
                width: 100%;
                justify-content: center;
            }
            
            .stats-bar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .active-filters {
                justify-content: center;
            }
            
            .filter-card {
                padding: 15px;
            }
            
            .current-dot-badge {
                display: block;
                margin-left: 0;
                margin-top: 5px;
                width: fit-content;
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
            
            .filter-section {
                padding: 15px;
            }
            
            .filter-title {
                font-size: 1.1rem;
            }
            
            .filter-card h4 {
                font-size: 1rem;
            }
            
            .search-input-group input,
            .dot-select-wrapper select {
                padding: 12px 40px 12px 15px;
                font-size: 0.9rem;
            }
            
            .btn-modern {
                padding: 12px 24px;
                font-size: 0.9rem;
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
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1><i class="fa fa-download"></i> Tài Nguyên Thực Tập</h1>
                    <p class="subtitle">Tài liệu và tài nguyên hỗ trợ cho quá trình thực tập</p>
                </div>

                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $errorMsg; ?>
                    </div>
                <?php else: ?>
                    <!-- Thông tin đợt thực tập -->
                    <div class="dot-info">
                        <h3><i class="fa fa-info-circle"></i> Thông Tin Đợt Thực Tập</h3>
                        <div class="info-row">
                            <div class="info-item">
                                <i class="fa fa-tag"></i>
                                <span><strong>Tên đợt:</strong> <?php echo htmlspecialchars($dotInfo['TenDot']); ?>
                                    <?php if ($selectedDot == $idDotHienTai): ?>
                                        <span class="current-dot-badge">Đợt hiện tại</span>
                                    <?php endif; ?>
                                </span>
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

                    <!-- Bộ lọc tìm kiếm nâng cao -->
                    <div class="filter-section">
                        <div class="filter-header">
                            <h3 class="filter-title">
                                <i class="fa fa-filter"></i>
                                Bộ Lọc & Tìm Kiếm
                            </h3>
                        </div>
                        
                        <form method="GET" action="" id="filter-form">
                            <!-- Hiển thị bộ lọc đang áp dụng -->
                            <?php if (!empty($searchKeyword) || !empty($dotSearch) || $selectedDot != $idDotHienTai): ?>
                                <div class="active-filters">
                                    <?php if (!empty($searchKeyword)): ?>
                                        <div class="filter-tag">
                                            <i class="fa fa-search"></i> 
                                            Tìm kiếm: "<?php echo htmlspecialchars($searchKeyword); ?>"
                                            <span class="remove-filter" onclick="removeFilter('search')">&times;</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($dotSearch)): ?>
                                        <div class="filter-tag">
                                            <i class="fa fa-calendar"></i> 
                                            Lọc đợt: "<?php echo htmlspecialchars($dotSearch); ?>"
                                            <span class="remove-filter" onclick="removeFilter('dot_search')">&times;</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($selectedDot != $idDotHienTai): ?>
                                        <div class="filter-tag">
                                            <i class="fa fa-bookmark"></i> 
                                            Đợt được chọn: <?php echo htmlspecialchars($dotInfo['TenDot']); ?>
                                            <span class="remove-filter" onclick="removeFilter('dot')">&times;</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="filter-grid">
                                <!-- Card chọn đợt -->
                                <div class="filter-card">
                                    <h4><i class="fa fa-calendar-check-o"></i> Chọn Đợt Thực Tập</h4>
                                    <div class="search-input-group">
                                        <input type="text" name="dot_search" id="dot-search-input" 
                                               placeholder="Tìm kiếm đợt theo tên hoặc năm..." 
                                               value="<?php echo htmlspecialchars($dotSearch); ?>">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <div class="dot-select-wrapper">
                                            <select name="dot" id="dot-select">
                                                <?php if (empty($allDots)): ?>
                                                    <option value="">Không tìm thấy đợt nào</option>
                                                <?php else: ?>
                                                    <?php foreach ($allDots as $dot): ?>
                                                        <option value="<?php echo $dot['ID']; ?>" <?php echo ($dot['ID'] == $selectedDot) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($dot['TenDot']); ?> (<?php echo $dot['Nam']; ?>)
                                                            <?php if ($dot['ID'] == $idDotHienTai): ?>
                                                                ★
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Card tìm kiếm tài nguyên -->
                                <div class="filter-card">
                                    <h4><i class="fa fa-file-text-o"></i> Tìm Kiếm Tài Nguyên</h4>
                                    <div class="search-input-group">
                                        <input type="text" name="search" id="search-input" 
                                               placeholder="Nhập tên file, tên hiển thị hoặc từ khóa..." 
                                               value="<?php echo htmlspecialchars($searchKeyword); ?>">
                                        <i class="fa fa-search search-icon"></i>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 0.85rem; color: #6c757d;">
                                        <i class="fa fa-lightbulb-o"></i> 
                                        Gợi ý: Tìm theo tên file, phần mở rộng (.pdf, .docx) hoặc nội dung
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit" class="btn-modern btn-search">
                                    <i class="fa fa-search"></i> Tìm Kiếm
                                </button>
                                <button type="button" class="btn-modern btn-reset" onclick="resetAllFilters()">
                                    <i class="fa fa-refresh"></i> Đặt Lại
                                </button>
                                <button type="button" class="btn-modern btn-clear" onclick="clearAllFilters()">
                                    <i class="fa fa-times"></i> Xóa Tất Cả
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Thanh thống kê -->
                    <div class="stats-bar">
                        <div class="stats-item">
                            <i class="fa fa-database"></i>
                            <span>Tổng đợt: <span class="stats-number"><?php echo count($allDots); ?></span></span>
                        </div>
                        <div class="stats-item">
                            <i class="fa fa-folder"></i>
                            <span>Tài nguyên: <span class="stats-number"><?php echo count($taiNguyenList); ?></span></span>
                        </div>
                        <div class="stats-item">
                            <i class="fa fa-star"></i>
                            <span>Đợt hiện tại: <span class="stats-number"><?php echo $idDotHienTai ? 'Có' : 'Không'; ?></span></span>
                        </div>
                    </div>

                    <!-- Thông tin kết quả tìm kiếm -->
                    <?php if (!empty($searchKeyword)): ?>
                        <div class="search-results-info">
                            <i class="fa fa-search"></i> 
                            Kết quả tìm kiếm cho "<strong><?php echo htmlspecialchars($searchKeyword); ?></strong>" 
                            trong đợt "<strong><?php echo htmlspecialchars($dotInfo['TenDot']); ?></strong>": 
                            <strong><?php echo count($taiNguyenList); ?></strong> tài nguyên
                        </div>
                    <?php endif; ?>

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
                            <?php if (!empty($searchKeyword)): ?>
                                <div class="no-results">
                                    <i class="fa fa-search"></i>
                                    <h3>Không tìm thấy kết quả</h3>
                                    <p>Không có tài nguyên nào phù hợp với từ khóa "<strong><?php echo htmlspecialchars($searchKeyword); ?></strong>"<br>
                                    trong đợt "<strong><?php echo htmlspecialchars($dotInfo['TenDot']); ?></strong>".</p>
                                    <p>Hãy thử tìm kiếm với từ khóa khác hoặc chọn đợt thực tập khác.</p>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fa fa-folder-open"></i>
                                    <h3>Chưa có tài nguyên nào</h3>
                                    <p>Hiện tại chưa có tài nguyên nào được chia sẻ cho đợt thực tập này.<br>
                                    Vui lòng kiểm tra lại sau hoặc liên hệ với giáo viên hướng dẫn.</p>
                                </div>
                            <?php endif; ?>
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
                                                <?php if (file_exists($taiNguyen['DIR'])): ?>
                                                    <div class="meta-item">
                                                        <i class="fa fa-hdd-o"></i>
                                                        <span><?php echo formatFileSize(filesize($taiNguyen['DIR'])); ?></span>
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
                                        $fileExists = file_exists($taiNguyen['DIR']);
                                        $fileSize = $fileExists ? filesize($taiNguyen['DIR']) : 0;
                                        $isLargeFile = $fileSize > 10485760; // 10MB
                                        ?>
                                        
                                        <?php if (!$fileExists): ?>
                                            <div class="alert alert-danger" style="margin: 10px 0; padding: 10px; font-size: 0.9rem;">
                                                <i class="fa fa-exclamation-triangle"></i> File không tồn tại hoặc đã bị xóa
                                            </div>
                                        <?php else: ?>
                                            <?php if (in_array($ext, $previewableTypes)): ?>
                                                <a href="/datn/pages/sinhvien/download_tainguyen.php?file=<?php echo urlencode($taiNguyen['ID']); ?>&preview=1" 
                                                   target="_blank" 
                                                   class="btn btn-primary"
                                                   title="Xem trước file">
                                                    <i class="fa fa-eye"></i> Xem trước
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Nút tải xuống -->
                                            <a href="/datn/pages/sinhvien/download_tainguyen.php?file=<?php echo urlencode($taiNguyen['ID']); ?>&name=<?php echo urlencode($taiNguyen['TenHienThi'] ?: $taiNguyen['TenFile']); ?>" 
                                               class="btn btn-success"
                                               title="Tải xuống file"
                                               download="<?php echo htmlspecialchars($taiNguyen['TenHienThi'] ?: $taiNguyen['TenFile']); ?>">
                                                <i class="fa fa-download"></i> Tải xuống
                                                <?php if ($isLargeFile): ?>
                                                    <small>(<?php echo formatFileSize($fileSize); ?>)</small>
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
    <script src="access/js/jquery.min.js"></script>
    <script src="access/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Thay thế startmin.js - xử lý sidebar navigation
            if (typeof $.fn.metisMenu !== 'undefined') {
                $('#side-menu').metisMenu({
                    toggle: true
                });
            } else {
                // Fallback cho sidebar navigation nếu không có metisMenu
                $('#side-menu li > a').click(function(e) {
                    var $this = $(this);
                    var $parent = $this.parent();
                    var $submenu = $parent.find('ul');
                    
                    if ($submenu.length > 0) {
                        e.preventDefault();
                        $submenu.slideToggle();
                    }
                });
            }
            
            // Xử lý click nút xem trước
            $('.btn-primary').on('click', function() {
                var $btn = $(this);
                var originalText = $btn.html();
                
                $btn.html('<i class="fa fa-spinner fa-spin"></i> Đang mở...');
                
                setTimeout(function() {
                    $btn.html(originalText);
                }, 2000);
            });
            
            // Thêm tooltip cho các nút
            $('[title]').tooltip();
            
            // Thêm hiệu ứng hover cho resource items
            $('.resource-item').hover(
                function() {
                    $(this).addClass('hovered');
                },
                function() {
                    $(this).removeClass('hovered');
                }
            );
            
            // Auto-submit khi thay đổi đợt thực tập
            $('#dot-select').change(function() {
                $('#filter-form').submit();
            });
            
            // Enter key để submit form tìm kiếm
            $('#search-input, #dot-search-input').keypress(function(e) {
                if (e.which === 13) {
                    $('#filter-form').submit();
                }
            });
            
            // Real-time search cho đợt thực tập
            $('#dot-search-input').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var $select = $('#dot-select');
                var $options = $select.find('option');
                
                $options.each(function() {
                    var $option = $(this);
                    var text = $option.text().toLowerCase();
                    
                    if (text.includes(searchTerm) || searchTerm === '') {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                });
            });
            
            // Highlight search terms
            highlightSearchTerms();
        });
        
        // Hàm reset tất cả filter
        function resetAllFilters() {
            var currentUrl = window.location.href.split('?')[0];
            window.location.href = currentUrl;
        }
        
        // Hàm xóa tất cả filter
        function clearAllFilters() {
            $('#search-input').val('');
            $('#dot-search-input').val('');
            $('#dot-select').val('<?php echo $idDotHienTai; ?>');
            $('#filter-form').submit();
        }
        
        // Hàm xóa filter cụ thể
        function removeFilter(filterType) {
            var url = new URL(window.location.href);
            var params = new URLSearchParams(url.search);
            
            switch(filterType) {
                case 'search':
                    params.delete('search');
                    break;
                case 'dot_search':
                    params.delete('dot_search');
                    break;
                case 'dot':
                    params.delete('dot');
                    break;
            }
            
            url.search = params.toString();
            window.location.href = url.toString();
        }
        
        // Hàm highlight search terms
        function highlightSearchTerms() {
            var searchTerm = '<?php echo addslashes($searchKeyword); ?>';
            if (searchTerm) {
                $('.resource-title, .resource-meta').each(function() {
                    var text = $(this).html();
                    var highlightedText = text.replace(
                        new RegExp(searchTerm, 'gi'),
                        '<mark style="background: #fff3cd; padding: 1px 3px; border-radius: 2px;">$&</mark>'
                    );
                    $(this).html(highlightedText);
                });
            }
        }
        
        // Animation cho filter cards
        $('.filter-card').hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );
    </script>
</body>
</html>
