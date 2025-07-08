<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = $_SESSION['user_id'];

// Lấy danh sách đợt thực tập
$stmt = $conn->prepare(query: "SELECT ID, TenDot FROM DotThucTap WHERE TrangThai >= 0 and NguoiQuanLy = ? ORDER BY ID DESC");
$stmt->execute([$ID_TaiKhoan]);
$dsDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $stmt = $conn->prepare("SELECT ks.ID, ks.TieuDe, ks.ThoiGianTao,ks.NguoiNhan,
        (SELECT COUNT(*) FROM PhanHoiKhaoSat WHERE ID_KhaoSat = ks.ID) AS SoLuongPhanHoi,
        ks.ID_Dot
        FROM KhaoSat ks
        WHERE ks.NguoiTao = ? and ks.TrangThai=1 $whereDot
        ORDER BY ks.ThoiGianTao DESC");
    $stmt->execute($params);
    $dsKhaoSatTao = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <table class="table" id="bangkhaosat">
        <thead>
            <tr>
                <th>#</th>
                <th>Tiêu đề</th>
                <th>Ngày tạo</th>
                <th>Đợt thực tập</th>
                <th>Người nhận</th>
                <th>Phản hồi</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dsKhaoSatTao as $index => $ks): ?>
                <tr>
                    <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= $index + 1 ?>
                    </td>
                    <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= htmlspecialchars($ks['TieuDe']) ?>
                    </td>
                    <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= date('d/m/Y', strtotime($ks['ThoiGianTao'])) ?>
                    </td>
                    <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
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
                    <td onclick="window.location='admin/pages/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
                        <?= $ks['NguoiNhan'] ?>
                    </td>
                    <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';" style="cursor: pointer;">
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
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_canbo.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>Tạo khảo sát</h1>
                </div>
                <div class="form-container">
                    <form id="formTaoKhaoSat" method="post" autocomplete="off">
                        <div class="row text-left">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label><strong>Chọn đợt thực tập</strong></label>
                                    <select id="id_dot" name="id_dot" class="form-control" style="width: 200px;"
                                        required>
                                        <option value="">-- Chọn đợt --</option>
                                        <?php foreach ($dsDot as $dot): ?>
                                            <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label><strong>Gửi đến</strong></label>
                                    <select id="to" name="to" class="form-control" style="width: 200px;" required>
                                        <option value="Sinh viên" selected>Sinh Viên</option>
                                        <option value="Giáo viên">Giáo Viên</option>
                                        <option value="Tất cả">Tất cả</option>
                                    </select>
                                </div>
                            </div>
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
                            <button type="submit" class="btn btn-success btn-lg">Gửi</button>
                        </div>
                    </form>
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
                                <h4>Danh sách các khảo sát đã tạo</h4>
                            </div>
                            <div class="panel-body">
                                <div id="bangKhaoSat"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div>
            <?php require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php" ?>
            <script>
                // Tạo khảo sát
                $('#formTaoKhaoSat').on('submit', function (e) {
                    e.preventDefault();
                    $.post('/datn/pages/canbo/khaosat', $(this).serialize() + '&action=tao', function (res) {
                        if (res.status === 'OK') {
                            Swal.fire('Tạo thành công!', '', 'success');
                            loadBangKhaoSat();
                            $('#formTaoKhaoSat')[0].reset();
                        } else {
                            Swal.fire('Lỗi', res.message || 'Không thể tạo khảo sát', 'error');
                        }
                    }, 'json');
                });

                // Lọc khảo sát theo đợt
                $('#dot_filter').on('change', function () {
                    loadBangKhaoSat();
                });

                // Hàm load bảng khảo sát
                function loadBangKhaoSat() {
                    $.get('/datn/pages/canbo/khaosat', {
                        ajax: 1,
                        dot_filter: $('#dot_filter').val()
                    }, function (html) {
                        $('#bangKhaoSat').html(html);
                        // Khởi tạo lại DataTable sau khi bảng đã được render
                        if ($('#bangkhaosat').length) {
                            $('#bangkhaosat').DataTable({
                                info: false,
                                destroy: true, // Thêm dòng này để tránh lỗi khi khởi tạo lại
                                language: {
                                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                                }
                            });
                        }
                    });
                }

                // Xóa khảo sát
                function xoaKhaoSat(id) {
                    Swal.fire({
                        title: 'Xác nhận xóa?',
                        icon: 'warning',
                        showCancelButton: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('/datn/pages/canbo/khaosat', { action: 'xoa', id: id }, function (res) {
                                if (res.status === 'OK') {
                                    loadBangKhaoSat();
                                } else {
                                    Swal.fire('Lỗi', res.message || 'Không thể xóa', 'error');
                                }
                            }, 'json');
                        }
                    });
                }

                // Khi trang load
                $(function () {
                    loadBangKhaoSat();
                });

                document.addEventListener("DOMContentLoaded", function () {
                    const danhSachCauHoi = document.getElementById("danhSachCauHoi");
                    const btnThem = document.getElementById("btnThemCauHoi");

                    btnThem.addEventListener("click", function () {
                        const cauHoiItem = danhSachCauHoi.querySelector(".cau-hoi-item");
                        const html = cauHoiItem.outerHTML;
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        const newItem = temp.firstElementChild;
                        // Reset giá trị các trường
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

                    // Hiện ô nhập đáp án nếu chọn trắc nghiệm
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
            </script>
        </div>
</body>