<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'chuyen_gv') {
    $id_sv = (int) $_POST['mssv'];
    $gvhdMoi = (int) $_POST['gvhdMoi'];
    $idDot = (int) $_POST['id_dot'];

    if ($id_sv && $gvhdMoi && $idDot) {
        $stmt = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gvhdMoi WHERE ID_TaiKhoan = :id_sv AND ID_Dot = :id_dot");
        $stmt->execute(['gvhdMoi' => $gvhdMoi, 'id_sv' => $id_sv, 'id_dot' => $idDot]);

        if ($stmt->rowCount() > 0) {
            echo "Chuyển giáo viên hướng dẫn thành công.";
        } else {
            http_response_code(400);
            echo "Không thể chuyển giáo viên hướng dẫn. Vui lòng kiểm tra lại.";
        }
    } else {
        http_response_code(400);
        echo "Thiếu dữ liệu đầu vào.";
    }
    exit;
}
$stmt = $conn->prepare("SELECT ID, TenDot FROM DOTTHUCTAP WHERE TrangThai = 1 ORDER BY ID DESC");
$stmt->execute();
$dsDotThucTap = $stmt->fetchAll(PDO::FETCH_ASSOC);

$idDot = $_GET['id_dot'] ?? ($_GET['id'] ?? null);

if (!$idDot) {
    die("Không tìm thấy ID đợt thực tập.");
}

$stmt = $conn->prepare("
    SELECT 
        gv.ID_TaiKhoan,
        gv.Ten,
        COUNT(sv.ID_TaiKhoan) AS SoLuongSinhVien
    FROM GiaoVien gv
    LEFT JOIN SinhVien sv ON sv.ID_GVHD = gv.ID_TaiKhoan AND sv.ID_Dot = :id_dot
    WHERE gv.TrangThai = 1
    GROUP BY gv.ID_TaiKhoan, gv.Ten
    ORDER BY gv.Ten
");
$stmt->execute(['id_dot' => $idDot]);
$dsGiaoVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu chọn giáo viên, lấy danh sách sinh viên của giáo viên đó
$idGV = $_GET['id_gv'] ?? null;
$dsSinhVien = [];
if ($idGV) {
    $stmt = $conn->prepare("
        SELECT ID_TaiKhoan, Ten, MSSV, Lop
        FROM SinhVien
        WHERE ID_GVHD = :id_gv AND ID_Dot = :id_dot
    ");
    $stmt->execute(['id_gv' => $idGV, 'id_dot' => $idDot]);
    $dsSinhVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy lại toàn bộ danh sách giáo viên để chuyển đổi
    $stmt = $conn->prepare("
        SELECT ID_TaiKhoan, Ten
        FROM GiaoVien
        WHERE TrangThai = 1
        ORDER BY Ten
    ");
    $stmt->execute();
    $allGiaoVien = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $allGiaoVien = $dsGiaoVien;
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý phân công hướng dẫn</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-3">
                        <a href="admin/pages/phanconghuongdan?id=<?= $idDot ?>" class="btn btn-default"
                            style="margin-top:40px;">
                            <i class="fa fa-arrow-left"></i>
                        </a>
                    </div>
                    <div class="col-md-9">

                        <h1 class="page-header">Quản lý phân công hướng dẫn</h1>
                    </div>
                </div>
                <form method="get" class="form-inline mb-3">
                    <label for="id_dot">Chọn đợt thực tập:</label>
                    <select name="id_dot" id="id_dot" class="form-control" onchange="this.form.submit()"
                        style="margin-bottom: 10px;">
                        <?php foreach ($dsDotThucTap as $dot): ?>
                            <option value="<?= $dot['ID'] ?>" <?= $dot['ID'] == $idDot ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dot['TenDot']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Danh sách giáo viên và số lượng sinh viên đã phân công</b></div>
                    <div class="panel-body">
                        <table class="table table-bordered" id="tableGiaoVien">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên giáo viên</th>
                                    <th>Số lượng sinh viên</th>
                                    <th>Xem danh sách sinh viên</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dsGiaoVien as $i => $gv): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($gv['Ten']) ?></td>
                                        <td><?= $gv['SoLuongSinhVien'] ?></td>
                                        <td>
                                            <a href="admin/pages/quanlyphancong?id=<?= $idDot ?>&id_gv=<?= $gv['ID_TaiKhoan'] ?>"
                                                class="btn btn-info btn-sm">
                                                Xem sinh viên
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($idGV): ?>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <b>Danh sách sinh viên được phân công cho giáo viên:
                                <?= htmlspecialchars($dsGiaoVien[array_search($idGV, array_column($dsGiaoVien, 'ID_TaiKhoan'))]['Ten'] ?? '') ?>
                            </b>
                        </div>
                        <div class="panel-body">
                            <?php if (count($dsSinhVien) > 0): ?>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>MSSV</th>
                                            <th>Họ tên</th>
                                            <th>Lớp</th>
                                            <th>Chuyển GVHD</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dsSinhVien as $idx => $sv): ?>
                                            <tr>
                                                <td><?= $idx + 1 ?></td>
                                                <td><?= htmlspecialchars($sv['MSSV']) ?></td>
                                                <td><?= htmlspecialchars($sv['Ten']) ?></td>
                                                <td><?= htmlspecialchars($sv['Lop']) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-xs"
                                                        onclick="moModalChuyenGV(<?= htmlspecialchars(json_encode($sv['ID_TaiKhoan'])) ?>)">
                                                        Chuyển GVHD
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-warning">Chưa có sinh viên nào được phân công cho giáo viên này.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>
    <script>
        $(document).ready(function () {
            // Khởi tạo DataTables
            $('#tableGiaoVien').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                }
            });

            // Sự kiện mở modal và truyền MSSV
            window.moModalChuyenGV = function (mssv) {
                $('#mssvChuyen').val(mssv);
                $('#modalChuyenGV').modal('show');
            };

            // Sự kiện submit form chuyển GV
            $(document).on('submit', '#formChuyenGV', function (e) {
                e.preventDefault();
                const formData = $(this).serialize() + '&action=chuyen_gv';
                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: 'Chuyển giáo viên hướng dẫn thành công!',
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        console.error("Error:", xhr.responseText);
                        alert("Chuyển không thành công: " + xhr.responseText);
                    }
                });
            });
        });
    </script>
</body>
<!-- Modal chuyển GVHD -->
<div class="modal fade" id="modalChuyenGV" tabindex="-1" role="dialog" aria-labelledby="modalChuyenGVLabel">
    <div class="modal-dialog" role="document">
        <form method="post" id="formChuyenGV">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalChuyenGVLabel">Chuyển giáo viên hướng dẫn</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="mssv" id="mssvChuyen">
                    <div class="form-group">
                        <input type="hidden" name="id_dot" value="<?= $idDot ?>">
                        <label for="gvhdMoi">Chọn giáo viên mới:</label>
                        <select class="form-control" name="gvhdMoi" id="gvhdMoi" required>
                            <?php foreach ($allGiaoVien as $gv): ?>
                                <?php if ($gv['ID_TaiKhoan'] != $idGV): ?>
                                    <option value="<?= $gv['ID_TaiKhoan'] ?>"><?= htmlspecialchars($gv['Ten']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Xác nhận chuyển</button>
                </div>
            </div>
        </form>
    </div>
</div>

</html>