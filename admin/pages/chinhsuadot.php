<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $conn->prepare("SELECT ID,TenDot,Loai,Nam,                                                  NguoiMoDot,NguoiQuanLy,ThoiGianBatDau,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();
$successMessage = "";
$notification = "";

$stmt = $conn->prepare("SELECT ID, TenDot, Loai, Nam, NguoiMoDot, NguoiQuanLy, ThoiGianBatDau, ThoiGianKetThuc, TrangThai 
                        FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();

$canbokhoa = $conn->query("SELECT ID_TaiKhoan, Ten FROM CanBoKhoa WHERE TrangThai = 1")->fetchAll(PDO::FETCH_ASSOC);

if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDot = $_POST['TenDot'] ?? '';
    $nam = $_POST['Nam'] ?? '';
    $loai = $_POST['Loai'] ?? '';
    $thoiGianBatDau = $_POST['ThoiGianBatDau'] ?? '';
    $thoiGianKetThuc = $_POST['ThoiGianKetThuc'] ?? '';
    $nguoiQuanLy = intval($_POST['NguoiQuanLy']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM DOTTHUCTAP WHERE TenDot = :tenDot AND ID != :id");
    $stmt->execute(['tenDot' => $tenDot, 'id' => $id]);
    $count = $stmt->fetchColumn();
    $errors = [];
    if ($count > 0) {
        $errors[] = "Tên đợt đã tồn tại!";
    }
    $today = date('Y-m-d');
    $ngayMai = date('Y-m-d', strtotime('+1 day'));
    if ($thoiGianBatDau < $ngayMai) {
        $errors[] = "Thời gian bắt đầu phải từ ngày mai trở đi!";
    }
    if ($thoiGianBatDau >= $thoiGianKetThuc) {
        $errors[] = "Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc!";
    }
    $diff = (strtotime($thoiGianKetThuc) - strtotime($thoiGianBatDau)) / (60 * 60 * 24);
    if ($diff < 28) {
        $errors[] = "Thời gian kết thúc phải cách thời gian bắt đầu ít nhất 4 tuần!";
    }

    if (!empty($errors)) {
        $notification = implode("<br>", $errors);
    } else {
        $updateStmt = $conn->prepare("
            UPDATE DOTTHUCTAP SET
                TenDot = :tenDot,
                Nam = :nam,
                Loai = :loai,
                ThoiGianBatDau = :thoiGianBatDau,
                ThoiGianKetThuc = :thoiGianKetThuc,
                NguoiQuanLy = :nguoiQuanLy
            WHERE ID = :id
        ");

        $updateStmt->execute([
            'tenDot' => $tenDot,
            'nam' => $nam,
            'loai' => $loai,
            'thoiGianBatDau' => $thoiGianBatDau,
            'thoiGianKetThuc' => $thoiGianKetThuc,
            'nguoiQuanLy' => $nguoiQuanLy,
            'id' => $id
        ]);

        $successMessage = "Cập nhật thành công!";

        $stmt = $conn->prepare("SELECT ID,TenDot,Loai,Nam,NguoiMoDot,NguoiQuanLy,ThoiGianBatDau,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
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
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Chỉnh sửa đợt: <?= htmlspecialchars($dot['TenDot']) ?></h1>

                <div class="col-md-offset">
                    <?php if (!empty($successMessage)): ?>
                        <div id="notificationAlert" class="alert alert-success">
                            <?= $successMessage ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($notification)): ?>
                        <div id="notificationAlert" class="alert alert-danger">
                            <?= $notification ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="post" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Tên đợt</label>
                        <div class="col-sm-10">
                            <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> type="text" name="TenDot" class="form-control"
                                value="<?= htmlspecialchars($dot['TenDot']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Năm</label>
                        <div class="col-sm-10" >
                            <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> type="number" name="Nam" min="1000" max="9999" class="form-control"
                                value="<?= htmlspecialchars($dot['Nam']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Loại</label>
                        <div class="col-sm-10">
                            <select <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> id="Loai" name="Loai" class="form-control" required>
                                <option value="Cao đẳng" <?= $dot['Loai'] == 'Cao đẳng' ? 'selected' : '' ?>>Cao đẳng
                                </option>
                                <option value="Cao đẳng ngành" <?= $dot['Loai'] == 'Cao đẳng ngành' ? 'selected' : '' ?>>
                                    Cao đẳng ngành</option>
                            </select>
                        </div>
                    </div>
                        <div class="form-group">
                            <label  class="col-sm-2 control-label">Thời gian bắt đầu</label>
                            <div class="col-sm-10">
                                <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> class="form-control"
                                    value="<?= isset($dot['ThoiGianBatDau']) ? htmlspecialchars($dot['ThoiGianBatDau']) : '' ?>"
                                    id="ThoiGianBatDau" name="ThoiGianBatDau" type="date"
                                    required>
                            </div>
                        </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Thời gian kết thúc</label>
                        <div class="col-sm-10">
                            <?php
                            ?>
                            <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> class="form-control"
                                value="<?= isset($dot['ThoiGianKetThuc']) ? htmlspecialchars($dot['ThoiGianKetThuc']) : '' ?>"
                                id="ThoiGianKetThuc" name="ThoiGianKetThuc" type="date"
                                placeholder="Chọn thời gian kết thúc" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Người quản lý</label>
                        <div class="col-sm-10">
                            <select <?= $dot['TrangThai'] == 0 ? 'disabled' : '' ?> id="NguoiQuanLy" name="NguoiQuanLy" class="form-control" required>
                                <?php foreach ($canbokhoa as $cb): ?>
                                    <option value="<?= $cb['ID_TaiKhoan'] ?>" <?= $dot['NguoiQuanLy'] == $cb['ID_TaiKhoan'] ? 'selected' : '' ?>>
    <?= htmlspecialchars($cb['TenNguoiQuanLy'] ?? $cb['Ten']) ?>
</option>

                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success btn-lg">Lưu thay đổi</button>
                        <a href="/datn/admin/pages/chitietdot?id=<?= urlencode($id) ?>"
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
        document.querySelector('form').addEventListener('submit', function (e) {
            const batDau = document.getElementById('ThoiGianBatDau').value;
            const ketThuc = document.getElementById('ThoiGianKetThuc').value;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const ngayMai = new Date(today.getTime() + 24 * 60 * 60 * 1000);
            const bd = new Date(batDau);
            const kt = new Date(ketThuc);

            let errors = [];
            if (bd < ngayMai) {
                errors.push("Thời gian bắt đầu phải từ ngày mai trở đi!");
            }
            if (bd >= kt) {
                errors.push("Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc!");
            }
            const diff = (kt - bd) / (1000 * 60 * 60 * 24);
            if (diff < 28) {
                errors.push("Thời gian kết thúc phải cách thời gian bắt đầu ít nhất 4 tuần!");
            }
            if (errors.length > 0) {
                alert(errors.join('\n'));
                e.preventDefault();
            }
        });
        
        window.addEventListener('DOMContentLoaded', () => {
            const alertBox = document.getElementById('notificationAlert');
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.transition = 'opacity 0.5s ease';
                    alertBox.style.opacity = '0';
                    setTimeout(() => alertBox.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>

</html>