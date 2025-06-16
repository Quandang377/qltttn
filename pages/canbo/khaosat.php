<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_login.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$ID_TaiKhoan = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT ks.ID, ks.TieuDe, ks.ThoiGianTao,
    (SELECT COUNT(*) FROM PhanHoiKhaoSat WHERE ID_KhaoSat = ks.ID) AS SoLuongPhanHoi
    FROM KhaoSat ks
    WHERE ks.NguoiTao = ? and ks.TrangThai=1
    ORDER BY ks.ThoiGianTao DESC");
$stmt->execute([$ID_TaiKhoan]);
$dsKhaoSatTao = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Tạo khảo sát
                    </h1>
                </div>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $tieude = trim($_POST['tieude'] ?? '');
                    $mota = trim($_POST['mota'] ?? '');
                    $nguoiNhan = $_POST['to'] ?? '';
                    $cauHoiList = $_POST['cauhoi'] ?? [];
                    $nguoiTao = $ID_TaiKhoan;

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
                        echo "<div  class='alert alert-success'id='noti'>Tạo khảo sát thành công!</div>";
                        $stmt = $conn->prepare("SELECT ks.ID, ks.TieuDe, ks.ThoiGianTao,
                        (SELECT COUNT(*) FROM PhanHoiKhaoSat WHERE ID_KhaoSat = ks.ID) AS SoLuongPhanHoi
                        FROM KhaoSat ks
                        WHERE ks.NguoiTao = ? and ks.TrangThai=1
                        ORDER BY ks.ThoiGianTao DESC");
                        $stmt->execute([$ID_TaiKhoan]);
                        $dsKhaoSatTao = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo "<div class='alert alert-danger'id='noti'>Lỗi khi tạo khảo sát: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
                ?>
                <div class="form-container">
                    <form id="formKhaoSat" method="post">
                        <div class="form-group">
                            <label><strong>Gửi đến</strong></label>
                            <select id="to" name="to" class="form-control" style="width: 200px;" required>
                                <option value="Sinh viên" selected>Sinh Viên</option>
                                <option value="Giáo viên">Giáo Viên</option>
                                <option value="Tất cả">Tất cả</option>
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
                            <button type="submit" class="btn btn-success btn-lg">Gửi</button>
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
                                <h4>Danh sách các khảo sát đã tạo</h4>
                            </div>
                            <div class="panel-body">
                                <table class="table" id="quanlykhaosat">
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
                                                <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                    style="cursor: pointer;">
                                                    <?= $index + 1 ?>
                                                </td>
                                                <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                    style="cursor: pointer;">
                                                    <?= htmlspecialchars($ks['TieuDe']) ?>
                                                </td>
                                                <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                    style="cursor: pointer;">
                                                    <?= date('d/m/Y', strtotime($ks['ThoiGianTao'])) ?>
                                                </td>
                                                <td onclick="window.location='pages/canbo/chitietkhaosat?id=<?= $ks['ID'] ?>';"
                                                    style="cursor: pointer;">
                                                    <?= $ks['SoLuongPhanHoi'] ?>
                                                </td>
                                                <td>
                                                    <form method="post"
                                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa khảo sát này?');">
                                                        <input type="hidden" name="xoa_khaosat_id" value="<?= $ks['ID'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($dsKhaoSatTao)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Chưa có khảo sát nào được
                                                    tạo.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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

                form.addEventListener("submit", function (e) {
                    const xacNhan = confirm("Xác nhận gửi khảo sát này?");
                    if (!xacNhan) {
                        e.preventDefault();
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
            $(document).ready(function () {
                $('#quanlykhaosat').DataTable({
                    info: false,
                    language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                        }
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
    </div>
</body>

</html>