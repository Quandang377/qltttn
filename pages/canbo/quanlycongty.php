<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý công ty</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
<<<<<<< HEAD
      <style>
=======
    <style>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
    }
<<<<<<< HEAD
</style>
=======
    tr.selected {
    background-color: #007bff !important;
    color: white;
}
    </style>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
</head>
<body>
    <div id="wrapper">
        <?php
<<<<<<< HEAD
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Quản lý công ty</h1>
                <div class="row">
                    <div class="form-group col-md-12">
                         
                        <div class="row">
                            <div class="col-md-4">
                                <label for="ma-so-thue">Tên công ty</label>
                                <input type="text" class="form-control" id="ten-cong-ty" placeholder="Tên công ty">
                            </div>
                            <div class="col-md-4">
                                <label for="ma-so-thue">SĐT</label>
                                <input type="text" class="form-control" id="sdt" placeholder="SĐt">
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
                
                <div class="row">
                            <div class="col-md-4">
                                <label for="ma-so-thue">Mã số thuế</label>
                                <input type="text" class="form-control" id="ma-so-thue" placeholder="Mã số thuế">
                            </div>
                            <div class="col-md-4">
                                <label for="ma-so-thue">Địa chỉ</label>
                                <textarea class="form-control" maxlength="200" id="dia-chi" rows="4" style="resize: none;" placeholder="Địa chỉ"></textarea>
                            </div>
                        </div>
                
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-plus" style="margin-right: 5px;"></i> Thêm
                            </button>
                    </div>
                    <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-trash-o" style="margin-right: 5px;"></i> Xóa
                            </button>
                    </div>
                    <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-pencil" style="margin-right: 5px;"></i> Sửa
                            </button>
                    </div><div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button"  class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-search" style="margin-right: 5px;"></i> Duyệt
                            </button>
                    </div>

                </div>

                <br>

=======
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
        ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Quản lý công ty</h1>
                <?php
                // Xử lý thêm công ty
                $msg = '';
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_congty'])) {
                    $ten = trim($_POST['ten_cong_ty'] ?? '');
                    $sdt = trim($_POST['sdt'] ?? '');
                    $email = trim($_POST['email_cong_ty' ?? '']);
                    $masothue = trim($_POST['ma_so_thue'] ?? '');
                    $diachi = trim($_POST['dia_chi' ?? '']);

                    // Kiểm tra dữ liệu
                    if ($ten === '' || $sdt === '' || $email === '' || $masothue === '' || $diachi === '') {
                        $msg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $msg = '<div class="alert alert-danger">Email không hợp lệ!</div>';
                    } elseif (!preg_match('/^[0-9]{10,15}$/', $sdt)) {
                        $msg = '<div class="alert alert-danger">Số điện thoại không hợp lệ!</div>';
                    } else {
                        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                        // Kiểm tra trùng mã số thuế
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM congty WHERE MaSoThue = ?");
                        $stmt->execute([$masothue]);
                        if ($stmt->fetchColumn() > 0) {
                            $msg = '<div class="alert alert-danger">Mã số thuế đã tồn tại!</div>';
                        } else {
                            // Thực hiện thêm công ty
                            $stmt = $conn->prepare("INSERT INTO congty (TenCty, MaSoThue, DiaChi, Sdt, Email, TrangThai) VALUES (?, ?, ?, ?, ?, 1)");
                            if ($stmt->execute([$ten, $masothue, $diachi, $sdt, $email])) {
                                $msg = '<div class="alert alert-success">Thêm công ty thành công!</div>';
                            } else {
                                $msg = '<div class="alert alert-danger">Thêm công ty thất bại!</div>';
                            }
                        }
                    }
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sua_congty'])) {
                    $id = intval($_POST['id_cong_ty']);
                    $ten = trim($_POST['ten_cong_ty'] ?? '');
                    $sdt = trim($_POST['sdt'] ?? '');
                    $email = trim($_POST['email_cong_ty' ?? '']);
                    $masothue = trim($_POST['ma_so_thue'] ?? '');
                    $diachi = trim($_POST['dia_chi' ?? '']);

                    // Kiểm tra dữ liệu
                    if ($ten === '' || $sdt === '' || $email === '' || $masothue === '' || $diachi === '') {
                        $msg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $msg = '<div class="alert alert-danger">Email không hợp lệ!</div>';
                    } elseif (!preg_match('/^[0-9]{10,15}$/', $sdt)) {
                        $msg = '<div class="alert alert-danger">Số điện thoại không hợp lệ!</div>';
                    } else {
                        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                        // Kiểm tra trùng mã số thuế (trừ chính nó)
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM congty WHERE MaSoThue = ? AND ID != ?");
                        $stmt->execute([$masothue, $id]);
                        if ($stmt->fetchColumn() > 0) {
                            $msg = '<div class="alert alert-danger">Mã số thuế đã tồn tại!</div>';
                        } else {
                            // Thực hiện cập nhật
                            $stmt = $conn->prepare("UPDATE congty SET TenCty = ?, MaSoThue = ?, DiaChi = ?, Sdt = ?, Email = ? WHERE ID = ?");
                            if ($stmt->execute([$ten, $masothue, $diachi, $sdt, $email, $id])) {
                                $msg = '<div class="alert alert-success">Cập nhật công ty thành công!</div>';
                            } else {
                                $msg = '<div class="alert alert-danger">Cập nhật công ty thất bại!</div>';
                            }
                        }
                    }
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_congty'])) {
                    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                    
                    $id = intval($_POST['id_cong_ty'] ?? 0);
                    if ($id > 0) {
                        $stmt = $conn->prepare("UPDATE congty SET TrangThai = 0 WHERE ID = ?");
                        if ($stmt->execute([$id])) {
                            $msg = '<div class="alert alert-success">Đã xóa công ty thành công!</div>';
                        } else {
                            $msg = '<div class="alert alert-danger">Xóa công ty thất bại!</div>';
                        }
                    } else {
                        $msg = '<div class="alert alert-danger">Vui lòng chọn công ty để xóa!</div>';
                    }
                }

                if ($msg) echo $msg;
                ?>
                <form method="post">
                <div class="row">
                    <div class="form-group col-md-12">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="ten-cong-ty">Tên công ty</label>
                                <input type="text" class="form-control" id="ten-cong-ty" name="ten_cong_ty" placeholder="Tên công ty" required>
                            </div>
                            <div class="col-md-4">
                                <label for="sdt">SĐT</label>
                                <input type="text" class="form-control" id="sdt" name="sdt" placeholder="SĐT" required>
                            </div>
                            <div class="col-md-4">
                                <label for="email-cong-ty">Email</label>
                                <input type="email" class="form-control" id="email-cong-ty" name="email_cong_ty" placeholder="Email công ty" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                        <div class="col-md-4">
                            <label for="ma-so-thue">Mã số thuế</label>
                            <input type="text" class="form-control" id="ma-so-thue" name="ma_so_thue" placeholder="Mã số thuế" required>
                        </div>
                        <div class="col-md-4">
                            <label for="dia-chi">Địa chỉ</label>
                            <textarea class="form-control" maxlength="200" id="dia-chi" name="dia_chi" rows="4" style="resize: none;" placeholder="Địa chỉ" required></textarea>
                        </div>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" name="them_congty" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-plus" style="margin-right: 5px;"></i> Thêm
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" name="xoa_congty" class="btn btn-danger btn-block" style="display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-trash-o" style="margin-right: 5px;"></i> Xóa
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" name="sua_congty" class="btn btn-warning btn-block" style="display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-pencil" style="margin-right: 5px;"></i> Sửa
                        </button>
                    </div>
                </div>
                <input type="hidden" id="id_cong_ty" name="id_cong_ty" value="">
                </form>
                <br>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách phiếu giới thiệu thực tập
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
<<<<<<< HEAD
                            <table class="table table-striped table-bordered">
