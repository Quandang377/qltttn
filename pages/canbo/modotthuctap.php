<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

function getAllInternships($pdo)
{
    $stmt = $pdo->prepare("SELECT ID,TenDot,Nam,Loai,NguoiQuanLy,ThoiGianBatDau,ThoiGianKetThuc,TenNguoiMoDot,TrangThai FROM DOTTHUCTAP where TrangThai !=-1 ORDER BY ID DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}
function countSimilar($conn, $tendot)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM DOTTHUCTAP WHERE TENDOT LIKE :tendot");
    $stmt->execute(['tendot' => $tendot . '%']);
    return $stmt->fetchColumn();
}
function saveInternship($conn, $tendot, $loai, $namHoc, $thoigianbatdau, $thoigianketthuc, $nguoiquanly, $nguoitao)
{
    $stmt = $conn->prepare("INSERT INTO DOTTHUCTAP (TENDOT, NAM, LOAI, NGUOIQUANLY, THOIGIANBATDAU, THOIGIANKETTHUC, TENNGUOIMODOT, TRANGTHAI) 
                           VALUES (:tendot, :nam, :loai, :nguoiquanly, :thoigianbatdau, :thoigianketthuc, :tennguoimodot, 1)");
    if (
        $stmt->execute([
            'tendot' => $tendot,
            'nam' => $namHoc,
            'loai' => $loai,
            'nguoiquanly' => $nguoiquanly,
            'thoigianbatdau' => $thoigianbatdau,
            'thoigianketthuc' => $thoigianketthuc,
            'tennguoimodot' => $nguoitao
        ])
    ) {
        return $conn->lastInsertId();
    }
    return false;
}
$successMessage = "";
$notification = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loai = $_POST['loai'];
    $namHoc = $_POST['namhoc'];
    $nguoitao = $_POST['nguoitao'];
    $nguoiquanly = $_POST['nguoiquanly'];
    $thoigianbatdau = $_POST['thoigianbatdau'];
    $thoigianketthuc = $_POST['thoigianketthuc'];
    if ($loai == "" || $namHoc == "" || $thoigianbatdau == "" || $thoigianketthuc == "" || $nguoiquanly == "") {
        $notification = "Vui lòng điền tất cả các trường.";
    } else {
        $tendot = ($loai === 'Cao đẳng' ? 'CĐTH' : 'CĐNTH') . substr($namHoc, -2) . $lastWord;

        $count = countSimilar($conn, $tendot);
        $tendot = $tendot . '-' . ($count + 1);

        $idDot = saveInternship($conn, $tendot, $loai, $namHoc, $thoigianbatdau, $thoigianketthuc, $nguoiquanly, $nguoitao);
        if ($idDot) {
            session_start();
            $_SESSION['success'] = "Đợt thực tập $tendot được mở thành công!";
            header("Location: /datn/pages/canbo/chitietdot?id=" . urlencode($idDot));
            exit;
        } else {
            $notification = "Mở đợt thực tập thất bại!";
            unset($_POST);
        }
    }

}
$today = date('Y-m-d');
$updateStmt = $conn->prepare("UPDATE DOTTHUCTAP SET TRANGTHAI = 0 WHERE THOIGIANKETTHUC < :today AND TRANGTHAI = 2");
$updateStmt->execute(['today' => $today]);
$updateStmt2 = $conn->prepare("UPDATE DOTTHUCTAP SET TRANGTHAI = 2 WHERE THOIGIANBATDAU <= :today AND TRANGTHAI = 1");
$updateStmt2->execute(['today' => $today]);
$danhSachDotThucTap = getAllInternships($conn);
$canbokhoa = $conn->query("SELECT ID_TaiKhoan,Ten FROM canbokhoa where TrangThai=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Mở đợt thực tập</title>
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
                        Mở Đợt Thực Tập
                    </h1>
                </div>
                <div class="row">
                    <div class="form-container">
                        <form id="FormMoDot" method="post">
                            <div class="row mb-3">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label>Năm</label>
                                            <input class="form-control" id="namhoc" name="namhoc" type="number"
                                                min="1000" max="9999" placeholder="Nhập năm học">
                                        </div>
                                        <div class="form-group">
                                            <label>Thời gian bắt đầu</label>
                                            <?php $NgayMai = date('Y-m-d', strtotime('+1 day')); ?>
                                            <input class="form-control" id="thoigianbatdau" name="thoigianbatdau"
                                                type="date" min="<?= $NgayMai ?>" placeholder="Chọn thời gian bắt đầu">
                                        </div>
                                        <div class="form-group">
                                            <label>Thời gian kết thúc</label>
                                            <input class="form-control" id="thoigianketthuc" name="thoigianketthuc"
                                                type="date" placeholder="Chọn thời gian kết thúc">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Loại</label>
                                        <select id="loai" name="loai" class="form-control">
                                            <option value="Cao đẳng">Cao đẳng</option>
                                            <option value="Cao đẳng ngành">Cao đẳng ngành</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Người quản lý đợt</label>
                                        <select id="nguoiquanly" name="nguoiquanly" class="form-control">
                                            <?php foreach ($canbokhoa as $cb): ?>
                                                <option value="<?= $cb['Ten'] ?>"><?= htmlspecialchars($cb['Ten']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" class="form-control" id="nguoitao" name="nguoitao"
                                    value="Lữ Cao Tiến">
                            </div>
                            <div class="row">
                                <div class="col-md-offset text-center">
                                    <button type="submit" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
                <div id="containerDotThucTap" class="mt-3">
                    <h2>Danh sách các đợt thực tập</h2>
                    <div id="listDotThucTap" class="row">
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table" id="TableDotTT">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tên đợt</th>
                                                    <th>Năm</th>
                                                    <th>Thời gian bắt đầu</th>
                                                    <th>Người quản lý</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1;
                                                foreach ($danhSachDotThucTap as $dot): ?>
                                                    <?php $link = 'pages/canbo/chitietdot?id=' . urlencode($dot['ID']); ?>
                                                    <tr onclick="window.location='<?= $link ?>';" style="cursor: pointer;">
                                                        <td><?= $i++ ?></td>
                                                        <td><?= htmlspecialchars($dot['TenDot']) ?></td>
                                                        <td><?= htmlspecialchars($dot['Nam']) ?></td>
                                                        <td><?= htmlspecialchars($dot['ThoiGianBatDau']) ?></td>
                                                        <td><?= htmlspecialchars($dot['NguoiQuanLy']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
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
        </div>
    </div>
</body>

</html>
<script>

    $(document).ready(function () {
        var table = $('#TableDotTT').DataTable({
            responsive: true,
            pageLength: 20,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
            }
        });

    });
    document.addEventListener('DOMContentLoaded', function () {
        const startInput = document.getElementById('thoigianbatdau');
        const endInput = document.getElementById('thoigianketthuc');
        const form = document.getElementById('FormMoDot');

        const today = new Date();
        today.setDate(today.getDate() + 1);
        const minStartDate = today.toISOString().split('T')[0];
        startInput.min = minStartDate;

        startInput.addEventListener('change', function () {
            const startDate = new Date(this.value);
            if (!isNaN(startDate)) {
                startDate.setDate(startDate.getDate() + 28);
                const minEndDate = startDate.toISOString().split('T')[0];
                endInput.min = minEndDate;
            }
        });

        <?php if (!empty($notification)): ?>
            Swal.fire({
                title: 'Thất bại!',
                text: '<?= addslashes($notification) ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        <?php endif ?>

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const loai = document.getElementById('loai').value;
            const nam = document.getElementById('namhoc').value;
            const nguoiquanly = document.getElementById('nguoiquanly').value;
            const batdau = new Date(startInput.value);
            const ketthuc = new Date(endInput.value);

            if (!startInput.value || !endInput.value || isNaN(batdau) || isNaN(ketthuc)) {
                Swal.fire("Lỗi", "Vui lòng chọn đầy đủ thời gian hợp lệ");
                return;
            }

            const minKetThuc = new Date(batdau);
            minKetThuc.setDate(minKetThuc.getDate() + 28);

            if (ketthuc < minKetThuc) {
                Swal.fire("Lỗi", "Thời gian kết thúc phải sau thời gian bắt đầu ít nhất 4 tuần");
                return;
            }

            Swal.fire({
                title: 'Xác nhận mở đợt?',
                html: `
                <p><strong>Loại:</strong> ${loai}</p>
                <p><strong>Năm học:</strong> ${nam}</p>
                <p><strong>Thời gian bắt đầu:</strong> ${startInput.value}</p>
                <p><strong>Thời gian kết thúc:</strong> ${endInput.value}</p>
                <p><strong>Người quản lý:</strong> ${nguoiquanly}</p>
            `,
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>