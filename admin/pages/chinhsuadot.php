<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $conn->prepare("SELECT ID,TenDot,BacDaoTao,Nam,NguoiMoDot,NguoiQuanLy,ThoiGianBatDau,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();
$successMessage = "";
$notification = "";

$stmt = $conn->prepare("SELECT ID, TenDot, BacDaoTao, Nam, NguoiMoDot, NguoiQuanLy, ThoiGianBatDau, ThoiGianKetThuc, TrangThai 
                        FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();

$stmt = $conn->query("
    SELECT ID_TaiKhoan, Ten FROM canbokhoa WHERE TrangThai = 1
    UNION
    SELECT ID_TaiKhoan, Ten FROM admin WHERE TrangThai = 1
");
$nguoiQuanLyList = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDot = $_POST['TenDot'] ?? '';
    $nam = $_POST['Nam'] ?? '';
    $BacDaoTao = $_POST['BacDaoTao'] ?? '';
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
    if($dot['TrangThai']==1)
    {
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
    }
else {
        $updateStmt = $conn->prepare("
            UPDATE DOTTHUCTAP SET
                TenDot = :tenDot,
                Nam = :nam,
                BacDaoTao = :BacDaoTao,
                ThoiGianBatDau = :thoiGianBatDau,
                ThoiGianKetThuc = :thoiGianKetThuc,
                NguoiQuanLy = :nguoiQuanLy
            WHERE ID = :id
        ");

        $updateStmt->execute([
            'tenDot' => $tenDot,
            'nam' => $nam,
            'BacDaoTao' => $BacDaoTao,
            'thoiGianBatDau' => $thoiGianBatDau,
            'thoiGianKetThuc' => $thoiGianKetThuc,
            'nguoiQuanLy' => $nguoiQuanLy,
            'id' => $id
        ]);

        $successMessage = "Cập nhật thành công!";

        $stmt = $conn->prepare("SELECT ID,TenDot,BacDaoTao,Nam,NguoiMoDot,NguoiQuanLy,ThoiGianBatDau,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
        $stmt->execute(['id' => $id]);
        $dot = $stmt->fetch();
    }
} else {
        $updateStmt = $conn->prepare("
            UPDATE DOTTHUCTAP SET
                NguoiQuanLy = :nguoiQuanLy
            WHERE ID = :id
        ");

        $updateStmt->execute([
            'nguoiQuanLy' => $nguoiQuanLy,
            'id' => $id
        ]);

        $successMessage = "Cập nhật thành công!";

        $stmt = $conn->prepare("SELECT ID,TenDot,BacDaoTao,Nam,NguoiMoDot,NguoiQuanLy,ThoiGianBatDau,ThoiGianKetThuc,TrangThai FROM DOTTHUCTAP WHERE ID = :id");
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
    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .page-header {
            color: #2c3e50;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
            border: none;
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .form-section h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
            transform: translateY(-1px);
        }
        
        .btn {
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            color: white;
        }
        
        .table-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-section .panel-heading {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 20px 25px;
            margin: 0;
            font-weight: 600;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-section .panel-body {
            padding: 25px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            border: none;
            padding: 15px;
            font-weight: 600;
            color: #2c3e50;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            padding: 15px;
            border-color: #e9ecef;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        tr.selected {
            background: linear-gradient(45deg, #007bff, #0056b3) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            font-weight: 500;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .alert-danger {
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .alert-success {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .action-buttons {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            #page-wrapper {
                padding: 15px;
            }
            
            .main-card {
                padding: 20px;
            }
            
            .form-section {
                padding: 15px;
            }
        }
        
        /* Animation cho loading */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification.fade-out {
            animation: slideOutRight 0.3s ease-in;
        }
    </style>
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
                            <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> type="text" name="TenDot"
                                class="form-control" value="<?= htmlspecialchars($dot['TenDot']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Năm</label>
                        <div class="col-sm-10">
                            <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> type="number" name="Nam" min="1000"
                                max="9999" class="form-control" value="<?= htmlspecialchars($dot['Nam']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Bậc đào tạo</label>
                        <div class="col-sm-10">
                            <select <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> id="BacDaoTao" name="BacDaoTao"
                                class="form-control" required>
                                <option value="Cao đẳng" <?= $dot['BacDaoTao'] == 'Cao đẳng' ? 'selected' : '' ?>>Cao đẳng
                                </option>
                                <option value="Cao đẳng ngành" <?= $dot['BacDaoTao'] == 'Cao đẳng ngành' ? 'selected' : '' ?>>
                                    Cao đẳng ngành</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Thời gian bắt đầu</label>
                        <div class="col-sm-10">
                            <input <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?> class="form-control"
                                value="<?= isset($dot['ThoiGianBatDau']) ? htmlspecialchars($dot['ThoiGianBatDau']) : '' ?>"
                                id="ThoiGianBatDau" name="ThoiGianBatDau" type="date" required>
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
                            <select <?= $dot['TrangThai'] == 0 ? 'disabled' : '' ?> id="NguoiQuanLy" name="NguoiQuanLy"
                                class="form-control" required>
                                <?php foreach ($nguoiQuanLyList as $cb): ?>
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
    <?php

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
            if($dot['TrangThai']==1)
        {
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