<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$id_sv = $_POST['idsv'] ?? '';
$tuan = $_POST['tuan'] ?? '';
$id_gvhd = '2'; // hoặc lấy từ session

// Nếu thiếu dữ liệu truyền vào thì chuyển sang 404
if (empty($id_sv) || empty($tuan)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/404.php";
    exit;
}

// Lấy thông tin sinh viên (bỏ Lop, Nghanh)
$stmt = $conn->prepare("SELECT Ten, MSSV FROM SinhVien WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_sv]);
$sv = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy sinh viên thì chuyển sang 404
if (!$sv) {
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/404.php";
    exit;
}

// Lấy báo cáo các ngày trong tuần
$stmt = $conn->prepare("SELECT Thu, CongviecThucHien, DanhGia FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ? ORDER BY Thu ASC");
$stmt->execute([$id_sv, $id_gvhd, 'Tuần '.$tuan]);
$baocaos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý lưu lời phê
$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loiphe'])) {
    $loiphe = $_POST['loiphe'];
    // Cập nhật lời phê cho tất cả các ngày trong tuần này
    $stmt = $conn->prepare("UPDATE BaoCao SET DanhGia = ? WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ?");
    if ($stmt->execute([$loiphe, $id_sv, $id_gvhd, 'Tuần '.$tuan])) {
        $successMsg = "Đã lưu lời phê!";
        // Reload lại dữ liệu
        $stmt = $conn->prepare("SELECT Thu, CongviecThucHien, DanhGia FROM BaoCao WHERE IDSV = ? AND IdGVHD = ? AND Tuan = ? ORDER BY Thu ASC");
        $stmt->execute([$id_sv, $id_gvhd, 'Tuần '.$tuan]);
        $baocaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
$danhGia = $baocaos[0]['DanhGia'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết báo cáo sinh viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>
<body>
<div class="container" style="margin-top:40px;">
    <h3>Chi tiết báo cáo tuần <?php echo htmlspecialchars($tuan); ?></h3>
    <p><strong>Sinh viên:</strong> <?php echo htmlspecialchars($sv['Ten'] ?? ''); ?> (<?php echo htmlspecialchars($sv['MSSV'] ?? ''); ?>)</p>
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Thứ</th>
                <th>Công việc thực hiện</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($baocaos as $bc): ?>
                <tr>
                    <td>Thứ <?php echo $bc['Thu']; ?></td>
                    <td><?php echo htmlspecialchars($bc['CongviecThucHien']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <form method="post">
        <div class="form-group">
            <label for="loiphe">Lời phê của giáo viên hướng dẫn</label>
            <textarea class="form-control" id="loiphe" name="loiphe" rows="5" maxlength="255"><?php echo htmlspecialchars($danhGia); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Lưu lời phê</button>
        <a href="/datn/pages/giaovien/xembaocaosinhvien" class="btn btn-default">Quay lại</a>
        <input type="hidden" name="idsv" value="<?php echo htmlspecialchars($id_sv); ?>">
        <input type="hidden" name="tuan" value="<?php echo htmlspecialchars($tuan); ?>">
    </form>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
</body>
</html>