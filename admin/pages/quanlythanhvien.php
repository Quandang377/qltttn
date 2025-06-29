<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if (isset($_GET['msg']) && $_GET['msg'] === 'mo_khoa_ok'): ?>
    <div id="noti" class="alert alert-success text-center">Mở khóa thành công.</div>
<?php endif;

$stmt = $conn->prepare("SELECT ID, TenDot FROM DotThucTap WHERE TrangThai =1 ORDER BY ThoiGianBatDau desc");
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
                    <?php
                    if (isset($_GET['error']) && $_GET['error'] === 'duplicated'): ?>
                        <div id="noti" class="alert alert-danger text-center">
                            Tài khoản đã tồn tại. Vui lòng nhập lại tài khoản khác.
                        </div>
                    <?php endif;

                    if (isset($_GET['msg'])) {
                        switch ($_GET['msg']) {
                            case 'deleted':
                                echo '<div id="noti" class="alert alert-success text-center">Đã xóa các tài khoản đã chọn.</div>';
                                break;
                            case 'empty':
                                echo '<div id="noti" class="alert alert-danger text-center">Bạn chưa chọn tài khoản nào để xóa.</div>';
                                break;
                            case 'unlocked':
                                echo '<div id="noti" class="alert alert-success text-center">Đã mở khóa tài khoản thành công.</div>';
                                break;
                            case 'added':
                                echo '<div id="noti" class="alert alert-success text-center">Đã thêm tài khoản thành công.</div>';
                                break;
                            case 'edited':
                                echo '<div id="noti" class="alert alert-success text-center">Đã chỉnh sửa tài khoản thành công.</div>';
                                break;
                        }
                    }
                    ?>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <form method="POST" id="formLocThanhVien" class="form-inline">
                            <div class="form-group col-sm-3">
                                <select name="selectVaiTro" id="selectVaiTro" class="form-control">
                                    <option value="Tất cả">Tất cả</option>
                                    <option value="Cán bộ Khoa/Bộ môn">Cán bộ Khoa/Bộ môn</option>
                                    <option value="Giáo viên">Giáo viên</option>
                                    <option value="Sinh viên">Sinh viên</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>

                            <div class="form-group col-sm-3" id="dotThucTapContainer" style="display: none;">
                                <select name="selectDot" id="selectDot" class="form-control">
                                    <option value="">Tất cả đợt</option>
                                    <?php
                                    $stmt = $conn->query("SELECT ID, TenDot FROM dotthuctap WHERE TrangThai >= 1 ORDER BY ID DESC");
                                    while ($dot = $stmt->fetch(PDO::FETCH_ASSOC)):
                                        ?>
                                        <option value="<?= $dot['ID'] ?>"><?= htmlspecialchars($dot['TenDot']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                        <form id="formXoa" method="POST" action="admin/pages/xoathanhvien"
                            onsubmit="return xacNhanXoa();">
                            <div class="form-group col-sm-1">
                                <button type="button" class="btn btn-primary" style="min-width:70px"
                                    onclick="moModalThem()">Thêm</button>
                            </div>
                            <div class="form-group col-sm-1">
                                <button type="submit" class="btn btn-danger" style="min-width:70px">Xóa</button>
                            </div>
                            <div class="col-lg-12">
                                <ul class="nav nav-tabs" id="tabThanhVien">
                                    <li class="active"><a href="#tab-hoatdong" data-toggle="tab">Đang hoạt động</a></li>
                                    <li><a href="#tab-ngung" data-toggle="tab">Ngừng hoạt động</a></li>
                                </ul>
                                <div class="tab-content"
                                    style="background:#fff; border:1px solid #ddd; border-top:0; padding:18px;">
                                    <div class="tab-pane fade in active" id="tab-hoatdong">
                                        <div class="table-responsive" id="wrapHoatDong">
                                            <!-- Bảng Đang hoạt động sẽ được AJAX thay thế -->
                                            <!-- ...bảng hoạt động ở đây... -->
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab-ngung">
                                        <div class="table-responsive" id="wrapNgung">
                                            <!-- Bảng Ngừng hoạt động sẽ được AJAX thay thế -->
                                            <!-- ...bảng ngừng ở đây... -->
                                        </div>
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
        document.querySelector("select[name='selectVaiTro']").addEventListener('change', function () {
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
            $.get('admin/pages/loadthanhvien', { vaiTro: 'Tất cả' }, function (html) {
                let temp = $('<div>').html(html);
                $('#wrapHoatDong').html(temp.find('#tableThanhVienHoatDong').prop('outerHTML'));
                $('#wrapNgung').html(temp.find('#tableThanhVienNgung').prop('outerHTML'));
                $('#tableThanhVienHoatDong').DataTable({ pageLength: 20, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' } });
                $('#tableThanhVienNgung').DataTable({ pageLength: 20, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' } });
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

        $('#selectVaiTro').on('change', function () {
            let vaiTro = $(this).val();
            $.get('admin/pages/loadthanhvien', { vaiTro: vaiTro }, function (html) {
                let temp = $('<div>').html(html);
                $('#wrapHoatDong').html(temp.find('#tableThanhVienHoatDong').prop('outerHTML'));
                $('#wrapNgung').html(temp.find('#tableThanhVienNgung').prop('outerHTML'));
                $('#tableThanhVienHoatDong').DataTable({ pageLength: 20, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' } });
                $('#tableThanhVienNgung').DataTable({ pageLength: 20, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json' } });
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
        $(document).on('click', '.btn-mo-khoa', function () {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Xác nhận mở khóa?',
                text: 'Bạn có chắc muốn mở khóa tài khoản này?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Mở khóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('admin/pages/xulythanhvien', {
                        che_do: 'mo_khoa',
                        id_tai_khoan: id
                    }, function () {
                        window.location.href = 'admin/pages/quanlythanhvien?msg=unlocked';
                    });
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
</body>

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
                        <input type="password" class="form-control" id="matkhau" name="matkhau" required
                            autocomplete="current-password">
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

</html>