=======
                            <table class="table table-striped table-bordered" id="table-dscongty">
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên công ty</th>
                                        <th>Mã số thuế</th>
                                        <th>Địa chỉ</th>
                                        <th>SĐT</th>
<<<<<<< HEAD
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr><tr>
                                        <td>1</td>
                                        <td>VNG</td>
                                        <td>0303490096</td>
                                        <td>Z06 Đường số 13, Phường Tân Thuận Đông, Quận 7, TP. Hồ Chí Minh</td>
                                        <td>093333333333</td>
                                    </tr>
=======
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$stmt = $conn->prepare("SELECT ID, TenCty, MaSoThue, DiaChi, Sdt, Email FROM congty WHERE TrangThai = 1");
$stmt->execute();
$stt = 1;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<tr>';
    echo '<td data-id="' . $row['ID'] . '">' . $stt++ . '</td>';
    echo '<td>' . htmlspecialchars($row['TenCty']) . '</td>';
    echo '<td>' . htmlspecialchars($row['MaSoThue']) . '</td>';
    echo '<td>' . htmlspecialchars($row['DiaChi']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Sdt']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
    echo '</tr>';
}
?>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
<<<<<<< HEAD
    document.getElementById('dia-chi').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') e.preventDefault();
    });
</script>
=======
    $(document).ready(function () {
        var table = $('#table-dscongty').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });

        $('#table-dscongty tbody').on('click', 'tr', function () {
            table.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
            var data = table.row(this).data();
            var id = $(this).find('td').eq(0).attr('data-id');
            $('#id_cong_ty').val(id || '');
            $('#ten-cong-ty').val(data[1]);
            $('#ma-so-thue').val(data[2]);
            $('#dia-chi').val(data[3]);
            $('#sdt').val(data[4]);
            $('#email-cong-ty').val(data[5]);
        });
        // Khi nhấn nút Thêm, reset form và bỏ nổi bật dòng
        $('button[name="them_congty"]').on('click', function() {
            $('#id_cong_ty').val('');
            $('#ten-cong-ty').val('');
            $('#ma-so-thue').val('');
            $('#dia-chi').val('');
            $('#sdt').val('');
            $('#email-cong-ty').val('');
            table.$('tr.selected').removeClass('selected');
        });
    });
    </script>
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
</body>
</html>