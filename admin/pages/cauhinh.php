<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['config'] as $ten => $giatri) {
        $stmt = $conn->prepare("UPDATE cauhinh SET GiaTri = ? WHERE Ten = ?");
        $stmt->execute([$giatri, $ten]);
    }
    $_SESSION['success'] = "Cập nhật cấu hình thành công!";
    header("Location: /datn/admin/pages/cauhinh");
    exit;
}

// Lấy danh sách cấu hình
$stmt = $conn->query("SELECT * FROM cauhinh ORDER BY ID");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['config'] as $ten => $giatri) {
        // Nếu là loại file và có upload
        if (isset($_FILES['config_file']['name'][$ten]) && $_FILES['config_file']['error'][$ten] === UPLOAD_ERR_OK) {
            $file = $_FILES['config_file'];
            $filename = basename($file['name'][$ten]);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed)) {
                $uploadDir = '/datn/uploads/Images/';
                $targetPath = $uploadDir . uniqid('logo_') . '.' . $ext;
                $savePath = $_SERVER['DOCUMENT_ROOT'] . $targetPath;

                if (!file_exists(dirname($savePath))) {
                    mkdir(dirname($savePath), 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'][$ten], $savePath)) {
                    $giatri = $targetPath; // Gán đường dẫn ảnh mới
                }
            }
        }

        // Cập nhật vào CSDL
        $stmt = $conn->prepare("UPDATE cauhinh SET GiaTri = ? WHERE Ten = ?");
        $stmt->execute([$giatri, $ten]);
    }

    $_SESSION['success'] = "Cập nhật cấu hình thành công!";
    header("Location: /datn/admin/pages/cauhinh");
    exit;
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cấu hình trang web</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>

<body>
    <div id="wrapper">
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php";
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">

                    <h1>
                        Cấu Hình Trang Web
                    </h1>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success" id="noti"><?= $_SESSION['success'];
                        unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <?php foreach ($configs as $cfg): ?>
                            <div class="form-group">
                                <label><strong><?= htmlspecialchars($cfg['MoTa'] ?: $cfg['Ten']) ?></strong></label>

                                <?php if ($cfg['Loai'] === 'textarea'): ?>
                                    <textarea name="config[<?= $cfg['Ten'] ?>]" class="form-control"
                                        rows="4"><?= htmlspecialchars($cfg['GiaTri']) ?></textarea>

                                <?php elseif ($cfg['Loai'] === 'file'): ?>
                                    <input type="file" name="config_file[<?= $cfg['Ten'] ?>]" class="form-control mb-2" accept="image/*">

                                    
                                    <input type="text" name="config[<?= $cfg['Ten'] ?>]" class="form-control mb-2"
                                        id="config_<?= $cfg['Ten'] ?>" value="<?= htmlspecialchars($cfg['GiaTri']) ?>"
                                        placeholder="Đường dẫn ảnh">

                                    <img src="<?= htmlspecialchars($cfg['GiaTri']) ?>" id="preview_<?= $cfg['Ten'] ?>"
                                        style="max-height:40px;margin-top:5px;<?= empty($cfg['GiaTri']) ? 'display:none;' : '' ?>">

                                <?php else: ?>
                                    <input type="<?= htmlspecialchars($cfg['Loai']) ?>" name="config[<?= $cfg['Ten'] ?>]"
                                        class="form-control" value="<?= htmlspecialchars($cfg['GiaTri']) ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php

    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>
    <script>
        document.querySelectorAll('.upload-config-file').forEach(input => {
            input.addEventListener('change', function () {
                const targetId = this.getAttribute('data-target');
                const formData = new FormData();
                formData.append('file', this.files[0]);

                fetch('/datn/admin/pages/ajax_upload_logo', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Cập nhật input đường dẫn
                            document.getElementById(targetId).value = data.path;

                            // Cập nhật ảnh xem trước
                            const previewImg = document.getElementById('preview_' + targetId.replace('config_', ''));
                            if (previewImg) {
                                previewImg.src = data.path;
                                previewImg.style.display = 'block';
                            }

                            Swal.fire('Thành công!', 'Đã tải ảnh lên.', 'success');
                        } else {
                            Swal.fire('Lỗi', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Lỗi', 'Không thể tải lên ảnh.', 'error'));
            });
        });
        window.addEventListener('DOMContentLoaded', () => {
            const alertBox = document.getElementById('noti');
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.transition = 'opacity 0.5s ease';
                    alertBox.style.opacity = '0';
                    setTimeout(() => alertBox.remove(), 500);
                }, 2000);
            }
        });
    </script>
</body>

</html>