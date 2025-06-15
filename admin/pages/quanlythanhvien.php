<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';
$vaiTroDaChon = $_POST['loc'] ?? 'Tất cả';
$dsThanhVien = [];
$sql = "SELECT 
            tk.ID_TaiKhoan, 
            tk.TaiKhoan,
            tk.MatKhau, 
            tk.VaiTro, 
            tk.TrangThai,
            COALESCE(sv.Ten, gv.Ten, cbk.Ten,ad.Ten) AS HoTen,
            sv.MSSV, 
            sv.Lop, 
            sv.ID_Dot
        FROM TaiKhoan tk 
        LEFT JOIN SinhVien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN GiaoVien gv ON gv.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN CanBoKhoa cbk ON cbk.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN Admin ad ON ad.ID_TaiKhoan = tk.ID_TaiKhoan"
;

$params = [];

if ($vaiTroDaChon != 'Tất cả') {
    $sql .= " WHERE tk.VaiTro = ?";
    $params[] = $vaiTroDaChon;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$danhSachThanhVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT ID,TenDot,TrangThai FROM DOTTHUCTAP where TrangThai =1 ORDER BY ID DESC");
$stmt->execute();
$dsDotThucTap = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý thành viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row mt-5">

                    <div class="col-lg-12">
                        <h1 class="page-header">Quản Lý Thành Viên</h1>
                    </div>
                </div>

                <div class="row">
                    <form id="formXoa" method="POST" action="admin/pages/xoathanhvien" onsubmit="return xacNhanXoa();">
                        <div class="row">
                            <div class="form-group col-sm-2">
                                <select name="loc" class="form-control" required onchange="this.form.submit()">
                                    <option value="Tất cả" <?= ($vaiTroDaChon == 'Tất cả') ? 'selected' : '' ?>>Tất cả
                                    </option>
                                    <option value="Cán bộ Khoa/Bộ môn" <?= ($vaiTroDaChon == 'Cán bộ Khoa/Bộ môn') ? 'selected' : '' ?>>Cán bộ Khoa/Bộ môn</option>
                                    <option value="Giáo viên" <?= ($vaiTroDaChon == 'Giáo viên') ? 'selected' : '' ?>>Giáo
                                        viên</option>
                                    <option value="Sinh viên" <?= ($vaiTroDaChon == 'Sinh viên') ? 'selected' : '' ?>>Sinh
                                        viên</option>
                                    <option value="Admin" <?= ($vaiTroDaChon == 'Admin') ? 'selected' : '' ?>>Admin
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-1">
                                <button type="button" class="btn btn-primary" onclick="moModalThem()">Thêm</button>
                            </div>
                            <div class="form-group col-sm-1">
                                <button type="submit" class="btn btn-danger">Xóa</button>
                            </div>
                        </div>
                        <?php
                        if (isset($_GET['error']) && $_GET['error'] === 'duplicated'): ?>
                            <div id="noti" class="alert alert-danger text-center">
                                Tài khoản đã tồn tại. Vui lòng nhập lại tài khoản khác.
                            </div>
                        <?php endif;
                        if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                            <div id="noti" class="alert alert-danger text-center">Đã xóa các tài khoản đã chọn.</div>
                        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'empty'): ?>
                            <div id="noti" class="alert alert-danger text-center">Bạn chưa chọn tài khoản nào để xóa.</div>
                        <?php endif;
                        ?>
                        <div class="row">
                            <h3>Danh sách thành viên</h3>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table" id="tableThanhVien">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Tài Khoản</th>
                                                    <th>Họ Tên</th>
                                                    <th>Vai trò</th>
                                                    <th>Trạng Thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($danhSachThanhVien as $tv): ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="chon[]"
                                                                value="<?= $tv['ID_TaiKhoan'] ?>"></td>
                                                        <td style="cursor: pointer;"
                                                            onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                            <?= $tv['TaiKhoan'] ?>
                                                        </td>
                                                        <td style="cursor: pointer;"
                                                            onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                            <?= $tv['HoTen'] ?>
                                                        </td>
                                                        <td style="cursor: pointer;"
                                                            onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                            <?= $tv['VaiTro'] ?>
                                                        </td>
                                                        <td style="cursor: pointer;"
                                                            onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                            <?= $tv['TrangThai'] == 1 ? 'Hoạt động' : 'Ngừng hoạt động' ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
            <?php
            require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"

                ?>
            <script>
                document.querySelector("select[name='loc']").addEventListener('change', function () {
                    document.getElementById('FormQuanLy').submit();
                });
                $(document).ready(function () {
                    var table = $('#tableThanhVien').DataTable({
                        responsive: true,
                        pageLength: 20,
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                        }
                    });

                });
                function xacNhanXoa() {
                    const checkboxes = document.querySelectorAll("input[name='chon[]']:checked");

                    if (checkboxes.length === 0) {
                        alert("Vui lòng chọn ít nhất một thành viên để xóa.");
                        return false;
                    }

                    let danhSachTaiKhoan = [];

                    checkboxes.forEach(cb => {
                        const row = cb.closest('tr');
                        const taiKhoan = row.cells[1].textContent.trim();
                        const hoTen = row.cells[2].textContent.trim();
                        danhSachTaiKhoan.push(`${hoTen} (${taiKhoan})`);
                    });

                    const message = "Bạn có chắc chắn muốn xóa các tài khoản sau?\n\n" + danhSachTaiKhoan.join('\n');

                    return confirm(message);
                }

                document.querySelector('select[name="loc"]').addEventListener("change", function () {
                    const vaiTro = this.value;

                    fetch("loadthanhvien.php?vaiTro=" + encodeURIComponent(vaiTro))
                        .then(res => res.json())
                        .then(data => {
                            const tbody = document.querySelector("#tableThanhVien tbody");
                            tbody.innerHTML = "";

                            data.forEach(tv => {
                                tbody.innerHTML += `
                        <tr>
                            <td><input type="checkbox" name="thanhvien[]" value="${tv.ID_TaiKhoan}"></td>
                            <td>${tv.MSSV ?? ''}</td>
                            <td>${tv.ID_Dot ?? ''}</td>
                            <td>${tv.Lop ?? ''}</td>
                            <td>${tv.HoTen}</td>
                            <td>${tv.TrangThai == 1 ? 'Hoạt động' : 'Ngừng hoạt động'}</td>
                        </tr>`;
                            });
                        });
                });
                function moModalThem() {
                    $('#formThanhVien')[0].reset();
                    $('#modalTitle').text('Thêm thành viên');
                    $('#che_do').val('them');
                    $('#id_tai_khoan').val('');
                    $('#dotThucTapGroup').show();
                    $('#matkhau').prop('required', true);
                    $('#matkhau').prop('placeholder', '');

                    $('#vai_tro').val('');
                    $('#sinhvien_fields').hide();

                    $('#modalThanhVien').modal('show');
                }
                function hienSinhVienFields() {
                    const vaiTro = $('#vai_tro').val();
                    if (vaiTro === 'Sinh viên') {
                        $('#sinhvien_fields').show();
                    } else {
                        $('#sinhvien_fields').hide();
                    }
                }

                function moModalChinhSua(data) {
                    $('#formThanhVien')[0].reset();
                    $('#modalTitle').text('Chỉnh sửa thành viên');
                    $('#che_do').val('sua');
                    $('#id_tai_khoan').val(data.ID_TaiKhoan);
                    $('#ten').val(data.HoTen);
                    $('#tai_khoan').val(data.TaiKhoan);
                    $('#vai_tro').val(data.VaiTro);
                    $('#matkhau').prop('placeholder', 'Nhập để đổi mật khẩu');
                    $('#matkhau').prop('required', false);

                    hienSinhVienFields();

                    if (data.VaiTro === 'Sinh viên') {
                        $('#mssv').val(data.MSSV);
                        $('#lop').val(data.Lop);
                        $('#dotThucTapGroup').hide();
                    } else {
                        $('#sinhvien_fields').hide();
                    }

                    $('#modalThanhVien').modal('show');
                }
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
<div class="modal fade" id="modalThanhVien" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <form method="post" id="formThanhVien" action="admin/pages/xulythanhvien">
            <input type="hidden" name="che_do" id="che_do">
            <input type="hidden" name="id_tai_khoan" id="id_tai_khoan">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalTitle">Thêm thành viên</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Họ tên</label>
                        <input type="text" class="form-control" id="ten" name="ten" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Tài khoản)</label>
                        <input type="text" class="form-control" id="tai_khoan" name="tai_khoan" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu/cccd</label>
                        <input type="password" class="form-control" id="matkhau" name="matkhau" required>
                    </div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <select class="form-control" id="vai_tro" name="vai_tro" required
                            onchange="hienSinhVienFields()">
                            <option value="">-- Chọn vai trò --</option>
                            <option value="Sinh viên">Sinh viên</option>
                            <option value="Giáo viên">Giáo viên</option>
                            <option value="Cán bộ Khoa/Bộ môn">Cán bộ Khoa/Bộ môn</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div id="sinhvien_fields" style="display: none;">
                        <div class="form-group">
                            <label>MSSV</label>
                            <input type="text" class="form-control" id="mssv" name="mssv">
                        </div>
                        <div class="form-group">
                            <label>Lớp</label>
                            <input type="text" class="form-control" id="lop" name="lop">
                        </div>
                        <div class="form-group" id="dotThucTapGroup">
                            <label>Chọn đợt thực tập</label>
                            <select class="form-control" name="dot" id="dot">
                                <?php foreach ($dsDotThucTap as $dot): ?>
                                    <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnLuu">Lưu</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>

                </div>
            </div>
        </form>
    </div>
</div>