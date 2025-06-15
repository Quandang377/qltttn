<<<<<<< HEAD
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = "6";
$stmt = $conn->prepare("SELECT VaiTro FROM TaiKhoan WHERE ID_TaiKhoan = ?");
$stmt->execute([$ID_TaiKhoan]);
$vaiTro = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT ks.*
    FROM KhaoSat ks
    WHERE ks.TrangThai = 1
    AND ks.NguoiNhan IN (?, ?)  -- truyền vào 'Tất cả' và vai trò
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat 
        FROM PhanHoiKhaoSat 
        WHERE ID_TaiKhoan = ?
    )
    ORDER BY ks.ThoiGianTao DESC
");
$stmt->execute(['Tất cả', $vaiTro, $ID_TaiKhoan]);

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_khaosat_id'])) {
    $idKhaoSat = $_POST['xoa_khaosat_id'];

    $stmt = $conn->prepare("UPDATE KhaoSat SET TrangThai = 0 WHERE ID = ?");
    $stmt->execute([$idKhaoSat]);

    $_SESSION['success'] = "Xoá khảo sát thành công.";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

=======
<!DOCTYPE html>
<html lang="vi">
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
<head>
    <meta charset="UTF-8">
    <title>Tạo khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<<<<<<< HEAD

<body>
    <div id="wrapper">
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
        ?>
=======
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
    ?>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Tạo khảo sát
                    </h1>
