<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
($role == "Cán bộ Khoa/Bộ môn") ?
    $stmt = $conn->prepare("SELECT Ten FROM CanBoKhoa WHERE ID_TaiKhoan = ?") :
    $stmt = $conn->prepare("SELECT Ten FROM Admin WHERE ID_TaiKhoan = ?");

$stmt->execute([$idTaiKhoan]);
$hoTen = $stmt->fetchColumn();
function getAllInternships($conn)
{
    $stmt = $conn->prepare("
        SELECT 
            d.ID, d.TenDot, d.Nam, d.BacDaoTao, d.NguoiQuanLy,
            COALESCE(cb.Ten, ad.Ten) AS TenNguoiQuanLy,
            d.ThoiGianBatDau, d.ThoiGianKetThuc, d.NguoiMoDot, d.TrangThai
        FROM DOTTHUCTAP d
        LEFT JOIN CanBoKhoa cb ON d.NguoiQuanLy = cb.ID_TaiKhoan
        LEFT JOIN Admin ad ON d.NguoiQuanLy = ad.ID_TaiKhoan
        WHERE d.TrangThai != -1
        ORDER BY d.ThoiGianBatDau DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);   
}

function countSimilar($conn, $tendot)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM DOTTHUCTAP WHERE TENDOT LIKE :tendot");
    $stmt->execute(['tendot' => $tendot . '%']);
    return $stmt->fetchColumn();
}
function saveInternship($conn, $tendot, $BacDaoTao, $namHoc, $thoigianbatdau, $thoigianketthuc, $nguoiquanly, $nguoitao)
{
    $stmt = $conn->prepare("INSERT INTO DOTTHUCTAP (
        TENDOT, NAM, BACDAOTAO, NGUOIQUANLY, THOIGIANBATDAU, THOIGIANKETTHUC, NGUOIMODOT, TRANGTHAI
    ) VALUES (:tendot, :nam, :BacDaoTao, :nguoiquanly, :thoigianbatdau, :thoigianketthuc, :nguoimodot, 1)");

    if (
        $stmt->execute([
            'tendot' => $tendot,
            'nam' => $namHoc,
            'BacDaoTao' => $BacDaoTao,
            'nguoiquanly' => $nguoiquanly,
            'thoigianbatdau' => $thoigianbatdau,
            'thoigianketthuc' => $thoigianketthuc,
            'nguoimodot' => $nguoitao
        ])
    ) {
        return $conn->lastInsertId();
    }

    return false;
}
$successMessage = "";
$notification = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    var_dump($_POST['NguoiQuanLy']);
    $BacDaoTao = $_POST['BacDaoTao'];
    $namHoc = $_POST['namhoc'];
    $nguoitao = $idTaiKhoan;
    $nguoiQuanLy = intval($_POST['NguoiQuanLy']);
    $thoigianbatdau = $_POST['thoigianbatdau'];
    $thoigianketthuc = $_POST['thoigianketthuc'];
    if ($BacDaoTao == "" || $namHoc == "" || $thoigianbatdau == "" || $thoigianketthuc == "" || $nguoiQuanLy == "") {
        $notification = "Vui lòng điền tất cả các trường.";
    } else {
        $tendot = ($BacDaoTao === 'Cao đẳng ngành' ? 'CĐTH' : 'CĐNTH') . substr($namHoc, -2);

        $count = countSimilar($conn, $tendot);
        $tendot = $tendot . '-' . ($count + 1);

        $idDot = saveInternship($conn, $tendot, $BacDaoTao, $namHoc, $thoigianbatdau, $thoigianketthuc, $nguoiQuanLy, $nguoitao);
        if ($idDot) {
            session_start();
            $_SESSION['success'] = "Đợt thực tập $tendot được mở thành công!";
            header("Location: /datn/admin/pages/chitietdot?id=" . urlencode($idDot));
            exit;
        } else {
            $notification = "Mở đợt thực tập thất bại!";
            unset($_POST);
        }
    }

}
$danhSachDotThucTap = getAllInternships($conn);
$stmt = $conn->query("
    SELECT ID_TaiKhoan, Ten FROM canbokhoa WHERE TrangThai = 1
    UNION
    SELECT ID_TaiKhoan, Ten FROM admin WHERE TrangThai = 1
");
$nguoiQuanLyList = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_SESSION['deleted'])) {
    $successMessage = $_SESSION['deleted'];
    unset($_SESSION['deleted']);
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Mở đợt thực tập</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
    <style>
    
    
    .page-header {
        /* background: #2c3e50; */
        color: #fff;
        padding: 20px 25px;
        margin-top: 0;
        border-radius: 5px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .page-header h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 600;
    }

    .btn-primary {
        background-color: #1abc9c;
        border-color: #16a085;
    }

    .btn-primary:hover {
        background-color: #16a085;
        border-color: #149174;
    }

    .panel {
        background: #ffffff;
        border: 1px solid #dcdcdc;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .panel-heading {
        background-color: #34495e;
        color: #fff;
        padding: 15px;
        font-size: 18px;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
    }

    .table thead th {
        background-color: #ecf0f1;
        color: #34495e;
        font-weight: 600;
    }

    .table td, .table th {
        vertical-align: middle !important;
    }

    #filterTrangThai {
        border: 1px solid #ccc;
        padding: 6px 12px;
        border-radius: 4px;
    }

    #noti.alert {
        margin-top: 15px;
        font-size: 16px;
    }

    .form-container {
        background: #ffffff;
        border: 1px solid #ddd;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }

    input.form-control, select.form-control {
        border-radius: 4px;
        box-shadow: none;
        border: 1px solid #ccc;
    }

    .btn-default {
        background:rgb(255, 255, 255);
        border-color: #95a5a6;
    }

    .btn-default:hover {
        background: #95a5a6;
        color: white;
    }

    a.btn-xs {
        padding: 5px 10px;
        font-size: 12px;
    }

    /* Responsive cải tiến */
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 22px;
        }

        .btn-lg {
            font-size: 16px;
            padding: 10px 16px;
        }
    }
