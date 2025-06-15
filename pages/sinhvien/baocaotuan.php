<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Giả sử ID sinh viên hiện tại là 1
$id_sv = '1';

// Lấy ID_GVHD của sinh viên này
$stmt = $conn->prepare("SELECT ID_GVHD FROM SinhVien WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_sv]);
$id_gvhd = $stmt->fetchColumn();

// Kiểm tra giáo viên hướng dẫn có mở tuần nào không
$tuanMo = [];
if ($id_gvhd) {
    $stmt = $conn->prepare("SELECT Tuan FROM TuanBaoCao WHERE ID_GVHD = ? AND TrangThai = 1 ORDER BY Tuan ASC");
    $stmt->execute([$id_gvhd]);
    $tuanMo = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Xác định tuần đang chọn (nếu có nhiều tuần mở)
$tuanDuocNhap = null;
if (count($tuanMo) > 0) {
    if (isset($_GET['tuan']) && in_array($_GET['tuan'], $tuanMo)) {
        $tuanDuocNhap = $_GET['tuan'];
    } else {
        $tuanDuocNhap = $tuanMo[0];
    }
}

// Xử lý lưu báo cáo tuần nếu có submit
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tuanDuocNhap) {
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
}

// Lấy đánh giá nếu có (giả sử trường DanhGia trong bảng BaoCao)
$danhGia = '';
if ($tuanDuocNhap) {
    $stmt = $conn->prepare("SELECT DanhGia FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ?");
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
</head>
<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>Báo Cáo Tuần</h1>
                </div>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                <?php endif; ?>
                <?php if ($successMsg): ?>
                    <div class="alert alert-success"><?php echo $successMsg; ?></div>
                <?php endif; ?>

                <?php if ($tuanDuocNhap): ?>
                    <!-- Chọn tuần nếu có nhiều tuần mở -->
                    <?php if (count($tuanMo) > 0): ?>
                        <form method="get" style="margin-bottom:20px;">
                            <label for="tuanChon"><strong>Chọn tuần báo cáo:</strong></label>
                            <select name="tuan" id="tuanChon" onchange="this.form.submit()" class="form-control" style="width:auto;display:inline-block;">
                                <?php foreach ($tuanMo as $tuan): ?>
                                    <option value="<?php echo $tuan; ?>" <?php if ($tuan == $tuanDuocNhap) echo 'selected'; ?>>
                                        Tuần <?php echo $tuan; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Tuần <?php echo $tuanDuocNhap; ?></h3>
                    </div>
                    <form method="post">
                        <?php for ($i = 2; $i <= 7; $i++): ?>
                            <div class="row" style="padding-top: 10px;">
                                <label for="Thu-<?php echo $i; ?>">Thứ <?php echo $i; ?></label>
                                <input type="text" class="form-control" id="Thu-<?php echo $i; ?>" name="Thu-<?php echo $i; ?>"
                                       placeholder="Công việc thực hiện" maxlength="255"
                                       pattern="^[a-zA-Z0-9À-ỹà-ỹ\s.,-]*$"
                                       title="Không được nhập ký tự đặc biệt">
                            </div>
                        <?php endfor; ?>
                        <!-- Dòng đánh giá readonly -->
                        <div class="row" style="padding-top: 10px;">
                            <label for="DanhGia">Đánh giá của giáo viên hướng dẫn</label>
                            <textarea class="form-control form-control-lg" id="DanhGia" name="DanhGia"
                                      readonly style="height:80px;font-size:18px;" maxlength="255"><?php echo htmlspecialchars($danhGia); ?></textarea>
                        </div>
                        <div class="justify-content-center align-items-center" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px;">
                            <button type="submit" class="btn btn-success" style="display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-pencil" style="margin-right: 5px;"></i> Lưu
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Hiện tại giáo viên hướng dẫn của bạn chưa mở tuần nào để báo cáo!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>