<<<<<<< HEAD
                </div>
                <?php

                if (
                    $_SERVER['REQUEST_METHOD'] === 'POST'
                    && isset($_POST['id_khaosat'], $_POST['id_cauhoi'], $_POST['traloi'])
                ) {

                    $idKhaoSat = $_POST['id_khaosat'];
                    $idTaiKhoan = $_SESSION['ID_TaiKhoan'] ?? "6";
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
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $tieude = trim($_POST['tieude']);
                    $mota = trim($_POST['mota']);
                    $nguoiNhan = $_POST['to'];
                    $cauHoiList = $_POST['cauhoi'];
                    $nguoiTao = $_SESSION['ID_TaiKhoan'] ?? "6";

                    if (!$tieude || !$mota || !$nguoiNhan || !$nguoiTao || empty($cauHoiList)) {
                        echo "<div class='alert alert-danger'>Thiếu thông tin bắt buộc.</div>";
                        exit;
                    }

                    try {
                        $conn->beginTransaction();
                        $stmt = $conn->prepare("INSERT INTO KhaoSat (TieuDe, MoTa,NguoiNhan, NguoiTao, ThoiGianTao, TrangThai) 
                               VALUES (?,?,?,?, NOW(), 1)");
                        $stmt->execute([$tieude, $mota, $nguoiNhan, $nguoiTao]);
                        $idKhaoSat = $conn->lastInsertId();

                        $stmtCauHoi = $conn->prepare("INSERT INTO CauHoiKhaoSat (ID_KhaoSat, NoiDung, TrangThai) 
                                     VALUES (?, ?, 1)");

                        foreach ($cauHoiList as $cauhoi) {
                            $noiDung = trim($cauhoi);
                            if ($noiDung !== '') {
                                $stmtCauHoi->execute([$idKhaoSat, $noiDung]);
                            }
                        }

                        $conn->commit();
                        echo "<div class='alert alert-success'>Tạo khảo sát thành công!</div>";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo "<div class='alert alert-danger'>Lỗi khi tạo khảo sát: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
                ?>
                <div class="form-container">
                    <form id="formKhaoSat" method="post">
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
                                <div class="input-group">
                                    <input type="text" name="cauhoi[]" class="form-control" required
                                        placeholder="Nhập nội dung câu hỏi">
                                    <span class="input-group-btn">
                                        <button class="btn btn-danger btn-remove" type="button">
                                            <i class="glyphicon glyphicon-remove"></i>
                                        </button>
                                    </span>
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
                    <div class="modal fade" id="modalPhanHoi<?= $ks['ID'] ?>" tabindex="-1" role="dialog"  data-backdrop="static" data-keyboard="false">
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
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Các khảo sát đã tạo</h4>

                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table " id="quanlykhaosat">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tiêu đề</th>
                                                <th>ngày tạo</th>
                                                <th>Phản hồi</th>
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dsKhaoSatTao as $index => $ks): ?>
                                                <tr>
                                                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                        style="cursor: pointer;">
                                                        <?= $index + 1 ?>
                                                    </td>
                                                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                        style="cursor: pointer;">
                                                        <?= htmlspecialchars($ks['TieuDe']) ?>
                                                    </td>
                                                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                        style="cursor: pointer;">
                                                        <?= date('d/m/Y', strtotime($ks['ThoiGianTao'])) ?>
                                                    </td>
                                                    <td onclick="window.location='pages/giaovien/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                        style="cursor: pointer;">
                                                        <?= $ks['SoLuongPhanHoi'] ?>
                                                    </td>
                                                    <td>
                                                        <form method="post"
                                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa khảo sát này?');">
                                                            <input type="hidden" name="xoa_khaosat_id"
                                                                value="<?= $ks['ID'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($dsKhaoSatTao)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Chưa có khảo sát nào
                                                        được
                                                        tạo.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.table-responsive -->
                            </div>
                            <!-- /.panel-body -->
                        </div>
                        <!-- /.panel -->
                    </div>
                    <!-- /.col-lg-6 -->
                </div>
            </div>
        </div>
        <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
            ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const danhSachCauHoi = document.getElementById("danhSachCauHoi");
                const btnThem = document.getElementById("btnThemCauHoi");
                const form = document.querySelector("form");

                btnThem.addEventListener("click", function () {
                    const cauHoiItem = danhSachCauHoi.querySelector(".cau-hoi-item");
                    const clone = cauHoiItem.cloneNode(true);
                    clone.querySelector("input").value = "";
                    danhSachCauHoi.appendChild(clone);
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

                function capNhatTrangThaiNutXoa() {
                    const items = danhSachCauHoi.querySelectorAll(".cau-hoi-item");
                    items.forEach((item, index) => {
                        const btn = item.querySelector(".btn-remove");
                        if (items.length === 1) {
                            btn.disabled = true;
                        } else {
                            btn.disabled = false;
                        }
                    });
                }

                capNhatTrangThaiNutXoa();
            });
            let clickedButton = null;

            document.querySelectorAll("button[type='submit']").forEach(button => {
                button.addEventListener("click", function () {
                    clickedButton = this;
                });
            });

            document.querySelectorAll("form").forEach(form => {
                form.addEventListener("submit", function (e) {
                    if (!clickedButton) return;

                    const action = clickedButton.value;

                    if (action === "phanhoi") {
                        if (!confirm("Xác nhận gửi phản hồi?")) {
                            e.preventDefault();
                        }
                    } else if (action === "guikhaosat") {
                        if (!confirm("Xác nhận gửi khảo sát này?")) {
                            e.preventDefault();
                        }
                    }
                });
            });
            $(document).ready(function () {
                $('#quanlykhaosat').DataTable({
                    info: false,
                    lengthChange: false
                });

                $('.btnPhanHoi').click(function () {
                    const id = $(this).data('id');
                    const ten = $(this).data('ten');
                    alert("Mở modal phản hồi khảo sát ID " + id + " - " + ten);
                });
            });
        </script>
</body>

</html>
=======
                </div><div class="form-container">
                <form id="formKhaoSat" method="post">
                    <div class="form-group">
                        <label><strong>Gửi đến</strong></label>
                        <select id="for" name="Lọc" class="form-control" style="width: 200px;">
                            <option value="Sinh viên">Sinh Viên</option>
                            <option value="Giáo viên">Giáo Viên</option>
                            <option value="Tất cả">Tất cả</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>Tiêu đề</label>
                                <input class="form-control" id="tieude" name="tieude" type="text" placeholder="Nhập tiêu đề cho khảo sát">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>Mô tả</label>
                                <input class="form-control" id="mota" name="mota" type="text" placeholder="Nhập mô tả">
                            </div>
                        </div>
                    </div>
                    <div id="danhSachCauHoi">
                        <div class="form-group cau-hoi-item">
                            <label>Câu hỏi</label>
                            <div class="input-group">
                                <input type="text" name="cauhoi[]" class="form-control" placeholder="Nhập nội dung câu hỏi">
                                <span class="input-group-btn">
                                    <button class="btn btn-danger btn-remove" type="button">
                                        <i class="glyphicon glyphicon-remove"></i>
                                    </button>
                                </span>
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
            </div>
        <div id="notification" style="display: none;"></div>
        <div id="containerKhaoSat" class="mt-3">
            <h2>Danh sách các khảo sát</h2>
            <div id="listKhaoSat" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tiêu đề</th>
                                                    <th>Người tạo</th>
                                                    <th>ngày tạo</th>
                                                    <th>Phản hồi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr onclick="window.location='pages/canbo/chitietkhaosat';" style="cursor: pointer;">
                                                    <td>1</td>
                                                    <td>Khảo sát thực tập</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    <td>1/1/2025</td>
                                                    <td>35</td>
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietkhaosat';" style="cursor: pointer;">
                                                    <td>3</td>
                                                    <td>Khảo sát thực tập</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    <td>1/1/2025</td>
                                                    <td>35</td>
                                                </tr>
                                                <tr onclick="window.location='pages/canbo/chitietkhaosat';" style="cursor: pointer;">
                                                    <td>3</td>
                                                    <td>Khảo sát thực tập</td>
                                                    <td>Lữ Cao Tiến</td>
                                                    <td>1/1/2025</td>
                                                    <td>35</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.table-responsive -->
                                </div>
                                <!-- /.panel-body -->
                            </div>
                            <!-- /.panel -->
                        </div>
                        <!-- /.col-lg-6 -->
                    </div>
</div>      
</div>
<script>
$(document).ready(function () {
    $('#btnThemCauHoi').click(function () {
        var cauHoiMoi = `
            <div class="row">
                <div class="col-lg-12 form-group cau-hoi-item">
                    <label>Câu hỏi</label>
                    <div class="input-group">
                        <input type="text" name="cauhoi[]" class="form-control" placeholder="Nhập nội dung câu hỏi">
                        <span class="input-group-btn">
                            <button class="btn btn-danger btn-remove" type="button">
                                <i class="glyphicon glyphicon-remove"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>`;
        $('#danhSachCauHoi').append(cauHoiMoi);
    });
    $(document).on('click', '.btn-remove', function () {
        $(this).closest('.cau-hoi-item').remove();
    });
});
</script>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
