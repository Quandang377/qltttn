<?php
// Bắt đầu session an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../middleware/check_role.php';
require_once __DIR__ . '/../../template/config.php';

// Tạm thời gán id tài khoản sinh viên là 3
$id_taikhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;

$baocao = null;
$baocao_dir = null;
$baocao_trangthai = null;
$ten_sv = '';
$cho_phep_nop = false;
$errorMsg = ''; // Thêm biến này ở đầu file

// Lấy tên sinh viên và id tài khoản giáo viên hướng dẫn
$stmt = $conn->prepare("SELECT Ten, ID_GVHD FROM sinhvien WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_taikhoan]);
$row_sv = $stmt->fetch(PDO::FETCH_ASSOC);
$ten_sv = $row_sv['Ten'] ?? 'Không xác định';
$id_gvhd = $row_sv['ID_GVHD'] ?? null;
    
// Kiểm tra trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
if ($id_gvhd) {
    $stmt = $conn->prepare("SELECT TrangThai FROM baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);
}

// Xử lý xóa file (cập nhật trạng thái về false và xóa file vật lý)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_baocao'])) {
    // Kiểm tra lại trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
    $stmt = $conn->prepare("SELECT TrangThai FROM baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);

    if (!$cho_phep_nop) {
        $errorMsg = "Giáo viên đã đóng chức năng, bạn không thể xóa báo cáo!";
    } else {
        $stmt = $conn->prepare("SELECT Dir FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'Baocao' ORDER BY ID DESC LIMIT 1");
        $stmt->execute([$id_taikhoan]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $filePath = $row['Dir'] ?? null;

        $stmt = $conn->prepare("UPDATE file SET TrangThai = 0 WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'Baocao'");
        $success = $stmt->execute([$id_taikhoan]);

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        if ($success) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo "<script>alert('Xóa báo cáo thất bại!');</script>";
        }
    }
}

// Xử lý xóa file nhận xét
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_nhanxet'])) {
    // Kiểm tra lại trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
    $stmt = $conn->prepare("SELECT TrangThai FROM baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);

    if (!$cho_phep_nop) {
        $errorMsg = "Giáo viên đã đóng chức năng, bạn không thể xóa file nhận xét!";
    } else {
        $stmt = $conn->prepare("SELECT Dir FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'nhanxet' ORDER BY ID DESC LIMIT 1");
        $stmt->execute([$id_taikhoan]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $filePath = $row['Dir'] ?? null;

        $stmt = $conn->prepare("UPDATE file SET TrangThai = 0 WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'nhanxet'");
        $success = $stmt->execute([$id_taikhoan]);

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        if ($success) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo "<script>alert('Xóa file nhận xét thất bại!');</script>";
        }
    }
}

// Xử lý xóa file phiếu thực tập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_phieuthuctap'])) {
    // Kiểm tra lại trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
    $stmt = $conn->prepare("SELECT TrangThai FROM baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);

    if (!$cho_phep_nop) {
        $errorMsg = "Giáo viên đã đóng chức năng, bạn không thể xóa file phiếu thực tập!";
    } else {
        $stmt = $conn->prepare("SELECT Dir FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'phieuthuctap' ORDER BY ID DESC LIMIT 1");
        $stmt->execute([$id_taikhoan]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $filePath = $row['Dir'] ?? null;

        $stmt = $conn->prepare("UPDATE file SET TrangThai = 0 WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'phieuthuctap'");
        $success = $stmt->execute([$id_taikhoan]);

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        if ($success) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo "<script>alert('Xóa file phiếu thực tập thất bại!');</script>";
        }
    }
}

// Xử lý xóa file khảo sát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_khoasat'])) {
    // Kiểm tra lại trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
    $stmt = $conn->prepare("SELECT TrangThai FROM baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    $trangthai_baocaotongket = $stmt->fetchColumn();
    $cho_phep_nop = ($trangthai_baocaotongket == 1);

    if (!$cho_phep_nop) {
        $errorMsg = "Giáo viên đã đóng chức năng, bạn không thể xóa file khảo sát!";
    } else {
        $stmt = $conn->prepare("SELECT Dir FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'khoasat' ORDER BY ID DESC LIMIT 1");
        $stmt->execute([$id_taikhoan]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $filePath = $row['Dir'] ?? null;

        $stmt = $conn->prepare("UPDATE file SET TrangThai = 0 WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'khoasat'");
        $success = $stmt->execute([$id_taikhoan]);

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        if ($success) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            echo "<script>alert('Xóa file khảo sát thất bại!');</script>";
        }
    }
}

// Lấy báo cáo mới nhất có trạng thái true (1) và loại 'Baocao'
$stmt = $conn->prepare("SELECT TenFile, Dir, TrangThai FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = 'Baocao' ORDER BY ID DESC LIMIT 1");
$stmt->execute([$id_taikhoan]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$baocao = $row['TenFile'] ?? null;
$baocao_dir = $row['Dir'] ?? null;
$baocao_trangthai = $row['TrangThai'] ?? null;

// Xử lý upload file nếu có file upload (đặt trong một điều kiện để tránh exit sớm)


// Lấy các file đã nộp cho từng loại
$nhanxet = $phieuthuctap = $khoasat = null;
$nhanxet_dir = $phieuthuctap_dir = $khoasat_dir = null;

foreach (['nhanxet', 'phieuthuctap', 'khoasat'] as $loai) {
    $stmt = $conn->prepare("SELECT TenFile, Dir, TrangThai FROM file WHERE ID_SV = ? AND TrangThai = 1 AND Loai = ? ORDER BY ID DESC LIMIT 1");
    $stmt->execute([$id_taikhoan, $loai]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($loai == 'nhanxet') {
        $nhanxet = $row['TenFile'] ?? null;
        $nhanxet_dir = $row['Dir'] ?? null;
    } elseif ($loai == 'phieuthuctap') {
        $phieuthuctap = $row['TenFile'] ?? null;
        $phieuthuctap_dir = $row['Dir'] ?? null;
    } elseif ($loai == 'khoasat') {
        $khoasat = $row['TenFile'] ?? null;
        $khoasat_dir = $row['Dir'] ?? null;
    }
}

// Lấy thông tin điểm từ giáo viên (nếu đã chấm)
$diem_data = null;
$stmt = $conn->prepare("SELECT Diem_BaoCao, Diem_ChuyenCan, Diem_ChuanNghe, Diem_ThucTe, GhiChu FROM diem_tongket WHERE ID_SV = ?");
$stmt->execute([$id_taikhoan]);
$diem_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Tính điểm tổng kết bằng cách cộng tất cả các điểm lại
$diem_tong = null;
if ($diem_data && 
    !is_null($diem_data['Diem_BaoCao']) && 
    !is_null($diem_data['Diem_ChuyenCan']) && 
    !is_null($diem_data['Diem_ChuanNghe']) && 
    !is_null($diem_data['Diem_ThucTe'])) {
    $diem_tong = $diem_data['Diem_BaoCao'] + 
                 $diem_data['Diem_ChuyenCan'] + 
                 $diem_data['Diem_ChuanNghe'] + 
                 $diem_data['Diem_ThucTe'];
    $diem_tong = round($diem_tong, 2);
}

// Xử lý upload file từ từng panel (modal mới)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file_panel'])) {
    $type = $_POST['upload_type'] ?? '';
    
    // Kiểm tra trạng thái cho phép nộp cho tất cả các loại file
    if (!$cho_phep_nop) {
        $loai_display = [
            'baocao' => 'báo cáo',
            'nhanxet' => 'nhận xét',
            'phieuthuctap' => 'phiếu thực tập',
            'khoasat' => 'khảo sát'
        ];
        echo "<script>alert('Giáo viên đã đóng chức năng, bạn không thể nộp " . ($loai_display[$type] ?? 'file') . "!');</script>";
    } else {
        $file = $_FILES['upload_file'] ?? null;
        $allow = [
            'baocao' => ['doc','docx'],
            'nhanxet' => ['jpg','jpeg','png'],
            'phieuthuctap' => ['jpg','jpeg','png'],
            'khoasat' => ['jpg','jpeg','png']
        ];
        $loai_db = [
            'baocao' => 'Baocao',
            'nhanxet' => 'nhanxet',
            'phieuthuctap' => 'phieuthuctap',
            'khoasat' => 'khoasat'
        ];
        if ($type && $file && $file['error'] === UPLOAD_ERR_OK) {
            $tenFile = $file['name'];
            $ext = strtolower(pathinfo($tenFile, PATHINFO_EXTENSION));
            if (in_array($ext, $allow[$type])) {
                $targetDir = __DIR__ . "/../../file/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $targetFile = $targetDir . basename($tenFile);
                if (file_exists($targetFile)) {
                    $fileNameNoExt = pathinfo($tenFile, PATHINFO_FILENAME);
                    $targetFile = $targetDir . $fileNameNoExt . '_' . time() . '.' . $ext;
                    $tenFile = basename($targetFile);
                }
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Lưu đường dẫn tương đối vào database
                    $dirForDB = 'file/' . basename($targetFile);
                    $stmt = $conn->prepare("INSERT INTO file (TenFile, Dir, ID_SV, TrangThai, Loai, NgayNop) VALUES (?, ?, ?, 1, ?, ?)");
                    $stmt->execute([$tenFile, $dirForDB, $id_taikhoan, $loai_db[$type], date('Y-m-d H:i:s')]);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    echo "<script>alert('Không thể lưu file lên máy chủ!');</script>";
                }
            } else {
                echo "<script>alert('File không đúng định dạng!');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nộp kết quả</title>
    <?php
    require_once __DIR__ . "/../../template/head.php";
    ?>
    <style>
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .page-header {
            font-size: 2.2rem;
            font-weight: 700;
            color: rgb(0, 58, 217);
            letter-spacing: 1px;
            margin-bottom: 32px;
            text-align: center;
            text-shadow: 0 2px 8px #b6d4fe44;
        }
        .upload-panel {
            border: 2px solid #e3eafc;
            border-radius: 16px;
            background: #fff;
            margin-bottom: 24px;
            padding: 20px 16px;
            box-shadow: 0 4px 18px rgba(0,123,255,0.08);
            min-height: 160px;
            max-height: 180px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .upload-panel:hover {
            border-color: #007bff;
            box-shadow: 0 6px 24px rgba(0,123,255,0.16);
            transform: translateY(-3px);
        }
        .upload-panel[style*="opacity:0.6"] {
            background-color: #f8fafc !important;
            opacity: 0.6 !important;
            cursor: not-allowed !important;
        }
        .upload-panel[style*="opacity:0.6"]:hover {
            transform: none !important;
            box-shadow: 0 4px 18px rgba(0,123,255,0.08) !important;
            border-color: #e3eafc !important;
        }
        .upload-panel .panel-icon {
            font-size: 28px;
            margin-bottom: 12px;
            text-align: center;
        }
        .upload-panel .panel-content {
            flex: 1;
            text-align: center;
        }
        .upload-panel .panel-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #007bff;
            line-height: 1.2;
        }
        .upload-panel .panel-status {
            font-size: 13px;
            color: #666;
            text-align: center;
        }
        .upload-panel .file-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .upload-panel .file-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            word-break: break-all;
            max-width: 100px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .upload-panel .file-link:hover {
            color: #0056b3;
            text-decoration: none;
        }
        .btn-action {
            border: none;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-download {
            background: #28a745;
            color: white;
        }
        .btn-download:hover {
            background: #218838;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .alert-success {
            border-radius: 12px;
            border: 2px solid #d4edda;
            background: linear-gradient(90deg, #d4edda 0%, #f8f9fa 100%);
            color: #155724;
            padding: 16px 20px;
            margin-top: 20px;
        }
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }
        .modal-header {
            background: linear-gradient(90deg, #007bff 70%, #5bc0f7 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            border: none;
            padding: 18px 24px;
        }
        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }
        .modal-body {
            padding: 24px;
            background: #fafdff;
        }
        .modal-footer {
            border: none;
            background: #fafdff;
            border-radius: 0 0 16px 16px;
            padding: 16px 24px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e3eafc;
            padding: 10px 16px;
            font-size: 14px;
            background: #fff;
            transition: border-color 0.2s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .btn-success {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-success:hover {
            background: linear-gradient(90deg, #218838 0%, #1a9b7a 100%);
            transform: translateY(-1px);
        }
        .btn-default {
            background: #f8f9fa;
            border: 2px solid #e3eafc;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.2s ease;
        }
        .btn-default:hover {
            background: #e9ecef;
            border-color: #007bff;
            color: #007bff;
        }
        /* Panel màu sắc cụ thể */
        .panel-baocao {
            background: linear-gradient(135deg, #d4edda 0%, #f8f9fa 100%);
            border-color: #28a745;
        }
        .panel-baocao .panel-icon {
            color: #28a745;
        }
        .panel-nhanxet, .panel-phieuthuctap, .panel-khoasat {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            border-color: #007bff;
        }
        .panel-nhanxet .panel-icon, 
        .panel-phieuthuctap .panel-icon, 
        .panel-khoasat .panel-icon {
            color: #007bff;
        }
        
        /* Responsive cho các panel */
        @media (max-width: 1200px) {
            .upload-panel {
                min-height: 150px;
                max-height: 170px;
                padding: 16px 12px;
            }
            .upload-panel .panel-title {
                font-size: 15px;
            }
        }
        
        @media (max-width: 992px) {
            .upload-panel {
                min-height: 140px;
                max-height: 160px;
                padding: 14px 10px;
            }
            .upload-panel .panel-title {
                font-size: 14px;
            }
            .upload-panel .panel-icon {
                font-size: 24px;
                margin-bottom: 8px;
            }
        }
        
        @media (max-width: 768px) {
            .upload-panel {
                margin-bottom: 16px;
                min-height: 120px;
                max-height: 140px;
            }
            .upload-panel .panel-title {
                font-size: 13px;
                margin-bottom: 6px;
            }
            .upload-panel .panel-status {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
    <?php require_once __DIR__ . "/../../template/slidebar_Sinhvien.php"; ?>
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-header">Nộp kết quả</h1>
                    
                    <?php if (!$cho_phep_nop): ?>
                    <div class="alert alert-warning" style="border-radius: 12px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center;">
                            <i class="fa fa-lock" style="font-size: 24px; margin-right: 15px; color: #856404;"></i>
                            <div>
                                <strong style="color: #856404;">Thông báo:</strong>
                                <span style="color: #856404;">Giáo viên hướng dẫn đã đóng chức năng nộp báo cáo tổng kết. Bạn không thể nộp hoặc chỉnh sửa báo cáo trong thời gian này.</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger" style="border-radius: 12px; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border: 2px solid #dc3545; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center;">
                            <i class="fa fa-exclamation-triangle" style="font-size: 24px; margin-right: 15px; color: #721c24;"></i>
                            <div>
                                <strong style="color: #721c24;">Lỗi:</strong>
                                <span style="color: #721c24;"><?php echo htmlspecialchars($errorMsg); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row">
                <!-- Panel Báo cáo tổng kết -->
                <div class="col-md-3">
                    <div class="upload-panel panel-baocao" data-type="baocao" style="<?php if(!$cho_phep_nop) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div class="panel-content">
                            <i class="fa fa-file-text panel-icon"></i>
                            <div class="panel-title">Báo cáo tổng kết</div>
                            <div class="panel-status">
                                <?php if (!$cho_phep_nop): ?>
                                    <div class="alert alert-warning" style="margin: 8px 0; padding: 6px 8px; font-size: 11px; border-radius: 4px;">
                                        <i class="fa fa-lock"></i> Đã đóng
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($baocao): ?>
                                    <div class="file-actions">
                                        <a href="download_file.php?file=<?php echo urlencode($baocao); ?>&type=baocao" target="_blank" class="file-link" title="<?php echo htmlspecialchars($baocao); ?>">
                                            <?php
                                                $maxLen = 12;
                                                $tenHienThi = (mb_strlen($baocao) > $maxLen)
                                                    ? mb_substr($baocao, 0, $maxLen) . '...'
                                                    : $baocao;
                                                echo htmlspecialchars($tenHienThi);
                                            ?>
                                        </a>
                                        <a href="download_file.php?file=<?php echo urlencode($baocao); ?>&type=baocao" class="btn-action btn-download" title="Tải xuống">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <?php if ($cho_phep_nop): ?>
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_baocao" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc muốn xóa báo cáo này?');" title="Xóa">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-success">✓ Đã nộp</small>
                                <?php else: ?>
                                    <?php if ($cho_phep_nop): ?>
                                        <span class="text-muted">Chưa nộp<br>(bấm để upload)</span>
                                    <?php else: ?>
                                        <span class="text-danger">Không thể nộp</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Nhận xét công ty -->
                <div class="col-md-3">
                    <div class="upload-panel panel-nhanxet" data-type="nhanxet" style="<?php if(!$cho_phep_nop) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div class="panel-content">
                            <i class="fa fa-comments panel-icon"></i>
                            <div class="panel-title">Nhận xét công ty</div>
                            <div class="panel-status">
                                <?php if (!$cho_phep_nop): ?>
                                    <div class="alert alert-warning" style="margin: 8px 0; padding: 6px 8px; font-size: 11px; border-radius: 4px;">
                                        <i class="fa fa-lock"></i> Đã đóng
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($nhanxet): ?>
                                    <div class="file-actions">
                                        <a href="download_file.php?file=<?php echo urlencode($nhanxet); ?>&type=nhanxet" target="_blank" class="file-link" title="<?php echo htmlspecialchars($nhanxet); ?>">
                                            <?php
                                                $maxLen = 12;
                                                $tenHienThi = (mb_strlen($nhanxet) > $maxLen)
                                                    ? mb_substr($nhanxet, 0, $maxLen) . '...'
                                                    : $nhanxet;
                                                echo htmlspecialchars($tenHienThi);
                                            ?>
                                        </a>
                                        <a href="download_file.php?file=<?php echo urlencode($nhanxet); ?>&type=nhanxet" class="btn-action btn-download" title="Tải xuống">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <?php if ($cho_phep_nop): ?>
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_nhanxet" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc muốn xóa file nhận xét này?');" title="Xóa">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-success">✓ Đã nộp</small>
                                <?php else: ?>
                                    <?php if ($cho_phep_nop): ?>
                                        <span class="text-muted">Chưa nộp<br>(bấm để upload)</span>
                                    <?php else: ?>
                                        <span class="text-danger">Không thể nộp</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Panel Phiếu thực tập -->
                <div class="col-md-3">
                    <div class="upload-panel panel-phieuthuctap" data-type="phieuthuctap" style="<?php if(!$cho_phep_nop) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div class="panel-content">
                            <i class="fa fa-clipboard panel-icon"></i>
                            <div class="panel-title">Phiếu thực tập</div>
                            <div class="panel-status">
                                <?php if (!$cho_phep_nop): ?>
                                    <div class="alert alert-warning" style="margin: 8px 0; padding: 6px 8px; font-size: 11px; border-radius: 4px;">
                                        <i class="fa fa-lock"></i> Đã đóng
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($phieuthuctap): ?>
                                    <div class="file-actions">
                                        <a href="download_file.php?file=<?php echo urlencode($phieuthuctap); ?>&type=phieuthuctap" target="_blank" class="file-link" title="<?php echo htmlspecialchars($phieuthuctap); ?>">
                                            <?php
                                                $maxLen = 12;
                                                $tenHienThi = (mb_strlen($phieuthuctap) > $maxLen)
                                                    ? mb_substr($phieuthuctap, 0, $maxLen) . '...'
                                                    : $phieuthuctap;
                                                echo htmlspecialchars($tenHienThi);
                                            ?>
                                        </a>
                                        <a href="download_file.php?file=<?php echo urlencode($phieuthuctap); ?>&type=phieuthuctap" class="btn-action btn-download" title="Tải xuống">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <?php if ($cho_phep_nop): ?>
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_phieuthuctap" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc muốn xóa phiếu thực tập này?');" title="Xóa">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-success">✓ Đã nộp</small>
                                <?php else: ?>
                                    <?php if ($cho_phep_nop): ?>
                                        <span class="text-muted">Chưa nộp<br>(bấm để upload)</span>
                                    <?php else: ?>
                                        <span class="text-danger">Không thể nộp</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Panel Phiếu khảo sát -->
                <div class="col-md-3">
                    <div class="upload-panel panel-khoasat" data-type="khoasat" style="<?php if(!$cho_phep_nop) echo 'opacity:0.6;pointer-events:none;'; ?>">
                        <div class="panel-content">
                            <i class="fa fa-list-alt panel-icon"></i>
                            <div class="panel-title">Phiếu khảo sát</div>
                            <div class="panel-status">
                                <?php if (!$cho_phep_nop): ?>
                                    <div class="alert alert-warning" style="margin: 8px 0; padding: 6px 8px; font-size: 11px; border-radius: 4px;">
                                        <i class="fa fa-lock"></i> Đã đóng
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($khoasat): ?>
                                    <div class="file-actions">
                                        <a href="download_file.php?file=<?php echo urlencode($khoasat); ?>&type=khoasat" target="_blank" class="file-link" title="<?php echo htmlspecialchars($khoasat); ?>">
                                            <?php
                                                $maxLen = 12;
                                                $tenHienThi = (mb_strlen($khoasat) > $maxLen)
                                                    ? mb_substr($khoasat, 0, $maxLen) . '...'
                                                    : $khoasat;
                                                echo htmlspecialchars($tenHienThi);
                                            ?>
                                        </a>
                                        <a href="download_file.php?file=<?php echo urlencode($khoasat); ?>&type=khoasat" class="btn-action btn-download" title="Tải xuống">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <?php if ($cho_phep_nop): ?>
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="xoa_khoasat" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc muốn xóa phiếu khảo sát này?');" title="Xóa">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-success">✓ Đã nộp</small>
                                <?php else: ?>
                                    <?php if ($cho_phep_nop): ?>
                                        <span class="text-muted">Chưa nộp<br>(bấm để upload)</span>
                                    <?php else: ?>
                                        <span class="text-danger">Không thể nộp</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel hiển thị điểm -->
            <?php if ($diem_data): ?>
            <div class="row" style="margin-top: 30px;">
                <div class="col-md-12">
                    <div class="panel panel-success" style="border-radius: 16px; box-shadow: 0 4px 18px rgba(40,167,69,0.15);">
                        <div class="panel-heading" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 16px 16px 0 0; padding: 20px; border: none;">
                            <h3 class="panel-title" style="font-size: 1.4rem; font-weight: 700; margin: 0;">
                                <i class="fa fa-star" style="margin-right: 10px;"></i>
                                Kết quả đánh giá từ giáo viên
                            </h3>
                        </div>
                        <div class="panel-body" style="background: #f8fff9; border-radius: 0 0 16px 16px; padding: 25px;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="score-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                        <div class="score-label" style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                                            <i class="fa fa-file-text-o" style="color: #28a745; margin-right: 8px;"></i>
                                            Điểm Báo cáo
                                        </div>
                                        <div class="score-value" style="font-size: 2rem; font-weight: 700; color: #28a745;">
                                            <?php echo !is_null($diem_data['Diem_BaoCao']) ? number_format($diem_data['Diem_BaoCao'], 1) : 'Chưa chấm'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="score-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                        <div class="score-label" style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                                            <i class="fa fa-clock-o" style="color: #17a2b8; margin-right: 8px;"></i>
                                            Điểm Chuyên cần
                                        </div>
                                        <div class="score-value" style="font-size: 2rem; font-weight: 700; color: #17a2b8;">
                                            <?php echo !is_null($diem_data['Diem_ChuyenCan']) ? number_format($diem_data['Diem_ChuyenCan'], 1) : 'Chưa chấm'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="score-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                        <div class="score-label" style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                                            <i class="fa fa-graduation-cap" style="color: #ffc107; margin-right: 8px;"></i>
                                            Điểm Chuẩn nghề
                                        </div>
                                        <div class="score-value" style="font-size: 2rem; font-weight: 700; color: #ffc107;">
                                            <?php echo !is_null($diem_data['Diem_ChuanNghe']) ? number_format($diem_data['Diem_ChuanNghe'], 1) : 'Chưa chấm'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="score-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                        <div class="score-label" style="font-weight: 600; color: #495057; margin-bottom: 8px;">
                                            <i class="fa fa-cogs" style="color: #6f42c1; margin-right: 8px;"></i>
                                            Điểm Thực tế
                                        </div>
                                        <div class="score-value" style="font-size: 2rem; font-weight: 700; color: #6f42c1;">
                                            <?php echo !is_null($diem_data['Diem_ThucTe']) ? number_format($diem_data['Diem_ThucTe'], 1) : 'Chưa chấm'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($diem_tong !== null): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="total-score" style="background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); border-radius: 16px; padding: 25px; text-align: center; margin-top: 20px; color: white; box-shadow: 0 4px 16px rgba(0,123,255,0.3);">
                                        <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">
                                            <i class="fa fa-trophy" style="margin-right: 10px;"></i>
                                            ĐIỂM TỔNG KẾT
                                        </div>
                                        <div style="font-size: 3rem; font-weight: 800; margin-bottom: 10px;">
                                            <?php echo number_format($diem_tong, 2); ?>
                                        </div>
                                        <div style="font-size: 1rem; opacity: 0.9;">
                                            <?php 
                                                if ($diem_tong >= 34) echo "Xuất sắc";
                                                elseif ($diem_tong >= 28) echo "Giỏi";
                                                elseif ($diem_tong >= 26) echo "Khá";
                                                elseif ($diem_tong >= 20) echo "Trung bình";
                                                else echo "Yếu";
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($diem_data['GhiChu'])): ?>
                            <div class="row" style="margin-top: 20px;">
                                <div class="col-md-12">
                                    <div class="comment-section" style="background: white; border-radius: 12px; padding: 20px; border-left: 4px solid #007bff; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                        <h5 style="color: #007bff; font-weight: 600; margin-bottom: 12px;">
                                            <i class="fa fa-comment" style="margin-right: 8px;"></i>
                                            Nhận xét từ giáo viên
                                        </h5>
                                        <p style="margin: 0; color: #495057; line-height: 1.6; font-size: 14px;">
                                            <?php echo nl2br(htmlspecialchars($diem_data['GhiChu'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    <!-- Modal upload file từng loại -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" role="dialog" aria-labelledby="uploadFileModalLabel">
  <div class="modal-dialog" role="document">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="upload_type" id="upload_type">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="uploadFileModalLabel">Tải lên file</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body" id="upload-modal-body">
          <!-- Nội dung sẽ được thay đổi bằng JS -->
        </div>
        <div class="modal-footer">
          <button type="submit" name="upload_file_panel" class="btn btn-success">Tải lên</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
        </div>
      </div>
    </form>
  </div>
</div>
    </div>  
        <?php require_once __DIR__ . "/../../template/footer.php"; ?>                                               
</body>
</html>
<script>
$(document).ready(function() {
    $('.upload-panel').click(function(e) {
        // Kiểm tra xem click có phải vào button xóa không
        if ($(e.target).closest('button[name^="xoa_"]').length > 0) {
            return; // Không làm gì nếu click vào nút xóa
        }
        
        // Kiểm tra xem click có phải vào link download hoặc view không
        if ($(e.target).closest('a').length > 0) {
            return; // Không làm gì nếu click vào link
        }

        var type = $(this).data('type');
        
        // Kiểm tra trạng thái cho phép nộp của tất cả các panel
        if ($(this).css('pointer-events') === 'none') {
            return; // Không cho phép nếu panel bị khóa
        }

        var label = '';
        var accept = '';
        if(type === 'baocao') {
            label = 'Chọn file báo cáo (.doc, .docx):';
            accept = '.doc,.docx';
        } else if(type === 'nhanxet') {
            label = 'Chọn ảnh nhận xét công ty (.jpg, .jpeg, .png):';
            accept = '.jpg,.jpeg,.png';
        } else if(type === 'phieuthuctap') {
            label = 'Chọn ảnh phiếu thực tập (.jpg, .jpeg, .png):';
            accept = '.jpg,.jpeg,.png';
        } else if(type === 'khoasat') {
            label = 'Chọn ảnh phiếu khảo sát (.jpg, .jpeg, .png):';
            accept = '.jpg,.jpeg,.png';
        }
        $('#upload_type').val(type);
        $('#upload-modal-body').html('<label>'+label+'</label><input type="file" name="upload_file" class="form-control" accept="'+accept+'" required>');
        $('#uploadFileModal').modal('show');
    });
});
</script>