</style>
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
                        Quản lý Đợt Thực Tập
                    </h1><?php if (!empty($successMessage)): ?>
                        <div id="noti" class="alert alert-success">
                            <?= $successMessage ?>
                        </div>
                    <?php endif; ?>
                    <button id="btnShowFormMoDot" class="btn btn-primary btn-lg mt-3">Mở đợt thực tập mới</button>
                </div>
                <div class="row">
                    <div class="form-container" id="formMoDotContainer" style="display:none;">
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
                                        <label>Bậc đào tạo</label>
                                        <select id="BacDaoTao" name="BacDaoTao" class="form-control">
                                            <option value="Cao đẳng ngành">Cao đẳng ngành</option>
                                            <option value="Cao đẳng nghề">Cao đẳng nghề</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Người quản lý đợt</label>
                                        <select id="NguoiQuanLy" name="NguoiQuanLy" class="form-control">
                                            <?php foreach ($nguoiQuanLyList as $i => $nql): ?>
                                                <option value="<?= $nql['ID_TaiKhoan'] ?>" <?= $i === 0 ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($nql['Ten']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-offset text-center" style="margin-bottom: 10px;">
                                    <button type="submit" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                                    <button type="button" id="btnHideFormMoDot" class="btn btn-default btn-lg mt-3"
                                        style="margin-left:10px;">Đóng</button>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
                <div id="containerDotThucTap" class="mt-3">

                    <div id="listDotThucTap" class="row">
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-md-9">
                                            <h4>Danh sách các đợt thực tập</h4>
                                        </div>
                                        <div class="col-md-3">
                                            <select id="filterTrangThai" class="form-control">
                                                <option value="">-- Tất cả trạng thái --</option>
                                                <option value="Đang chuẩn bị">Đang chuẩn bị</option>
                                                <option value="Hoàn tất phân công">Hoàn tất phân công</option>
                                                <option value="Đã bắt đầu">Đã bắt đầu</option>
                                                <option value="Đã kết thúc">Đã kết thúc</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">

                                        <table class="table" id="TableDotTT">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Tên đợt</th>
                                                    <th>Năm</th>
                                                    <th>Bậc đào tạo</th>
                                                    <th>Thời gian bắt đầu</th>
                                                    <th>Thời gian kết thúc</th>
                                                    <th>Người quản lý</th>
                                                    <th>Trạng thái</th>
                                                    <th>Xem</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1;
                                                foreach ($danhSachDotThucTap as $dot):
                                                    if ($dot['TrangThai'] == -1)
                                                        continue;
                                                    $link = '/datn/admin/pages/chitietdot?id=' . urlencode($dot['ID']);
                                                    switch ($dot['TrangThai']) {
                                                        case 1:
                                                            $trangthai = 'Đang chuẩn bị';
                                                            break;
                                                        case 2: case 4:
                                                            $trangthai = 'Đã bắt đầu';
                                                            break;
                                                        case 3:
                                                            $trangthai = 'Hoàn tất phân công';
                                                            break;
                                                        case 5:
                                                            $trangthai = 'Nộp kết quả';
                                                         case 0:
                                                            $trangthai = 'Đã kết thúc';
                                                            break;
                                                        default:
                                                            $trangthai = 'Không xác định';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= $i++ ?></td>
                                                        <td><?= htmlspecialchars($dot['TenDot']) ?></td>
                                                        <td><?= htmlspecialchars($dot['Nam']) ?></td>
                                                        <td><?= htmlspecialchars($dot['BacDaoTao']) ?></td>
                                                        <td><?= htmlspecialchars($dot['ThoiGianBatDau']) ?></td>
                                                        <td><?= htmlspecialchars($dot['ThoiGianKetThuc']) ?></td>
                                                        <td><?= htmlspecialchars($dot['TenNguoiQuanLy']) ?></td>
                                                        <td><?= $trangthai ?></td>
                                                        <td>
                                                            <a href="<?= $link ?>" class="btn btn-xs btn-primary">Xem chi
                                                                tiết</a>
                                                        </td>
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
    <?php
    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>
    <script>
        function formatDateInput(date) {
            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const day = date.getDate().toString().padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        document.getElementById('btnShowFormMoDot').addEventListener('click', function () {
            var formDiv = document.getElementById('formMoDotContainer');
            if (formDiv.style.display === 'none') {
                formDiv.style.display = 'block';
                this.style.display = 'none';
            }
        });

        document.getElementById('btnHideFormMoDot').addEventListener('click', function () {
            document.getElementById('formMoDotContainer').style.display = 'none';
            document.getElementById('btnShowFormMoDot').style.display = 'inline-block';
        });

        var table;
        $(document).ready(function () {
            table = $('#TableDotTT').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: false,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
                }
            });
        });

        $('#filterTrangThai').on('change', function () {
            var val = $(this).val();
            if (val) {
                table.column(7).search('^' + val + '$', true, false).draw();
            } else {
                table.column(7).search('').draw();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('thoigianbatdau');
            const endInput = document.getElementById('thoigianketthuc');
            const form = document.getElementById('FormMoDot');

            // Gán min ngày bắt đầu = hôm nay + 1 (dùng local time)
            const today = new Date();
            today.setDate(today.getDate() + 1);
            startInput.min = formatDateInput(today);

            startInput.addEventListener('change', function () {
                const startDate = new Date(this.value);
                if (!isNaN(startDate)) {
                    startDate.setDate(startDate.getDate() + 28); // Cộng 4 tuần
                    endInput.min = formatDateInput(startDate);
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

                const BacDaoTao = document.getElementById('BacDaoTao').value;
                const nam = document.getElementById('namhoc').value;
                const nguoiquanlySelect = document.getElementById('NguoiQuanLy');
                const nguoiquanly = nguoiquanlySelect.options[nguoiquanlySelect.selectedIndex].text;
                const batdau = new Date(startInput.value);
                const ketthuc = new Date(endInput.value);

                if (!startInput.value || !endInput.value || isNaN(batdau) || isNaN(ketthuc)) {
                    Swal.fire("Lỗi", "Vui lòng chọn đầy đủ thời gian hợp lệ");
                    return;
                }

                const minKetThuc = new Date(batdau);
                minKetThuc.setDate(minKetThuc.getDate() + 28); // Phải sau ít nhất 4 tuần

                if (ketthuc < minKetThuc) {
                    Swal.fire("Lỗi", "Thời gian kết thúc phải sau thời gian bắt đầu ít nhất 4 tuần");
                    return;
                }

                Swal.fire({
                    title: 'Xác nhận mở đợt?',
                    html: `
                    <p><strong>Bậc đào tạo:</strong> ${BacDaoTao}</p>
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

        // Ẩn thông báo sau 2 giây
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