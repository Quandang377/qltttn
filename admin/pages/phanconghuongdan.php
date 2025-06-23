<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Không tìm thấy ID đợt thực tập.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hoan_tat') {
    $stmt = $conn->prepare("UPDATE DOTTHUCTAP SET TrangThai = 3 WHERE ID = :id");
    $stmt->execute(['id' => $id]);

    // Lấy danh sách phân công
    $stmt = $conn->prepare("
        SELECT sv.MSSV, sv.Ten AS TenSV, sv.Lop, gv.Ten AS TenGV
        FROM SinhVien sv
        LEFT JOIN GiaoVien gv ON sv.ID_GVHD = gv.ID_TaiKhoan
        WHERE sv.ID_Dot = :id AND sv.ID_GVHD IS NOT NULL AND sv.ID_GVHD != ''
        ORDER BY gv.Ten, sv.Lop, sv.MSSV
    ");
    $stmt->execute(['id' => $id]);
    $dsPhanCong = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'OK', 'phancong' => $dsPhanCong]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM DOTTHUCTAP WHERE ID = :id");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();
$successMessage = "";
$notification = "";
if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GiaoVien'], $_POST['chon'])) {

    $gvId = $_POST['GiaoVien'];
    $sinhVienIds = $_POST['chon'];
    if (empty($sinhVienIds))
        $notification = "Hãy chọn sinh viên!";
    else {
        $update = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gvId WHERE ID_TaiKhoan IN (" . implode(",", array_map('intval', $sinhVienIds)) . ")");
        $update->execute(['gvId' => $gvId]);

        $successMessage = "Phân công thành công!";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview_auto' && isset($_POST['giaovien_thamgia'])) {
    $giaovienThamGia = array_map('intval', $_POST['giaovien_thamgia']);
    // Lấy danh sách giáo viên
    $placeholders = implode(',', array_fill(0, count($giaovienThamGia), '?'));
    $stmt = $conn->prepare("SELECT ID_TaiKhoan, Ten FROM GiaoVien WHERE ID_TaiKhoan IN ($placeholders)");
    $stmt->execute($giaovienThamGia);
    $giaoViens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy sinh viên chưa có GVHD, nhóm theo lớp
    $stmt = $conn->prepare("SELECT ID_TaiKhoan, Ten, MSSV, Lop FROM SinhVien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '') ORDER BY Lop ASC");
    $stmt->execute(['id' => $id]);
    $sinhViens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gom sinh viên theo lớp
    $svTheoLop = [];
    foreach ($sinhViens as $sv) {
        $lop = $sv['Lop'] ?? 'Khác';
        $svTheoLop[$lop][] = $sv;
    }
    ksort($svTheoLop, SORT_NATURAL | SORT_FLAG_CASE);
    $allSinhVien = [];

    // Gộp lại thành một mảng lớn, giữ thứ tự lớp
    foreach ($svTheoLop as $dsSv) {
        foreach ($dsSv as $sv) {
            $allSinhVien[] = $sv;
        }
    }

    // Chia lớp cho giáo viên: mỗi giáo viên nhận 1 lớp, hết thì quay lại
    $phanCong = [];
    $gvCount = count($giaoViens);
    foreach ($allSinhVien as $i => $sv) {
        $gvTen = $giaoViens[$i % $gvCount]['Ten'];
        $phanCong[$gvTen][] = [
            'MSSV' => $sv['MSSV'],
            'Ten' => $sv['Ten'],
            'Lop' => $sv['Lop']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'giaoviens' => $giaoViens,
        'phancong' => $phanCong
    ]);
    exit;
}
if (isset($_GET['auto']) && $_GET['auto'] == 1 && isset($_POST['giaovien_thamgia'])) {
    $giaovienThamGia = array_map('intval', $_POST['giaovien_thamgia']);
    // Lấy danh sách giáo viên (để lấy đúng ID_TaiKhoan)
    $placeholders = implode(',', array_fill(0, count($giaovienThamGia), '?'));
    $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM GiaoVien WHERE ID_TaiKhoan IN ($placeholders)");
    $stmt->execute($giaovienThamGia);
    $giaoViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Lấy sinh viên chưa có GVHD, nhóm theo lớp, sắp xếp lớp và tên
    $stmt = $conn->prepare("SELECT ID_TaiKhoan, Ten, MSSV, Lop FROM SinhVien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '') ORDER BY Lop ASC, Ten ASC");
    $stmt->execute(['id' => $id]);
    $sinhViens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gom sinh viên theo lớp
    $svTheoLop = [];
    foreach ($sinhViens as $sv) {
        $lop = $sv['Lop'] ?? 'Khác';
        $svTheoLop[$lop][] = $sv;
    }
    ksort($svTheoLop, SORT_NATURAL | SORT_FLAG_CASE);

    // Gộp lại thành một mảng lớn, giữ thứ tự lớp
    $allSinhVien = [];
    foreach ($svTheoLop as $dsSv) {
        foreach ($dsSv as $sv) {
            $allSinhVien[] = $sv;
        }
    }

    if (count($allSinhVien) > 0 && count($giaoViens) > 0) {
        $gvCount = count($giaoViens);
        foreach ($allSinhVien as $i => $sv) {
            $gvId = $giaoViens[$i % $gvCount];
            $update = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gvId WHERE ID_TaiKhoan = :svId");
            $update->execute(['gvId' => $gvId, 'svId' => $sv['ID_TaiKhoan']]);
        }
        $successMessage = "Phân công tự động theo lớp thành công!";
    } else {
        $notification = "Không đủ dữ liệu để phân công tự động!";
    }
    exit;
}
$sinhviens = $conn->prepare("SELECT ID_TaiKhoan,Ten,MSSV,ID_Dot,ID_GVHD,Lop,TrangThai FROM SinhVien WHERE ID_DOT = :id AND (ID_GVHD IS NULL or ID_GVHD='')");
$sinhviens->execute(['id' => $id]);

