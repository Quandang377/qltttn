<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['giay_id'])) {
    $_SESSION['giay_id'] = $_POST['giay_id'];
    $id = $_POST['giay_id'];
} else {
    $id = $_SESSION['giay_id'] ?? '';
}

$giay = null;
$tenSinhVien = '';
$mssv = '';
$message = '';

// Lấy thông tin giấy giới thiệu và sinh viên
if ($id) {
    $stmt = $conn->prepare("
        SELECT g.*, s.Ten AS TenSinhVien, s.MSSV
        FROM GiayGioiThieu g
        LEFT JOIN SinhVien s ON g.IdSinhVien = s.ID_TaiKhoan
        WHERE g.ID = ?
    ");
    $stmt->execute([$id]);
    $giay = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($giay) {
        $tenSinhVien = $giay['TenSinhVien'] ?? '';
        $mssv = $giay['MSSV'] ?? '';
    }
}

// Xử lý duyệt giấy giới thiệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duyet']) && $id) {
    $stmt = $conn->prepare("UPDATE GiayGioiThieu SET TrangThai = 1 WHERE ID = ?");
    if ($stmt->execute([$id])) {
        $message = "Đã duyệt giấy giới thiệu thành công!";
        // Cập nhật lại dữ liệu sau khi duyệt
        $stmt = $conn->prepare("
            SELECT g.*, s.Ten AS TenSinhVien, s.MSSV
            FROM GiayGioiThieu g
            LEFT JOIN SinhVien s ON g.IdSinhVien = s.ID_TaiKhoan
            WHERE g.ID = ?
        ");
        $stmt->execute([$id]);
        $giay = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($giay) {
            $tenSinhVien = $giay['TenSinhVien'] ?? '';
            $mssv = $giay['MSSV'] ?? '';
        }
    } else {
        $message = "Duyệt thất bại!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết giấy giới thiệu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>
<body>
    <div class="container" style="margin-top:40px;">
        <h2>Chi tiết giấy giới thiệu</h2>
        <h3>
            <?php if ($tenSinhVien || $mssv): ?>
                <?php echo htmlspecialchars($tenSinhVien); ?>
                <?php if ($mssv): ?>
                    (<?php echo htmlspecialchars($mssv); ?>)
                <?php endif; ?>
            <?php endif; ?>
        </h3>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($giay): ?>
            <table class="table table-bordered">
                <tr><th>Tên công ty</th><td><?php echo htmlspecialchars($giay['TenCty']); ?></td></tr>
                <tr><th>Mã số thuế</th><td><?php echo htmlspecialchars($giay['MaSoThue']); ?></td></tr>
                <tr><th>Địa chỉ</th><td><?php echo htmlspecialchars($giay['DiaChi']); ?></td></tr>
                <tr><th>Lĩnh vực</th><td><?php echo htmlspecialchars($giay['LinhVuc']); ?></td></tr>
                <tr><th>SĐT</th><td><?php echo htmlspecialchars($giay['Sdt']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($giay['Email']); ?></td></tr>
                <tr><th>Trạng thái</th>
                    <td>
                        <?php
                        if ($giay['TrangThai'] == 0) echo "Đang chờ duyệt";
                        elseif ($giay['TrangThai'] == 1) echo "Đã duyệt";
                        else echo "Từ chối";
                        ?>
                    </td>
                </tr>
            </table>
            <?php if ($giay['TrangThai'] == 0): ?>
                <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn duyệt giấy giới thiệu này?');">
                    <input type="hidden" name="giay_id" value="<?php echo htmlspecialchars($giay['ID']); ?>">
                    <div class="clearfix" style="margin-top:20px;">
                        <div style="float:left;">
                            <a href="/datn/pages/giaovien/quanlygiaygioithieu" class="btn btn-default btn-lg">Quay lại</a>
                        </div>
                        <div style="float:right;">
                            <button type="submit" name="duyet" class="btn btn-success btn-lg">Duyệt</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="clearfix" style="margin-top:20px;">
                    <div style="float:left;">
                        <a href="/datn/pages/giaovien/quanlygiaygioithieu" class="btn btn-default btn-lg">Quay lại</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger">Không tìm thấy giấy giới thiệu!</div>
        <?php endif; ?>
    </div>
</body>
</html>