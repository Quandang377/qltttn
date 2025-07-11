<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Lấy ID sinh viên từ session
$id_sv = $_SESSION['user']['ID_TaiKhoan'];

// Lấy ID_GVHD của sinh viên này và thông tin đợt thực tập
$stmt = $conn->prepare("SELECT sv.ID_GVHD, sv.ID_Dot, dt.TenDot, dt.TrangThai FROM SinhVien sv LEFT JOIN DotThucTap dt ON sv.ID_Dot = dt.ID WHERE sv.ID_TaiKhoan = ?");
$stmt->execute([$id_sv]);
$sinhVienInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$id_gvhd = $sinhVienInfo['ID_GVHD'] ?? null;
$dotThucTapInfo = [
    'ID_Dot' => $sinhVienInfo['ID_Dot'] ?? null,
    'TenDot' => $sinhVienInfo['TenDot'] ?? null,
    'TrangThai' => $sinhVienInfo['TrangThai'] ?? null
];

// Kiểm tra trạng thái đợt thực tập giống như dangkygiaygioithieu.php
$dotKhongHoatDong = !$dotThucTapInfo['ID_Dot'] || $dotThucTapInfo['TrangThai'] < 3;

$tatCaTuan = [];
if ($id_gvhd && !$dotKhongHoatDong) {
    $stmt = $conn->prepare("SELECT DISTINCT Tuan FROM TuanBaoCao WHERE ID_GVHD = ? ORDER BY Tuan ASC");
    $stmt->execute([$id_gvhd]);
    $tatCaTuan = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Lấy danh sách các tuần đang mở (cho phép nộp báo cáo)
$tuanMo = [];
if ($id_gvhd && !$dotKhongHoatDong) {
    $stmt = $conn->prepare("SELECT Tuan FROM TuanBaoCao WHERE ID_GVHD = ? AND TrangThai = 1 ORDER BY Tuan ASC");
    $stmt->execute([$id_gvhd]);
    $tuanMo = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Xác định tuần đang chọn (nếu có)
$tuanDuocNhap = null;
if (isset($_GET['tuan']) && in_array($_GET['tuan'], $tatCaTuan)) {
    $tuanDuocNhap = $_GET['tuan'];
} elseif (count($tatCaTuan) > 0) {
    $tuanDuocNhap = $tatCaTuan[0];
}

// Kiểm tra xem tuần hiện tại có đang mở không
$tuanHienTaiMo = in_array($tuanDuocNhap, $tuanMo);

// Xử lý lưu báo cáo tuần nếu có submit và tuần đang mở
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tuanDuocNhap && $tuanHienTaiMo && !$dotKhongHoatDong) {
    $specialCharPattern = '/[^a-zA-Z0-9À-ỹà-ỹ\s.,-]/u';
    $hasSpecialChar = false;
    $congviecArr = [];
    for ($i = 2; $i <= 7; $i++) {
        $val = $_POST['Thu-'.$i] ?? '';
        if (preg_match($specialCharPattern, $val)) {
            $hasSpecialChar = true;
        }
        $congviecArr[$i] = $val;
    }
    if ($hasSpecialChar) {
        $errorMsg = "Không được nhập ký tự đặc biệt!";
    } else {
        // Xóa báo cáo cũ tuần này (nếu có)
        $stmt = $conn->prepare("DELETE FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ?");
        $stmt->execute([$id_sv, $id_gvhd, 'Tuần '.$tuanDuocNhap]);

        // Lưu từng ngày vào DB
        $success = true;
        foreach ($congviecArr as $thu => $congviec) {
            $stmt = $conn->prepare("INSERT INTO BaoCao (IDSV, IdGVHD, Tuan, Thu, CongviecThucHien, TrangThai) VALUES (?, ?, ?, ?, ?, 1)");
            if (!$stmt->execute([$id_sv, $id_gvhd, 'Tuần '.$tuanDuocNhap, $thu, $congviec])) {
                $success = false;
            }
        }
        if ($success) {
            $successMsg = "Đã lưu báo cáo tuần thành công!";
        } else {
            $errorMsg = "Lưu báo cáo thất bại!";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $dotKhongHoatDong) {
    $errorMsg = "Đợt thực tập của bạn đã kết thúc hoặc chưa bắt đầu. Không thể nộp báo cáo!";
}

// Lấy dữ liệu báo cáo đã nộp (nếu có)
$baoCaoDaNop = [];
$danhGia = '';
if ($tuanDuocNhap && !$dotKhongHoatDong) {
    // Lấy công việc đã nộp theo từng thứ
    $stmt = $conn->prepare("SELECT Thu, CongviecThucHien FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ? ORDER BY Thu ASC");
    $stmt->execute([$id_sv, $id_gvhd, 'Tuần '.$tuanDuocNhap]);
    $baoCaoDaNop = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Lấy đánh giá
    $stmt = $conn->prepare("SELECT DanhGia FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ? LIMIT 1");
    $stmt->execute([$id_sv, $id_gvhd, 'Tuần '.$tuanDuocNhap]);
    $danhGia = $stmt->fetchColumn() ?: '';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Tuần</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            background: none;
        }
        .page-header h1, .page-header {
            font-size: 2.2rem;
            font-weight: 700;
            color: #007bff;
            letter-spacing: 1px;
            margin-bottom: 32px;
            text-align: center;
            text-shadow: 0 2px 8px #b6d4fe44;
        }
        .form-control, select.form-control {
            border-radius: 8px;
            border: 1.5px solid #b6d4fe;
            font-size: 16px;
            padding: 8px 14px;
            background: #fafdff;
            transition: border 0.2s;
        }
        .form-control:focus, select.form-control:focus {
            border: 1.5px solid #007bff;
            background: #f0f8ff;
            outline: none;
        }
        .readonly-field {
            background-color: #f8f9fa !important;
            cursor: not-allowed;
            color: #888;
        }
        label {
            font-weight: 600;
            color: #007bff;
            margin-bottom: 6px;
        }
        .badge-danger {
            background: #ff4d4f;
            color: #fff;
            font-size: 15px;
            padding: 6px 16px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-success, .btn-primary {
            border-radius: 8px !important;
            font-weight: 600;
            box-shadow: 0 2px 8px #007bff22;
            border: none;
            background: linear-gradient(90deg, #007bff 70%, #5bc0f7 100%) !important;
            color: #fff !important;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-success:hover, .btn-primary:hover {
            background: linear-gradient(90deg, #0056d2 70%, #3fa9f5 100%) !important;
            color: #fff !important;
            box-shadow: 0 4px 16px #007bff33;
        }
        .alert {
            border-radius: 10px;
            font-size: 16px;
        }
        select.form-control {
            width: auto;
            display: inline-block;
            min-width: 120px;
            margin-left: 8px;
        }
        @media (max-width: 991px) {
            #page-wrapper { padding: 10px; }
            .page-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>Báo Cáo Tuần</h1>
                </div>
                
                <!-- Thông báo trạng thái đợt thực tập -->
                <?php if ($dotKhongHoatDong): ?>
                    <?php if ($dotThucTapInfo['TrangThai'] < 3): ?>
                        <div class="alert alert-warning text-center" style="margin-bottom: 30px;">
                            <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>
                            <strong>Đợt của bạn đã kết thúc hoặc chưa bắt đầu</strong>
                            <?php if ($dotThucTapInfo['TenDot']): ?>
                                <br><small>Đợt thực tập: <?php echo htmlspecialchars($dotThucTapInfo['TenDot']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center" style="margin-bottom: 30px;">
                            <i class="fa fa-info-circle" style="margin-right: 8px;"></i>
                            <strong>Đợt thực tập:</strong> <?php echo htmlspecialchars($dotThucTapInfo['TenDot']); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning text-center" style="margin-bottom: 30px;">
                        <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        <strong>Chưa có đợt thực tập:</strong> Bạn chưa được phân công vào đợt thực tập nào.
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                <?php endif; ?>
                <?php if ($successMsg): ?>
                    <div class="alert alert-success"><?php echo $successMsg; ?></div>
                <?php endif; ?>

                <?php if ($tuanDuocNhap && !$dotKhongHoatDong): ?>
                    <!-- Chọn tuần (hiển thị tất cả tuần đã từng mở) -->
                    <form method="get" style="margin-bottom:20px;">
                        <label for="tuanChon"><strong>Chọn tuần báo cáo:</strong></label>
                        <select name="tuan" id="tuanChon" onchange="this.form.submit()" class="form-control" style="height:50px;display:inline-block;">
                            <?php foreach ($tatCaTuan as $tuan): ?>
                                <option value="<?php echo $tuan; ?>" <?php if ($tuan == $tuanDuocNhap) echo 'selected'; ?>>
                                    Tuần <?php echo $tuan; ?><?php if (!in_array($tuan, $tuanMo)) echo ' (Đã đóng)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="d-flex justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Tuần <?php echo $tuanDuocNhap; ?></h3>
                        <?php if (!$tuanHienTaiMo): ?>
                            <span class="badge badge-danger">Đã đóng</span>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post">
                        <?php for ($i = 2; $i <= 7; $i++): ?>
                            <div class="row" style="padding-top: 10px;">
                                <label for="Thu-<?php echo $i; ?>">Thứ <?php echo $i; ?></label>
                                <textarea class="form-control <?php if (!$tuanHienTaiMo || $dotKhongHoatDong) echo 'readonly-field'; ?>" 
                                    id="Thu-<?php echo $i; ?>" 
                                    name="Thu-<?php echo $i; ?>"
                                    placeholder="Công việc thực hiện" 
                                    maxlength="250"
                                    rows="4"
                                    style="resize: none;"
                                    <?php if (!$tuanHienTaiMo || $dotKhongHoatDong) echo 'readonly'; ?>
                            ><?php echo isset($baoCaoDaNop[$i]) ? htmlspecialchars($baoCaoDaNop[$i]) : ''; ?></textarea>
                            </div>
                        <?php endfor; ?>
                        
                        <!-- Dòng đánh giá readonly -->
                        <div class="row" style="padding-top: 10px;">
                            <label for="DanhGia">Đánh giá của giáo viên hướng dẫn</label>
                            <textarea class="form-control form-control-lg readonly-field" id="DanhGia" name="DanhGia"
                                      readonly style="height:80px;font-size:18px;" maxlength="255"><?php echo htmlspecialchars($danhGia); ?></textarea>
                        </div>
                        
                        <?php if ($tuanHienTaiMo && !$dotKhongHoatDong): ?>
                            <div class="justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px;">
                                <button type="submit" class="btn btn-success" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-pencil" style="margin-right: 5px;"></i> Lưu
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php elseif ($dotKhongHoatDong): ?>
                    <div class="alert alert-info text-center">
                        <i class="fa fa-info-circle" style="margin-right: 8px;"></i>
                        Bạn không thể nộp báo cáo tuần khi đợt thực tập đã kết thúc hoặc chưa bắt đầu.
                    </div>
                <?php elseif ($tuanDuocNhap): ?>
                    <!-- Trường hợp có tuần nhưng đợt không hoạt động - hiển thị readonly -->
                    <div class="alert alert-warning text-center" style="margin-bottom: 20px;">
                        <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        Đợt thực tập đã kết thúc. Bạn chỉ có thể xem báo cáo, không thể chỉnh sửa.
                    </div>
                    
                    <form method="get" style="margin-bottom:20px;">
                        <label for="tuanChon"><strong>Chọn tuần báo cáo:</strong></label>
                        <select name="tuan" id="tuanChon" onchange="this.form.submit()" class="form-control" style="height:50px;display:inline-block;">
                            <?php foreach ($tatCaTuan as $tuan): ?>
                                <option value="<?php echo $tuan; ?>" <?php if ($tuan == $tuanDuocNhap) echo 'selected'; ?>>
                                    Tuần <?php echo $tuan; ?> (Chỉ xem)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="d-flex justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Tuần <?php echo $tuanDuocNhap; ?></h3>
                        <span class="badge badge-danger">Đã kết thúc</span>
                    </div>
                    
                    <form method="post">
                        <?php for ($i = 2; $i <= 7; $i++): ?>
                            <div class="row" style="padding-top: 10px;">
                                <label for="Thu-<?php echo $i; ?>">Thứ <?php echo $i; ?></label>
                                <textarea class="form-control readonly-field" 
                                    id="Thu-<?php echo $i; ?>" 
                                    name="Thu-<?php echo $i; ?>"
                                    placeholder="Công việc thực hiện" 
                                    maxlength="250"
                                    rows="4"
                                    style="resize: none;"
                                    readonly
                            ><?php echo isset($baoCaoDaNop[$i]) ? htmlspecialchars($baoCaoDaNop[$i]) : ''; ?></textarea>
                            </div>
                        <?php endfor; ?>
                        
                        <!-- Dòng đánh giá readonly -->
                        <div class="row" style="padding-top: 10px;">
                            <label for="DanhGia">Đánh giá của giáo viên hướng dẫn</label>
                            <textarea class="form-control form-control-lg readonly-field" id="DanhGia" name="DanhGia"
                                      readonly style="height:80px;font-size:18px;" maxlength="255"><?php echo htmlspecialchars($danhGia); ?></textarea>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Bạn chưa có tuần báo cáo nào!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>