<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Thư mục lưu file upload
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/datn/file/';
if (!is_dir($uploadDir))
    mkdir($uploadDir, 0777, true);


$maxSize = 40 * 1024 * 1024; // 40MB

$msg = "";
$idTaiKhoan = $_SESSION['user_id'] ?? $_SESSION['user']['ID_TaiKhoan'] ?? null;

// Xử lý xóa file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT DIR FROM file WHERE ID=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && file_exists($row['DIR']) && strpos($row['DIR'], '/datn/file/') !== false) {
        unlink($row['DIR']);
    }
    $stmt = $conn->prepare("UPDATE file SET TrangThai=0 WHERE ID=?");
    $stmt->execute([$id]);
    $msg = "Xóa tài nguyên thành công!";
}

// Xử lý thêm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $ten = trim($_POST['ten']);
    $nguoidang = $idTaiKhoan;
    $duongdan = "";
    $tenfile = "";

    if (isset($_FILES['duongdan']) && $_FILES['duongdan']['error'] === UPLOAD_ERR_OK && $_FILES['duongdan']['size'] <= $maxSize) {
        $ext = strtolower(pathinfo($_FILES['duongdan']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('file_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['duongdan']['tmp_name'], $targetPath)) {
            $duongdan = realpath($targetPath);
            $tenfile = $_FILES['duongdan']['name'];
        }
    } elseif (!empty($_POST['duongdan_link'])) {
        $duongdan = trim($_POST['duongdan_link']);
        $tenfile = basename($duongdan);
    }

    if ($ten && $duongdan && $nguoidang) {
        $stmt = $conn->prepare("INSERT INTO file (TenFile, DIR, ID_SV, ID_GVHD, TrangThai, Loai, NgayNop, Ten) VALUES (?, ?, NULL, NULL, 1, 'Tainguyen', NOW(), ?)");
        $stmt->execute([$tenfile, $duongdan, $ten]);
        $msg = "Thêm tài nguyên thành công!";
    } else {
        $msg = "Vui lòng nhập đủ thông tin và chọn file hoặc nhập đường dẫn!";
    }
}

// Xử lý sửa file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $ten = trim($_POST['ten']);
    $duongdan = $_POST['duongdan_old'] ?? "";
    $tenfile = $_POST['tenfile_old'] ?? "";

    // Nếu upload file mới
    if (isset($_FILES['duongdan']) && $_FILES['duongdan']['error'] === UPLOAD_ERR_OK && $_FILES['duongdan']['size'] <= $maxSize) {
        $ext = strtolower(pathinfo($_FILES['duongdan']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('file_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['duongdan']['tmp_name'], $targetPath)) {
            // Xóa file cũ nếu là file upload
            if ($duongdan && file_exists($duongdan) && strpos($duongdan, '/datn/file/') !== false)
                unlink($duongdan);
            $duongdan = realpath($targetPath);
            $tenfile = $_FILES['duongdan']['name'];
        }
    } elseif (!empty($_POST['duongdan_link'])) {
        // Nếu nhập đường dẫn mới
        if ($duongdan && file_exists($duongdan) && strpos($duongdan, '/datn/file/') !== false)
            unlink($duongdan);
        $duongdan = trim($_POST['duongdan_link']);
        $tenfile = basename($duongdan);
    }

    if ($ten && $duongdan) {
        $stmt = $conn->prepare("UPDATE file SET TenFile=?, DIR=?, Ten=? WHERE ID=?");
        $stmt->execute([$tenfile, $duongdan, $ten, $id]);
        $msg = "Cập nhật tài nguyên thành công!";
    } else {
        $msg = "Vui lòng nhập đủ thông tin và chọn file hoặc nhập đường dẫn!";
    }
}

// Lấy danh sách tài nguyên
$stmt = $conn->prepare("SELECT * FROM file WHERE Loai='Tainguyen' and trangthai=1 ORDER BY ID DESC");
$stmt->execute();
$tainguyen = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý tài nguyên thực tập</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
    <style>
        .container,
        .container-fluid,
        #wrapper,
        #page-wrapper {
            max-width: 100%;
            overflow-x: hidden;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row"></div>
                <div class="container">
                    <h2 class="page-header">Quản lý tài nguyên thực tập</h2>
                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-success" id="notificationAlert"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                    <!-- Form thêm tài nguyên -->
                    <form method="post" class="form-inline" style="margin-bottom: 20px;" enctype="multipart/form-data"
                        id="form-tainguyen">
                        <div>
                            <div class="form-group">
                                <label class="radio-inline">
                                    <input type="radio" name="chon_kieu" value="file" checked> Tải lên file
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="chon_kieu" value="link"> Nhập đường dẫn
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <input type="text" name="ten" class="form-control" placeholder="Tên tài nguyên" required>
                        </div>

                        <div class="form-group" id="file-upload-group">
                            <input type="file" name="duongdan" class="form-control" id="input-file">
                        </div>
                        <div class="form-group" id="file-link-group" style="display:none;">
                            <input type="text" name="duongdan_link" class="form-control" id="input-link"
                                placeholder="Nhập đường dẫn tới file (Google Drive, ...)">
                        </div>
                        <button type="submit" class="btn btn-success">Thêm mới</button>

                    </form>
                    <!-- Danh sách tài nguyên -->
                    <table id="table-tainguyen" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tên tài nguyên</th>
                                <th>File</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tainguyen as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['Ten']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['TenFile']) ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-warning"
                                            onclick="editRow(<?= htmlspecialchars(json_encode($row)) ?>)">Sửa</button>
                                        <form method="post" style="display:inline;"
                                            onsubmit="return confirm('Xác nhận xóa?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['ID'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form method="post" id="editForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit-id">
                            <input type="hidden" name="duongdan_old" id="edit-duongdan-old">
                            <input type="hidden" name="tenfile_old" id="edit-tenfile-old">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">Sửa tài nguyên</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Tên tài nguyên</label>
                                        <input type="text" name="ten" id="edit-ten" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Chọn file mới (nếu muốn thay thế)</label>
                                        <input type="file" name="duongdan" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Hoặc nhập đường dẫn mới</label>
                                        <input type="text" name="duongdan_link" class="form-control" id="edit-link"
                                            placeholder="Nhập đường dẫn tới file (Google Drive, ...)">
                                    </div>
                                    <div id="file-link"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                                    <button type="submit" class="btn btn-primary">Lưu</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>
    <script>
        $('#table-tainguyen').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' }
        });

        $('form[enctype="multipart/form-data"]').on('submit', function (e) {
            var fileInput = $(this).find('input[type="file"]')[0];
            if (fileInput && fileInput.files.length > 0) {
                var file = fileInput.files[0];
                if (file.size > 40 * 1024 * 1024) { // 40MB
                    alert('Vui lòng chọn file nhỏ hơn 40MB!');
                    e.preventDefault();
                    return false;
                }
            }
        });
        $('input[name="chon_kieu"]').on('change', function () {
            if ($(this).val() === 'file') {
                $('#file-upload-group').show();
                $('#input-file').prop('required', true);
                $('#file-link-group').hide();
                $('#input-link').prop('required', false);
            } else {
                $('#file-upload-group').hide();
                $('#input-file').prop('required', false);
                $('#file-link-group').show();
                $('#input-link').prop('required', true);
            }
        });
        $('#input-file').on('change', function () {
            var file = this.files[0];
            if (file && file.size > 40 * 1024 * 1024) {
                alert('File lớn hơn 40MB. Vui lòng nhập đường dẫn tới file thay vì upload!');
                $('input[name="chon_kieu"][value="link"]').prop('checked', true).trigger('change');
                $(this).val('');
            }
        });
        function editRow(row) {
            $('#edit-id').val(row.ID);
            $('#edit-ten').val(row.Ten);
            $('#edit-duongdan-old').val(row.DIR);
            $('#edit-tenfile-old').val(row.TenFile);
            $('#edit-link').val('');
            $('#file-link').html(row.TenFile ? 'File hiện tại: <b>' + row.TenFile + '</b>' : '');
            $('#editModal').modal('show');
        }
        const alertBox = document.getElementById('notificationAlert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 2000);
        }
    </script>
</body>

</html>