$giaoviens = $conn->query("SELECT ID_TaiKhoan,Ten FROM giaovien where TrangThai=1")->fetchAll();

if (isset($_GET['auto']) && $_GET['auto'] == 1 && isset($_POST['giaovien_thamgia'])) {
    $giaovienThamGia = $_POST['giaovien_thamgia'];
}
$stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
$stmt->execute(['id' => $id]);
$soSinhVienChuaPC = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = :id");
$stmt->execute(['id' => $id]);
$tongSinhVienDot = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Phân công hướng dẫn</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>

</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row mt-5">
                    <div class="col-lg-12">
                        <h1 class="page-header"><?= htmlspecialchars($dot['TenDot']) ?></h1>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-offset">
                        <?php if (!empty($successMessage)): ?>
                            <div id="notificationAlert" class="alert alert-success">
                                <?= $successMessage ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($notification)): ?>
                            <div id="notificationAlert" class="alert alert-success">
                                <?= $notification ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="post" id="FormPhanCong">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>Chọn Giáo Viên</label>
                                <select name="GiaoVien" class="form-control" required>
                                    <?php foreach ($giaoviens as $gv): ?>
                                        <option value="<?= $gv['ID_TaiKhoan'] ?>"><?= htmlspecialchars($gv['Ten']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row text-left ">
                            <a href="/datn/admin/pages/quanlyphancong?id=<?= urlencode($id) ?>"
                                class="btn btn-primary " <?= $dot['TrangThai'] == 3 ? 'disabled' : '' ?>>Quản lý</a>
                            <button type="button" class="btn btn-primary " id="btnAutoPhanCong"
                                <?= $dot['TrangThai'] == 3 ? 'disabled' : '' ?>>Phân công tự
                                động</button>
                            <button type="button" class="btn btn-success " id="btnHoanTat"
                                <?= $sinhviens->rowCount() > 0 || $dot['TrangThai'] == 3 || $tongSinhVienDot == 0 ? 'disabled' : '' ?>>
                                Hoàn tất phân công
                            </button>
                        </div>
                        <div class="row">
                            <h3>Danh sách sinh viên chưa được hướng dẫn</h3>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table" id="tableSinhVien">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>MSSV</th>
                                                    <th>Họ Tên</th>
                                                    <th>Lớp</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sinhviens as $sv): ?>
                                                    <tr onclick="toggleCheckbox(this)">
                                                        <td style="cursor: pointer;"><input type="checkbox" name="chon[]"
                                                                value="<?= $sv['ID_TaiKhoan'] ?>"></td>
                                                        <td style="cursor: pointer;"><?= htmlspecialchars($sv['MSSV']) ?>
                                                        </td>
                                                        <td style="cursor: pointer;"><?= htmlspecialchars($sv['Ten']) ?>
                                                        </td>
                                                        <td style="cursor: pointer;"><?= htmlspecialchars($sv['Lop']) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg mt-3" <?= $dot['TrangThai'] == 3 ? 'disabled' : '' ?>>Phân công</button>
                                    <a href="/datn/admin/pages/chitietdot?id=<?= urlencode($id) ?>"
                                        class="btn btn-default btn-lg">Thoát</a>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
            <?php
            require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
                ?>
            <div class="modal fade" id="modalChonGV" tabindex="-1" role="dialog" aria-labelledby="modalChonGVLabel">
                <div class="modal-dialog" role="document">
                    <form id="formChonGV">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="modalChonGVLabel">Chọn giáo viên tham gia phân công tự động
                                </h4>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="chonHetGV"> <b>Chọn hết</b>
                                        </label>
                                    </div>
                                    <?php foreach ($giaoviens as $gv): ?>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="giaovien_thamgia[]"
                                                    value="<?= $gv['ID_TaiKhoan'] ?>">
                                                <?= htmlspecialchars($gv['Ten']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Huỷ</button>
                                <button type="submit" class="btn btn-primary">Xác nhận</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
</body>

</html>

<script>
    var soSinhVienChuaPC = <?= (int) $soSinhVienChuaPC ?>;
    $(document).ready(function () {
        // Khởi tạo DataTable cho bảng Sinh Viên
        $('#tableSinhVien').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });

        // Mở modal chọn giáo viên cho auto phân công
        $('#btnAutoPhanCong').on('click', function () {
            if (soSinhVienChuaPC === 0) {
                Swal.fire('Không còn sinh viên cần phân công!', '', 'info');
                return;
            }
            $('#modalChonGV').modal('show');
        });
        $('#chonHetGV').on('change', function () {
            const checked = $(this).is(':checked');
            $('#modalChonGV input[name="giaovien_thamgia[]"]').prop('checked', checked);
        });

        // Xử lý form chọn giáo viên để auto phân công
        $('#formChonGV').on('submit', function (e) {
            e.preventDefault();
            const checkedGV = $('input[name="giaovien_thamgia[]"]:checked');
            if (checkedGV.length === 0) {
                Swal.fire('Chưa chọn giáo viên!', 'Bạn phải chọn ít nhất một giáo viên.', 'warning');
                return;
            }
            const gvIds = checkedGV.map(function () { return $(this).val(); }).get();

            // Gửi AJAX lấy preview
            $.post(window.location.href, { action: 'preview_auto', giaovien_thamgia: gvIds }, function (res) {
                if (res.status === 'OK') {
                    let html = '';
                    for (const gv in res.phancong) {
                        html += `<b>${gv}</b><ul>`;
                        res.phancong[gv].forEach(sv => {
                            html += `<li>${sv.Ten} (${sv.MSSV}) - ${sv.Lop}</li>`;
                        });
                        html += '</ul>';
                    }
                    Swal.fire({
                        title: 'Kiểm tra phân công tự động',
                        html: `<div style="text-align:left;max-height:400px;overflow:auto">
                                <b>Giáo viên được chọn:</b> ${res.giaoviens.map(gv => gv.Ten).join(', ')}<br><br>
                                <b>Danh sách phân công:</b><br>${html}
                            </div>`,
                        icon: 'info',
                        width: 800,
                        showCancelButton: true,
                        confirmButtonText: 'Xác nhận thực hiện',
                        cancelButtonText: 'Huỷ'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Thực hiện phân công tự động
                            $.post(window.location.href + '&auto=1', { giaovien_thamgia: gvIds }, function () {
                                location.reload();
                            });
                        }
                    });
                }
            }, 'json');
            $('#modalChonGV').modal('hide');
        });
        $('#btnHoanTat').on('click', function () {
            Swal.fire({
                title: 'Xác nhận hoàn tất?',
                text: 'Sau khi hoàn tất, bạn sẽ không thể phân công lại!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Huỷ'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(window.location.href, { action: 'hoan_tat' }, function () {
                        location.reload();
                    });
                }
            });
        });
        $('#FormPhanCong').on('submit', function (e) {
            e.preventDefault();

            const checkboxes = $('input[name="chon[]"]:checked');
            const selectGV = $('select[name="GiaoVien"]');
            const gvTen = selectGV.find('option:selected').text();

            if (checkboxes.length === 0) {
                Swal.fire({
                    title: 'Chưa chọn sinh viên!',
                    text: 'Bạn phải chọn ít nhất một sinh viên để phân công.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            let svInfo = "";
            checkboxes.each(function () {
                const row = $(this).closest("tr");
                const mssv = row.children().eq(1).text().trim();
                const ten = row.children().eq(2).text().trim();
                const lop = row.children().eq(3).text().trim();
                svInfo += `• ${ten} ${mssv} ${lop}\n`;
            });

            Swal.fire({
                title: 'Xác nhận phân công?',
                html: `<b>Giáo viên:</b> ${gvTen}<br><b>Sinh viên:</b><pre style="text-align:left;">${svInfo}</pre>`,
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Huỷ',
                confirmButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit();
                }
            });
        });

        // Tự động ẩn thông báo sau 2 giây
        const alertBox = document.getElementById('notificationAlert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 2000);
        }
    });

    // Hàm toggle checkbox khi bấm hàng
    function toggleCheckbox(row) {
        if (event.target.type === 'checkbox') return;
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = !checkbox.checked;
    }
</script>