<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_login.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT VaiTro FROM TaiKhoan WHERE ID_TaiKhoan = ?");
$stmt->execute([$ID_TaiKhoan]);
$vaiTro = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT ks.*, 
    COALESCE(gv.Ten, sv.Ten,cb.Ten, tk.TaiKhoan) AS TenNguoiTao
    FROM KhaoSat ks
    JOIN TaiKhoan tk ON ks.NguoiTao = tk.ID_TaiKhoan
    LEFT JOIN GiaoVien gv ON gv.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN CanBoKhoa cb ON cb.ID_TaiKhoan = tk.ID_TaiKhoan
    LEFT JOIN SinhVien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
    WHERE ks.TrangThai = 1
    AND (
        ks.NguoiNhan IN ('Tất cả', ?) -- Vai trò
        OR (
            ks.NguoiNhan = 'Sinh viên thuộc hướng dẫn'
            AND EXISTS (
                SELECT 1
                FROM SinhVien sv2
                WHERE sv2.ID_TaiKhoan = ?
                AND sv2.ID_GVHD = ks.NguoiTao
            )
        )
    )
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat 
        FROM PhanHoiKhaoSat 
        WHERE ID_TaiKhoan = ?
    )
    ORDER BY ks.ThoiGianTao DESC
");

$stmt->execute([$vaiTro, $ID_TaiKhoan, $ID_TaiKhoan]);
$dsKhaoSat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dsID = array_column($dsKhaoSat, 'ID');
$dsCauHoiTheoKhaoSat = [];
if (!empty($dsID)) {
    $placeholders = implode(',', array_fill(0, count($dsID), '?'));
    $sqlCauHoi = "SELECT * FROM CauHoiKhaoSat WHERE ID_KhaoSat IN ($placeholders)";
    $stmtCauHoi = $conn->prepare($sqlCauHoi);
    $stmtCauHoi->execute($dsID);
    $tatCaCauHoi = $stmtCauHoi->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tatCaCauHoi as $ch) {
        $dsCauHoiTheoKhaoSat[$ch['ID_KhaoSat']][] = $ch;
    }
}
// 3. Lấy các đợt thực tập mà GVHD sinh viên
$stmt2 = $conn->prepare("
    SELECT DISTINCT dt.ID, dt.TenDot
    FROM dot_giaovien dg
    JOIN dotthuctap dt ON dg.ID_Dot = dt.ID
    WHERE dg.ID_GVHD = ?
    ORDER BY dt.ID DESC
");
$stmt2->execute([$ID_TaiKhoan]);
$dsDot = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// AJAX: Tạo khảo sát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tao') {
    $tieude = trim($_POST['tieude'] ?? '');
    $mota = trim($_POST['mota'] ?? '');
    $nguoiNhan = $_POST['to'] ?? '';
    $cauHoiList = $_POST['cauhoi'] ?? [];
    $loaiList = $_POST['loaicauhoi'] ?? [];
    $dapanList = $_POST['dapan'] ?? [];
    $nguoiTao = $ID_TaiKhoan;
    $idDot = $_POST['id_dot'] ?? null;

    try {
        $conn->beginTransaction();

        // 1. Tạo khảo sát trước
        $stmt = $conn->prepare("INSERT INTO KhaoSat (TieuDe, MoTa, NguoiNhan, NguoiTao, ThoiGianTao, TrangThai, ID_Dot) 
            VALUES (?, ?, ?, ?, NOW(), 1, ?)");
        $stmt->execute([$tieude, $mota, $nguoiNhan, $nguoiTao, $idDot]);
        $idKhaoSat = $conn->lastInsertId();

        // 2. Thêm câu hỏi (có loại và đáp án)
        $stmtCauHoi = $conn->prepare("INSERT INTO CauHoiKhaoSat (ID_KhaoSat, NoiDung, Loai, DapAn, TrangThai) VALUES (?, ?, ?, ?, 1)");
        foreach ($cauHoiList as $i => $cauhoi) {
            $noiDung = trim($cauhoi);
            $loai = $loaiList[$i] ?? 'text';
            $dapan = ($loai === 'choice') ? trim($dapanList[$i] ?? '') : null;
            // Xử lý đáp án: bỏ khoảng trắng và dấu ; ở cuối

            if ($loai === 'choice' || $loai === 'multiple') {
                $dapan = trim($dapanList[$i] ?? '');
                $dapan = preg_replace('/\s*;\s*$/', '', $dapan); // Xóa dấu ; và khoảng trắng cuối
            } else {
                $dapan = null;
            }
            if ($noiDung !== '') {
                $stmtCauHoi->execute([$idKhaoSat, $noiDung, $loai, $dapan]);
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'OK']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Xóa khảo sát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'xoa') {
    $idKhaoSat = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("UPDATE KhaoSat SET TrangThai = 0 WHERE ID = ?");
    $stmt->execute([$idKhaoSat]);
    echo json_encode(['status' => 'OK']);
    exit;
}

// AJAX: Lấy danh sách khảo sát
if (isset($_GET['ajax'])) {
    $whereDot = "";
    $params = [$ID_TaiKhoan];
    if (!empty($_GET['dot_filter'])) {
        $whereDot = " AND ks.ID_Dot = ? ";
        $params[] = $_GET['dot_filter'];
    }
    $stmt = $conn->prepare("SELECT ks.ID, ks.TieuDe, ks.ThoiGianTao,
        (SELECT COUNT(*) FROM PhanHoiKhaoSat WHERE ID_KhaoSat = ks.ID) AS SoLuongPhanHoi,
        ks.ID_Dot
        FROM KhaoSat ks
        WHERE ks.NguoiTao = ? and ks.TrangThai=1 $whereDot
        ORDER BY ks.ThoiGianTao DESC");
    $stmt->execute($params);
    $dsKhaoSatTao = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table class="table" id="quanlykhaosat">
        <thead>
            <tr>
                <th>#</th>
                <th>Tiêu đề</th>
                <th>Ngày tạo</th>
                <th>Đợt thực tập</th>
                <th>Phản hồi</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dsKhaoSatTao as $index => $ks): ?>
                <tr>
                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= $index + 1 ?>
                    </td>
                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= htmlspecialchars($ks['TieuDe']) ?>
                    </td>
                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= date('d/m/Y', strtotime($ks['ThoiGianTao'])) ?>
                    </td>
                    <td>
                        <?php
                        $tenDot = '';
                        foreach ($dsDot as $dot) {
                            if ($dot['ID'] == $ks['ID_Dot']) {
                                $tenDot = $dot['TenDot'];
                                break;
                            }
                        }
                        echo htmlspecialchars($tenDot);
                        ?>
                    </td>
                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= $ks['SoLuongPhanHoi'] ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="xoaKhaoSat(<?= $ks['ID'] ?>)">Xoá</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($dsKhaoSatTao)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">Chưa có khảo sát nào được tạo.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tạo khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>

<body>
    <div id="wrapper">
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Tạo khảo sát
                    </h1>
                </div>
                <?php
                if (
                    $_SERVER['REQUEST_METHOD'] === 'POST'
                    && isset($_POST['id_khaosat'], $_POST['id_cauhoi'], $_POST['traloi'])
                ) {

                    $idKhaoSat = $_POST['id_khaosat'];
                    $idTaiKhoan = $ID_TaiKhoan;
                    $dsIDCauHoi = $_POST['id_cauhoi'];
                    $dsTraLoi = $_POST['traloi'];

                    try {
                        $conn->beginTransaction();

                        $stmtPhanHoi = $conn->prepare("
                                INSERT INTO PhanHoiKhaoSat (ID_KhaoSat, ID_TaiKhoan, ThoiGianTraLoi, TrangThai)
                                VALUES (?, ?, NOW(), 1)
                            ");
                        $stmtPhanHoi->execute([$idKhaoSat, $idTaiKhoan]);

                        $idPhanHoi = $conn->lastInsertId();

                        $stmtTraLoi = $conn->prepare("
                                INSERT INTO CauTraLoi (ID_PhanHoi, ID_CauHoi, TraLoi, TrangThai)
                                VALUES (?, ?, ?, 1)
                            ");

                        foreach ($dsIDCauHoi as $i => $idCauHoi) {
                            $traLoi = trim($dsTraLoi[$i]);
                            if ($traLoi !== '') {
                                $stmtTraLoi->execute([$idPhanHoi, $idCauHoi, $traLoi]);
                            }
                        }

                        $conn->commit();
                        $_SESSION['success'] = "Phản hồi khảo sát thành công!";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $_SESSION['error'] = "Đã xảy ra lỗi khi phản hồi: " . $e->getMessage();
                    }

                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
                $stmt = $conn->prepare("SELECT ks.ID, ks.TieuDe, ks.ThoiGianTao,
                    (SELECT COUNT(*) FROM PhanHoiKhaoSat WHERE ID_KhaoSat = ks.ID ) AS SoLuongPhanHoi
                    FROM KhaoSat ks
                    WHERE ks.NguoiTao = ? and ks.TrangThai=1
                    ORDER BY ks.ThoiGianTao DESC");
                $stmt->execute([$ID_TaiKhoan]);
                $dsKhaoSatTao = $stmt->fetchAll(PDO::FETCH_ASSOC);

                ?>
                <div class="form-container">
                    <form id="formKhaoSat" method="post">
                        <div class="form-group">
                            <label><strong>Chọn đợt thực tập</strong></label>
                            <select id="id_dot" name="id_dot" class="form-control" style="width: 250px;" required>
                                <option value="">-- Chọn đợt --</option>
                                <?php foreach ($dsDot as $dot): ?>
                                    <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Gửi đến</strong></label>
                            <select id="to" name="to" class="form-control" style="width: 200px;" required>
                                <option value="Sinh viên thuộc hướng dẫn" selected>Sinh viên thuộc hướng dẫn
                                </option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Tiêu đề</label>
                                    <input class="form-control" id="tieude" name="tieude" type="text" required
                                        placeholder="Nhập tiêu đề cho khảo sát">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Mô tả</label>
                                    <input class="form-control" id="mota" name="mota" type="text" required
                                        placeholder="Nhập mô tả">
                                </div>
                            </div>
                        </div>
                        <div id="danhSachCauHoi">
                            <div class="form-group cau-hoi-item">
                                <label>Câu hỏi</label>
                                <div class="row" style="margin-bottom: 5px;">
                                    <div class="col-md-5">
                                        <input type="text" name="cauhoi[]" class="form-control" required
                                            placeholder="Nhập nội dung câu hỏi">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="loaicauhoi[]" class="form-control">
                                            <option value="text">Tự luận</option>
                                            <option value="choice">Chọn một</option>
                                            <option value="multiple">Chọn nhiều</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="dapan[]" class="form-control nhap-dapan"
                                            style="display:none;"
                                            placeholder="Nhập các câu trả lời, cách nhau bởi dấu ;">
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-danger btn-remove" type="button" style="width:100%;">
                                            <i class="glyphicon glyphicon-remove"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-right">
                            <button type="button" class="btn btn-primary" id="btnThemCauHoi">Thêm câu hỏi</button>
                        </div>
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success btn-lg" name="action"
                                value="guikhaosat">Gửi</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="containerKhaoSat" class="mt-3">
                <div id="listKhaoSat" class="row">
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Danh sách khảo sát cần phản hồi</h4>
                            </div>
                            <table class="table" id="bangkhaosat">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tiêu đề</th>
                                        <th>Ngày tạo</th>
                                        <th>Phản hồi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($dsKhaoSat)):
                                        foreach ($dsKhaoSat as $ks): ?>
                                            <tr>
                                                <td><?= $ks['ID'] ?></td>
                                                <td><?= htmlspecialchars($ks['TieuDe']) ?></td>
                                                <td><?= $ks['ThoiGianTao'] ?></td>
                                                <td>
                                                    <button class="btn btn-primary" data-toggle="modal"
                                                        data-target="#modalPhanHoi<?= $ks['ID'] ?>">Phản hồi</button>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Chưa có khảo sát nào
                                                cần phản hồi.</td>
                                        </tr>

                                    <?php endif; ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
                <?php foreach ($dsKhaoSat as $ks): ?>
                    <div class="modal fade" id="modalPhanHoi<?= $ks['ID'] ?>" tabindex="-1" role="dialog"
                        data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog">
                            <form method="post">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title"><?= htmlspecialchars($ks['TieuDe']) ?></h4>
                                        <p class="text-muted"><?= htmlspecialchars($ks['MoTa']) ?></p>
                                        <input type="hidden" name="id_khaosat" value="<?= $ks['ID'] ?>">
                                    </div>
                                    <div class="modal-body">
                                        <?php
                                        $cauHoi = $dsCauHoiTheoKhaoSat[$ks['ID']] ?? [];
                                        if (!empty($cauHoi)):
                                            foreach ($cauHoi as $index => $ch): ?>
                                                <div class="form-group">
                                                    <label>Câu <?= $index + 1 ?>:
                                                        <?= htmlspecialchars($ch['NoiDung']) ?></label>
                                                    <input type="hidden" name="id_cauhoi[]" value="<?= $ch['ID'] ?>">
                                                    <input type="text" class="form-control" name="traloi[]" required>
                                                </div>
                                            <?php endforeach;
                                        else: ?>
                                            <p class="text-danger">Không có câu hỏi nào cho khảo sát này.</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success" name="action" value="phanhoi">Gửi
                                            phản hồi</button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="containerKhaoSat" class="mt-3">
                <div id="listKhaoSat" class="row">
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <form class="form-inline" style="margin-bottom: 15px;">
                            <label for="dot_filter">Lọc theo đợt: </label>
                            <select name="dot_filter" id="dot_filter" class="form-control">
                                <option value="">-- Tất cả --</option>
                                <?php foreach ($dsDot as $dot): ?>
                                    <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Các khảo sát đã tạo</h4>
                            </div>
                            <div class="panel-body">
                                <div id="quanlykhaosat"></div>
                            </div>
                            <!-- /.panel-body -->
                        </div>
                        <!-- /.panel -->
                    </div>
                    <!-- /.col-lg-6 -->
                </div>
            </div>
        </div>
        </div>
        <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
            ?>
        <script>
            let clickedButton = null;

            // Ghi lại nút được nhấn (dùng để xác định hành động)
            document.querySelectorAll("button[type='submit']").forEach(button => {
                button.addEventListener("click", function () {
                    clickedButton = this;
                });
            });

            // Ngăn submit toàn cục và xử lý xác nhận gửi
            document.querySelectorAll("form").forEach(form => {
                form.addEventListener("submit", async function (e) {
                    e.preventDefault();

                    const btn = clickedButton || e.submitter;
                    if (!btn) return;

                    const action = btn.value;

                    if (action === "guikhaosat") {
                        const result = await Swal.fire({
                            title: "Xác nhận gửi khảo sát?",
                            icon: "question",
                            showCancelButton: true,
                            confirmButtonText: "Gửi khảo sát",
                            cancelButtonText: "Huỷ",
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#d33"
                        });

                        if (result.isConfirmed) {
                            form.submit(); // chỉ submit khi đã xác nhận
                        }
                        // nếu không xác nhận thì dừng lại ở đây, không làm gì cả
                    } else if (action === "phanhoi") {
                        const result = await Swal.fire({
                            title: "Xác nhận gửi phản hồi?",
                            icon: "question",
                            showCancelButton: true,
                            confirmButtonText: "Gửi",
                            cancelButtonText: "Huỷ",
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#d33"
                        });

                        if (result.isConfirmed) {
                            form.submit();
                        }
                    } else {
                        // Với các action khác thì cứ submit
                        form.submit();
                    }
                });
            });

            // Xử lý tạo khảo sát qua Ajax
            $('#formKhaoSat').on('submit', function (e) {
                e.preventDefault();
                $.post('/datn/pages/giaovien/khaosat', $(this).serialize() + '&action=tao', function (res) {
                    if (res.status === 'OK') {
                        Swal.fire('Tạo thành công!', '', 'success');
                        loadBangKhaoSat();
                        $('#formTaoKhaoSat')[0].reset();
                    } else {
                        Swal.fire('Lỗi', res.message || 'Không thể tạo khảo sát', 'error');
                    }
                }, 'json');
            });

            // Load bảng khảo sát theo đợt
            $('#dot_filter').on('change', function () {
                loadBangKhaoSat();
            });

            function loadBangKhaoSat() {
                $.get('/datn/pages/giaovien/khaosat', {
                    ajax: 1,
                    dot_filter: $('#dot_filter').val()
                }, function (html) {
                    $('#quanlykhaosat').html(html);
                    if ($('#quanlykhaosat table').length) {
                        $('#quanlykhaosat table').DataTable({
                            info: false,
                            destroy: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                            }
                        });
                    }
                });
            }

            // Xoá khảo sát
            function xoaKhaoSat(id) {
                Swal.fire({
                    title: 'Xác nhận xóa?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Xóa',
                    cancelButtonText: 'Huỷ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('/datn/pages/giaovien/khaosat', { action: 'xoa', id: id }, function (res) {
                            if (res.status === 'OK') {
                                loadBangKhaoSat();
                            } else {
                                Swal.fire('Lỗi', res.message || 'Không thể xóa', 'error');
                            }
                        }, 'json');
                    }
                });
            }

            // Xử lý thêm câu hỏi trong khảo sát
            document.addEventListener("DOMContentLoaded", function () {
                const danhSachCauHoi = document.getElementById("danhSachCauHoi");
                const btnThem = document.getElementById("btnThemCauHoi");

                btnThem.addEventListener("click", function () {
                    const cauHoiItem = danhSachCauHoi.querySelector(".cau-hoi-item");
                    const html = cauHoiItem.outerHTML;
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItem = temp.firstElementChild;
                    newItem.querySelector("input[name='cauhoi[]']").value = "";
                    newItem.querySelector("select[name='loaicauhoi[]']").value = "text";
                    newItem.querySelector("input[name='dapan[]']").style.display = "none";
                    newItem.querySelector("input[name='dapan[]']").value = "";
                    newItem.querySelector("input[name='dapan[]']").required = false;
                    danhSachCauHoi.appendChild(newItem);
                    capNhatTrangThaiNutXoa();
                });

                danhSachCauHoi.addEventListener("click", function (e) {
                    if (e.target.closest(".btn-remove")) {
                        const items = danhSachCauHoi.querySelectorAll(".cau-hoi-item");
                        if (items.length > 1) {
                            e.target.closest(".cau-hoi-item").remove();
                            capNhatTrangThaiNutXoa();
                        }
                    }
                });

                danhSachCauHoi.addEventListener('change', function (e) {
                    if (e.target.name === 'loaicauhoi[]') {
                        const $item = e.target.closest('.cau-hoi-item');
                        const dapAnInput = $item.querySelector("input[name='dapan[]']");
                        if (e.target.value === 'choice' || e.target.value === 'multiple') {
                            dapAnInput.style.display = '';
                            dapAnInput.required = true;
                        } else {
                            dapAnInput.style.display = 'none';
                            dapAnInput.required = false;
                        }
                    }
                });

                function capNhatTrangThaiNutXoa() {
                    const items = danhSachCauHoi.querySelectorAll(".cau-hoi-item");
                    items.forEach((item, index) => {
                        const btn = item.querySelector(".btn-remove");
                        btn.disabled = (items.length === 1);
                    });
                }

                capNhatTrangThaiNutXoa();
            });

            // Modal phản hồi khảo sát
            $(document).ready(function () {
                $('.btnPhanHoi').click(function () {
                    const id = $(this).data('id');
                    const ten = $(this).data('ten');
                    alert("Mở modal phản hồi khảo sát ID " + id + " - " + ten);
                });
            });

            // Ẩn alert thành công sau 2 giây
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