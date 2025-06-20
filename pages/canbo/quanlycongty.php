<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý công ty</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
        }
        tr.selected {
            background-color: #007bff !important;
            color: white;
        }
    </style>
</head>
<body>
<div id="wrapper">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
    <div id="page-wrapper">
        <div class="container-fluid">
            <h1 class="page-header">Quản lý công ty</h1>
            <?php
            // Xử lý thêm, sửa, xóa công ty
            $msg = '';
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

            // Thêm công ty
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_congty'])) {
                $ten = trim($_POST['ten_cong_ty'] ?? '');
                $sdt = trim($_POST['sdt'] ?? '');
                $email = trim($_POST['email_cong_ty'] ?? '');
                $masothue = trim($_POST['ma_so_thue'] ?? '');
                $diachi = trim($_POST['dia_chi'] ?? '');

                if ($ten === '' || $sdt === '' || $email === '' || $masothue === '' || $diachi === '') {
                    $msg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $msg = '<div class="alert alert-danger">Email không hợp lệ!</div>';
                } elseif (!preg_match('/^[0-9]{10,15}$/', $sdt)) {
                    $msg = '<div class="alert alert-danger">Số điện thoại không hợp lệ!</div>';
                } else {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM congty WHERE MaSoThue = ?");
                    $stmt->execute([$masothue]);
                    if ($stmt->fetchColumn() > 0) {
                        $msg = '<div class="alert alert-danger">Mã số thuế đã tồn tại!</div>';
                    } else {
                        $stmt = $conn->prepare("INSERT INTO congty (TenCty, MaSoThue, DiaChi, Sdt, Email, TrangThai) VALUES (?, ?, ?, ?, ?, 1)");
                        if ($stmt->execute([$ten, $masothue, $diachi, $sdt, $email])) {
                            header("Location: " . $_SERVER['REQUEST_URI']);
                            exit;
                        } else {
                            $msg = '<div class="alert alert-danger">Thêm công ty thất bại!</div>';
                        }
                    }
                }
            }

            // Sửa công ty
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sua_congty'])) {
                $id = intval($_POST['id_cong_ty'] ?? 0);
                $ten = trim($_POST['ten_cong_ty'] ?? '');
                $sdt = trim($_POST['sdt'] ?? '');
                $email = trim($_POST['email_cong_ty'] ?? '');
                $masothue = trim($_POST['ma_so_thue'] ?? '');
                $diachi = trim($_POST['dia_chi'] ?? '');

                if ($ten === '' || $sdt === '' || $email === '' || $masothue === '' || $diachi === '') {
                    $msg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $msg = '<div class="alert alert-danger">Email không hợp lệ!</div>';
                } elseif (!preg_match('/^[0-9]{10,15}$/', $sdt)) {
                    $msg = '<div class="alert alert-danger">Số điện thoại không hợp lệ!</div>';
                } else {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM congty WHERE MaSoThue = ? AND ID != ?");
                    $stmt->execute([$masothue, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        $msg = '<div class="alert alert-danger">Mã số thuế đã tồn tại!</div>';
                    } else {
                        $stmt = $conn->prepare("UPDATE congty SET TenCty = ?, MaSoThue = ?, DiaChi = ?, Sdt = ?, Email = ? WHERE ID = ?");
                        if ($stmt->execute([$ten, $masothue, $diachi, $sdt, $email, $id])) {
                            header("Location: " . $_SERVER['REQUEST_URI']);
                            exit;
                        } else {
                            $msg = '<div class="alert alert-danger">Cập nhật công ty thất bại!</div>';
                        }
                    }
                }
            }

            // Xóa công ty (ẩn)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_congty'])) {
                $id = intval($_POST['id_cong_ty'] ?? 0);
                if ($id > 0) {
                    $stmt = $conn->prepare("UPDATE congty SET TrangThai = 0 WHERE ID = ?");
                    if ($stmt->execute([$id])) {
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } else {
                        $msg = '<div class="alert alert-danger">Xóa công ty thất bại!</div>';
                    }
                } else {
                    $msg = '<div class="alert alert-danger">Vui lòng chọn công ty để xóa!</div>';
                }
            }

            if ($msg) echo $msg;
            ?>
            <form method="post" autocomplete="off">
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
                        <input type="text" class="form-control" id="ma-so-thue" name="ma_so_thue" placeholder="Mã số thuế" required maxlength="15" minlength="10" pattern="[0-9]{10,15}">
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
            <div class="panel panel-default">
                <div class="panel-heading">
                    Danh sách phiếu giới thiệu thực tập
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="table-dscongty">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tên công ty</th>
                                    <th>Mã số thuế</th>
                                    <th>Địa chỉ</th>
                                    <th>SĐT</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function resetCongTyForm() {
    $('#id_cong_ty').val('');
    $('#ten-cong-ty').val('');
    $('#ma-so-thue').val('');
    $('#dia-chi').val('');
    $('#sdt').val('');
    $('#email-cong-ty').val('');
}

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
});
</script>
</body>
</html>