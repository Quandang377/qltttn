<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/datn/file/';
if (!is_dir($uploadDir))
    mkdir($uploadDir, 0777, true);
$maxSize = 40 * 1024 * 1024; // 40MB
$msg = "";
$idTaiKhoan = $_SESSION['user_id'] ?? $_SESSION['user']['ID_TaiKhoan'] ?? null;

$stmt = $conn->prepare("
    SELECT f.*, GROUP_CONCAT(dt.TenDot SEPARATOR ', ') AS dotthuctap
    FROM file f
    LEFT JOIN tainguyen_dot td ON f.ID = td.ID_File
    LEFT JOIN dotthuctap dt ON td.ID_Dot = dt.ID
    WHERE f.Loai='Tainguyen' AND f.TrangThai = 1
    GROUP BY f.ID
    ORDER BY f.ID DESC
");
$stmt->execute();
$tainguyen = $stmt->fetchAll(PDO::FETCH_ASSOC);
function respond($status, $message)
{
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        if (ob_get_length())
            ob_clean(); // Xóa mọi output
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ten = trim($_POST['ten'] ?? '');
    $dsDot = $_POST['ds_dot'] ?? [];
    $duongdan = $_POST['duongdan_old'] ?? '';
    $tenfile = $_POST['tenfile_old'] ?? '';

    // THÊM
    if ($action === 'add') {
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

        if ($ten && $duongdan) {
            $stmt = $conn->prepare("INSERT INTO file (TenFile, DIR, ID_SV, ID_GVHD, TrangThai, Loai, NgayNop, TenHienThi)
                                    VALUES (?, ?, NULL, NULL, 1, 'Tainguyen', NOW(), ?)");
            $stmt->execute([$tenfile, $duongdan, $ten]);
            $idFile = $conn->lastInsertId();

            if (!empty($dsDot)) {
                // Nếu chọn "Tất cả sinh viên" thì không insert vào tainguyen_dot
                if (in_array('0', $dsDot)) {
                    // không cần insert, tài nguyên sẽ áp dụng cho tất cả
                } else {
                    $stmt = $conn->prepare("INSERT INTO tainguyen_dot (ID_File, ID_Dot) VALUES (?, ?)");
                    foreach ($dsDot as $idDot) {
                        $stmt->execute([$idFile, $idDot]);
                    }
                }
            }

            respond('success', 'Thêm tài nguyên thành công!');
        } else {
            respond('error', 'Vui lòng nhập đủ thông tin và chọn file hoặc đường dẫn!');
        }
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');

            // Sau khi xử lý xong:
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã thêm tài nguyên thành công!'
            ]);
            exit;
        }
    }

    // SỬA
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);

        if (isset($_FILES['duongdan']) && $_FILES['duongdan']['error'] === UPLOAD_ERR_OK && $_FILES['duongdan']['size'] <= $maxSize) {
            $ext = strtolower(pathinfo($_FILES['duongdan']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid('file_') . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['duongdan']['tmp_name'], $targetPath)) {
                if ($duongdan && file_exists($duongdan) && strpos($duongdan, '/datn/file/') !== false)
                    unlink($duongdan);
                $duongdan = realpath($targetPath);
                $tenfile = $_FILES['duongdan']['name'];
            }
        } elseif (!empty($_POST['duongdan_link'])) {
            if ($duongdan && file_exists($duongdan) && strpos($duongdan, '/datn/file/') !== false)
                unlink($duongdan);
            $duongdan = trim($_POST['duongdan_link']);
            $tenfile = basename($duongdan);
        }

        if ($ten && $duongdan) {
            $stmt = $conn->prepare("UPDATE file SET TenFile=?, DIR=?, TenHienThi=? WHERE ID=?");
            $stmt->execute([$tenfile, $duongdan, $ten, $id]);

            $stmt = $conn->prepare("DELETE FROM tainguyen_dot WHERE ID_File=?");
            $stmt->execute([$id]);

            if (!empty($dsDot)) {
                $stmt = $conn->prepare("INSERT INTO tainguyen_dot (ID_File, ID_Dot) VALUES (?, ?)");
                foreach ($dsDot as $idDot) {
                    $stmt->execute([$id, $idDot]);
                }
            }

            respond('success', 'Cập nhật tài nguyên thành công!');
        } else {
            respond('error', 'Vui lòng nhập đủ thông tin!');
        }
    }

    // XÓA
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("SELECT DIR FROM file WHERE ID=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && file_exists($row['DIR']) && strpos($row['DIR'], '/datn/file/') !== false) {
            unlink($row['DIR']);
        }
        $stmt = $conn->prepare("UPDATE file SET TrangThai=0 WHERE ID=?");
        $stmt->execute([$id]);
        respond('success', 'Xóa tài nguyên thành công!');
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý tài nguyên thực tập</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
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
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
        ?>
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
                            <div class="form-group" style="margin:5px">
                                <button type="button" class="btn btn-info btn-md" data-toggle="modal"
                                    data-target="#modalChonDot">
                                    Chọn đợt
                                </button>
                                <span id="dot-da-chon" class="ml-2 text-primary font-weight-bold">Chưa chọn</span>

                            </div>
                            <div id="dot-container"></div>

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
                                <th>Áp dụng cho đợt</th>
                                <th>Ngày tải lên</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($tainguyen as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['TenHienThi']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['TenFile']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['dotthuctap']??"Tất cả") ?></td>
                                    <td><?= htmlspecialchars($row['NgayNop']) ?></td>

                                    <td>
                                        <?php

                                        $stmtDot = $conn->prepare("SELECT ID_Dot FROM tainguyen_dot WHERE ID_File = ?");
                                        $stmtDot->execute([$row['ID']]);
                                        $dotIDs = $stmtDot->fetchAll(PDO::FETCH_COLUMN);
                                        $row['DotIDs'] = $dotIDs;
                                        $row['DuongDan'] = $row['DIR'];
                                        ?>
                                        <button class="btn btn-xs btn-warning btn-edit"
                                            data-row='<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'>
                                            Sửa
                                        </button>

                                        <button class="btn btn-xs btn-danger"
                                            onclick="deleteTaiNguyen(<?= $row['ID'] ?>)">Xóa</button>

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
                                    <div class="form-group">
                                        <label>Các đợt thực tập áp dụng:</label>
                                        <select multiple class="form-control" name="ds_dot[]" id="edit-ds-dot" size="6">
                                            <?php
                                            $stmt = $conn->query("SELECT ID, TenDot FROM dotthuctap WHERE TrangThai >= 0 ORDER BY ID DESC");
                                            while ($dot = $stmt->fetch(PDO::FETCH_ASSOC)):
                                                ?>
                                                <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <small class="text-muted">Giữ Ctrl/Shift (hoặc Cmd trên Mac) để chọn nhiều đợt</small>
                                    </div>
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
        function syncDotInputs() {
            const selectedOptions = $('#selectDot option:selected');
            const dotContainer = $('#dot-container');
            dotContainer.empty();

            selectedOptions.each(function () {
                const dotId = $(this).val();
                dotContainer.append(`<input type="hidden" name="ds_dot[]" value="${dotId}">`);
            });
        }
        $(document).ready(function () {
            $('#selectDot').on('change', function () {
                const selected = $(this).val();
                if (selected.includes('0')) {
                    // Nếu chọn "Tất cả", bỏ chọn các đợt khác
                    $('#selectDot option').not('[value="0"]').prop('selected', false);
                } else {
                    // Nếu chọn đợt khác, bỏ chọn "Tất cả"
                    $('#selectDot option[value="0"]').prop('selected', false);
                }
            });
            $('#btnLuuDot').on('click', function () {
                const selectedOptions = $('#selectDot option:selected');
                const dotContainer = $('#dot-container');
                const selectedNames = [];

                dotContainer.empty(); // Xóa các input hidden cũ
                selectedOptions.each(function () {
                    const dotId = $(this).val();
                    const dotName = $(this).text();
                    selectedNames.push(dotName);
                    // Thêm input hidden để gửi qua form
                    dotContainer.append(`<input type="hidden" name="ds_dot[]" value="${dotId}">`);
                });

                // Hiển thị tên các đợt đã chọn bên cạnh nút
                if (selectedNames.length > 0) {
                    $('#dot-da-chon').text(selectedNames.join(', '));
                } else {
                    $('#dot-da-chon').text('Chưa chọn');
                }

                // Ẩn modal
                $('#modalChonDot').modal('hide');
            });

            $('#form-tainguyen').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('ajax', '1');

                // Debug để kiểm tra
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (res) {
                        if (res && res.status && res.message) {
                            Swal.fire({
                                icon: res.status === 'success' ? 'success' : 'error',
                                title: res.status === 'success' ? 'Thành công' : 'Lỗi',
                                text: res.message
                            }).then(() => {
                                if (res.status === 'success') {
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi không xác định',
                                text: 'Phản hồi không hợp lệ từ server!'
                            });
                            console.log(res);
                        }
                    }
                });
            });

            // AJAX sửa tài nguyên
            $('#editForm').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('ajax', '1');

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                $('#editModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: res.message
                            });
                        }
                    },
                    error: () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Lỗi cập nhật!'
                        });
                    }
                });
            });


            // Gán dữ liệu khi ấn "Sửa"
            $('.btn-edit').on('click', function () {
                const data = $(this).data('row');
                $('#edit-id').val(data.ID);
                $('#edit-ten').val(data.TenHienThi);
                $('#edit-duongdan-old').val(data.DuongDan);
                $('#edit-tenfile-old').val(data.TenFile);
                $('#edit-link').val(data.Link || '');

                $('#edit-ds-dot option').prop('selected', false);
                if (data.DotIDs && Array.isArray(data.DotIDs)) {
                    data.DotIDs.forEach(id => {
                        $('#edit-ds-dot option[value="' + id + '"]').prop('selected', true);
                    });
                }

                $('#editModal').modal('show');
            });
        });
        // AJAX xóa tài nguyên
        function deleteTaiNguyen(id) {
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: 'Tài nguyên sẽ bị xóa và không thể khôi phục!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', { action: 'delete', id: id, ajax: 1 }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: res.message
                            });
                        }
                    }).fail(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Lỗi khi xóa!'
                        });
                    });
                }
            });
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
    <!-- Modal Chọn Đợt -->
    <div class="modal fade" id="modalChonDot" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chọn các đợt thực tập áp dụng</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <select multiple class="form-control" id="selectDot" size="8">
                        <option value="0">Tất cả</option>
                        <?php
                        $stmt = $conn->query("SELECT ID, TenDot FROM dotthuctap WHERE TrangThai >= 0 ORDER BY ID DESC");
                        while ($dot = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Giữ Ctrl/Shift (hoặc Cmd trên Mac) để chọn nhiều đợt</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="btnLuuDot">Lưu lựa chọn</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>