<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $conn->prepare("SELECT ID,TenDot,Loai,Nam,TenNguoiMoDot,NguoiQuanLy,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();
$successMessage = "";
$notification = "";

$canbokhoa = $conn->query("SELECT ID_TaiKhoan, Ten FROM canbokhoa WHERE TrangThai = 1")->fetchAll();

if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDot = $_POST['TenDot'] ?? '';
    $nam = $_POST['Nam'] ?? '';
    $loai = $_POST['Loai'] ?? '';
    $thoiGianKetThuc = $_POST['ThoiGianKetThuc'] ?? '';
    $nguoiQuanLy = $_POST['NguoiQuanLy'] ?? '';

    $stmt = $conn->prepare("SELECT COUNT(*) FROM DOTTHUCTAP WHERE TenDot = :tenDot AND ID != :id");
    $stmt->execute(['tenDot' => $tenDot, 'id' => $id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $notification = "Tên đợt đã tồn tại!";
    } else {
        $updateStmt = $conn->prepare("
            UPDATE DOTTHUCTAP SET
                TenDot = :tenDot,
                Nam = :nam,
                Loai = :loai,
                ThoiGianKetThuc = :thoiGianKetThuc,
                NguoiQuanLy = :nguoiQuanLy
            WHERE ID = :id
        ");

        $updateStmt->execute([
            'tenDot' => $tenDot,
            'nam' => $nam,
            'loai' => $loai,
            'thoiGianKetThuc' => $thoiGianKetThuc,
            'nguoiQuanLy' => $nguoiQuanLy,
            'id' => $id
        ]);

        $successMessage = "Cập nhật thành công!";

        $stmt = $conn->prepare("SELECT ID,TenDot,Loai,Nam,TenNguoiMoDot,NguoiQuanLy,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
        $stmt->execute(['id' => $id]);
        $dot = $stmt->fetch();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa đợt thực tập</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Chỉnh sửa đợt: <?= htmlspecialchars($dot['TenDot']) ?></h1>

                <div class="col-md-offset">
                    <?php if (!empty($successMessage)): ?>
                        <div id="successAlert" class="alert alert-success">
                            <?= $successMessage ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($notification)): ?>
                        <div id="notificationAlert" class="alert alert-success">
                            <?= $notification ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="post" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Tên đợt</label>
                        <div class="col-sm-10">
                            <input type="text" name="TenDot" class="form-control"
                                value="<?= htmlspecialchars($dot['TenDot']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Năm</label>
                        <div class="col-sm-10">
                            <input type="number" name="Nam" class="form-control"
                                value="<?= htmlspecialchars($dot['Nam']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Loại</label>
                        <div class="col-sm-10">
                            <select id="Loai" name="Loai" class="form-control" required>
                                <option value="Cao đẳng" <?= $dot['Loai'] == 'Cao đẳng' ? 'selected' : '' ?>>Cao đẳng
                                </option>
                                <option value="Cao đẳng ngành" <?= $dot['Loai'] == 'Cao đẳng ngành' ? 'selected' : '' ?>>
                                    Cao đẳng ngành</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Thời gian kết thúc</label>
                        <div class="col-sm-10">
                            <?php
                            $ThangSau = date('Y-m-01', strtotime('first day of next month'));
                            ?>
                            <input class="form-control"
                                value="<?= isset($dot['ThoiGianKetThuc']) ? htmlspecialchars($dot['ThoiGianKetThuc']) : '' ?>"
                                id="ThoiGianKetThuc" name="ThoiGianKetThuc" type="date" min="<?= $ThangSau ?>"
                                placeholder="Chọn thời gian kết thúc" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Người quản lý</label>
                        <div class="col-sm-10">
                            <select id="NguoiQuanLy" name="NguoiQuanLy" class="form-control" required>
                                <?php foreach ($canbokhoa as $cb): ?>
                                    <option value="<?= $cb['Ten'] ?>" <?= $dot['NguoiQuanLy'] == $cb['Ten'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cb['Ten']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success btn-lg">Lưu thay đổi</button>
                        <a href="/datn/pages/canbo/chitietdot?id=<?= urlencode($id) ?>"
                            class="btn btn-default btn-lg">Thoát</a>

                    </div>
                </form>
            </div>
        </div>
    </div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const alertBox = document.getElementById('successAlert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 2000);
        }
    });
    window.addEventListener('DOMContentLoaded', () => {
        const alertBox = document.getElementById('notificationAlert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 2000);
        }
    });
</script>
