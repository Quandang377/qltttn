<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sinh viên</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";        
    ?>
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
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div id="wrapper">  
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php";
        ?>
    
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Quản lý danh sách sinh viên</h1>

                <!-- Nút import bằng excel -->
                <div class="text-right" style="margin-bottom: 20px;">
                    <a href="/datn/pages/canbo/importexcel.php" class="btn btn-success">
                        <i class="fa fa-upload"></i> Import bằng Excel
                    </a>
                </div>
                
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_sinh_vien'])) {
                    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                    
                    $ten = trim($_POST['ten']);
                    $mssv = trim($_POST['mssv']);
                    $xep_loai = trim($_POST['xep_loai']);
                    $id_dot = intval($_POST['id_dot']);
                    $id_gvhd = trim($_POST['id_gvhd']);
                    
                    $errors = [];
                    
                    if (empty($ten)) {
                        $errors['ten'] = "Vui lòng nhập tên sinh viên";
                    } elseif (strlen($ten) > 50) {
                        $errors['ten'] = "Tên sinh viên không được quá 50 ký tự";
                    }
                    
                    // Kiểm tra MSSV
                    if (empty($mssv)) {
                        $errors['mssv'] = "Vui lòng nhập MSSV";
                    } elseif (!preg_match('/^[A-Za-z0-9]{8,12}$/', $mssv)) {
                        $errors['mssv'] = "MSSV phải từ 8-12 ký tự và chỉ chứa chữ số, chữ cái";
                    } else {
                        // Kiểm tra trùng MSSV
                        $sql_check = "SELECT COUNT(*) FROM SinhVien WHERE MSSV = :mssv";
                        $stmt_check = $conn->prepare($sql_check);
                        $stmt_check->execute([':mssv' => $mssv]);
                        $count = $stmt_check->fetchColumn();
                        
                        if ($count > 0) {
                            $errors['mssv'] = "MSSV đã tồn tại trong hệ thống";
                        }
                    }
                    
                    $xep_loai_hople = ['Xuất sắc', 'Giỏi', 'Khá', 'Trung bình', 'Yếu'];
                    if (!in_array($xep_loai, $xep_loai_hople)) {
                        $errors['xep_loai'] = "Xếp loại không hợp lệ";
                    }
                    
                    if (empty($errors)) {
                        try {
                            $id_tai_khoan = 'SV' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                            
                            $sql_tk = "INSERT INTO TaiKhoan (ID_TaiKhoan, TaiKhoan, MatKhau, VaiTro, TrangThai) 
                                      VALUES (:id_tk, :tk, :mk, 'Sinh viên', 1)";
                            $stmt_tk = $conn->prepare($sql_tk);
                            $stmt_tk->execute([
                                ':id_tk' => $id_tai_khoan,
                                ':tk' => strtolower($mssv),
                                ':mk' => password_hash($mssv, PASSWORD_DEFAULT)
                            ]);
                            
                            $sql = "INSERT INTO SinhVien (ID_TaiKhoan, ID_Dot, Ten, XepLoai, MSSV, ID_GVHD, TrangThai) 
                                    VALUES (:id_tk, :id_dot, :ten, :xep_loai, :mssv, :id_gvhd, 1)";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([
                                ':id_tk' => $id_tai_khoan,
                                ':id_dot' => $id_dot,
                                ':ten' => $ten,
                                ':xep_loai' => $xep_loai,
                                ':mssv' => $mssv,
                                ':id_gvhd' => $id_gvhd
                            ]);
                            
                            header("Location: " . $_SERVER['REQUEST_URI']);
                            exit;
                        } catch(PDOException $e) {
                            echo '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">Vui lòng kiểm tra lại thông tin nhập!</div>';
                    }
                }
                                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sua_sinh_vien'])) {
                        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

                        $ten = trim($_POST['ten']);
                        $mssv = trim($_POST['mssv']);
                        $xep_loai = trim($_POST['xep_loai']);
                        $id_dot = intval($_POST['id_dot']);
                        $id_gvhd = trim($_POST['id_gvhd']);
                        
                        $errors = [];

                        if (empty($ten)) {
                            $errors['ten'] = "Vui lòng nhập tên sinh viên";
                        }

                        if (empty($mssv)) {
                            $errors['mssv'] = "Vui lòng nhập MSSV";
                        }

                        if (!in_array($xep_loai, ['Xuất sắc', 'Giỏi', 'Khá', 'Trung bình', 'Yếu'])) {
                            $errors['xep_loai'] = "Xếp loại không hợp lệ";
                        }

                        if (empty($errors)) {
                            try {
                                $sql = "UPDATE SinhVien 
                                        SET Ten = :ten, XepLoai = :xep_loai, ID_Dot = :id_dot, ID_GVHD = :id_gvhd
                                        WHERE MSSV = :mssv";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute([
                                    ':ten' => $ten,
                                    ':xep_loai' => $xep_loai,
                                    ':id_dot' => $id_dot,
                                    ':id_gvhd' => $id_gvhd,
                                    ':mssv' => $mssv
                                ]);
                                header("Location: " . $_SERVER['REQUEST_URI']);
                                exit;
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-danger">Vui lòng kiểm tra lại thông tin nhập!</div>';
                        }
                    }
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_sinh_vien'])) {
                            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

                            $mssv = trim($_POST['mssv']);

                            if (empty($mssv)) {
                                echo '<div class="alert alert-danger">Vui lòng chọn sinh viên để xóa.</div>';
                            } else {
                                try {
                                    $sql = "UPDATE SinhVien SET TrangThai = 0 WHERE MSSV = :mssv";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute([':mssv' => $mssv]);

                                    header("Location: " . $_SERVER['REQUEST_URI']);
                                    exit;
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
                                }
                            }
                        }

                ?>
                
                <br>
                <form method="POST" action="">
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label for="ten-sinh-vien">Tên sinh viên *</label>
                            <input type="text" class="form-control" id="ten-sinh-vien" name="ten" placeholder="Tên sinh viên" 
                                   value="<?php echo isset($_POST['ten']) ? htmlspecialchars($_POST['ten']) : ''; ?>" required>
                            <?php if (isset($errors['ten'])): ?>
                                <div class="error-message"><?php echo $errors['ten']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <div class="row">
                        
                        <div class="form-group col-md-3">
                            <label for="mssv">MSSV *</label>
                            <input type="text" class="form-control" id="mssv" name="mssv" placeholder="MSSV" 
                                   value="<?php echo isset($_POST['mssv']) ? htmlspecialchars($_POST['mssv']) : ''; ?>" required>
                            <?php if (isset($errors['mssv'])): ?>
                                <div class="error-message"><?php echo $errors['mssv']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label for="xep_loai">Xếp loại *</label>
                            <select class="form-control" id="xep_loai" name="xep_loai" required>
                                <option value="">-- Chọn xếp loại --</option>
                                <option value="Xuất sắc" <?php echo (isset($_POST['xep_loai']) && $_POST['xep_loai'] === 'Xuất sắc') ? 'selected' : ''; ?>>Xuất sắc</option>
                                <option value="Giỏi" <?php echo (isset($_POST['xep_loai']) && $_POST['xep_loai'] === 'Giỏi') ? 'selected' : ''; ?>>Giỏi</option>
                                <option value="Khá" <?php echo (isset($_POST['xep_loai']) && $_POST['xep_loai'] === 'Khá') ? 'selected' : ''; ?>>Khá</option>
                                <option value="Trung bình" <?php echo (isset($_POST['xep_loai']) && $_POST['xep_loai'] === 'Trung bình') ? 'selected' : ''; ?>>Trung bình</option>
                                <option value="Yếu" <?php echo (isset($_POST['xep_loai']) && $_POST['xep_loai'] === 'Yếu') ? 'selected' : ''; ?>>Yếu</option>
                            </select>
                            <?php if (isset($errors['xep_loai'])): ?>
                                <div class="error-message"><?php echo $errors['xep_loai']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="id_dot">Đợt thực tập *</label>
                            <select class="form-control" id="id_dot" name="id_dot" required>
                                <option value="">-- Chọn đợt thực tập --</option>
                                <?php
                                require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                                $sql_dot = "SELECT ID, TenDot FROM DotThucTap WHERE TrangThai = 1";
                                $stmt_dot = $conn->prepare($sql_dot);
                                $stmt_dot->execute();
                                while ($dot = $stmt_dot->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (isset($_POST['id_dot']) && $_POST['id_dot'] == $dot['ID']) ? 'selected' : '';
                                    echo "<option value='{$dot['ID']}' $selected>{$dot['TenDot']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label for="id_gvhd">Giáo viên hướng dẫn *</label>
                            <select class="form-control" id="id_gvhd" name="id_gvhd" required>
                                <option value="">-- Chọn GVHD --</option>
                                <?php
                                $sql_gv = "SELECT g.ID_TaiKhoan, g.Ten FROM GiaoVien g 
                                          JOIN TaiKhoan t ON g.ID_TaiKhoan = t.ID_TaiKhoan 
                                          WHERE t.TrangThai = 1";
                                $stmt_gv = $conn->prepare($sql_gv);
                                $stmt_gv->execute();
                                while ($gv = $stmt_gv->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (isset($_POST['id_gvhd']) && $_POST['id_gvhd'] == $gv['ID_TaiKhoan']) ? 'selected' : '';
                                    echo "<option value='{$gv['ID_TaiKhoan']}' $selected>{$gv['Ten']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 15px; margin-bottom: 15px;">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <button type="submit" class="btn btn-primary" name="them_sinh_vien">Thêm</button>
                        <button type="submit" class="btn btn-warning" name="sua_sinh_vien">Sửa</button>
                        <button type="submit" class="btn btn-danger" name ="xoa_sinh_vien" id="btn-delete">Xoá</button>
                    </div>
                </div>
                </form>
                
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách sinh viên
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table-dssv">
                                <thead>
                                    <tr>
                                        <th>Tên</th>
                                        <th>MSSV</th>
                                        <th>Xếp loại</th>
                                        <th>Tên đợt</th>
                                        <th>Giáo viên hướng dẫn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                                    try {
                                        $sql = "SELECT 
                                                sv.Ten,
                                                sv.MSSV,
                                                sv.XepLoai,
                                                dt.TenDot AS DotThucTap,
                                                gv.Ten AS TenGVHD
                                            FROM 
                                                SinhVien sv
                                            LEFT JOIN 
                                                DotThucTap dt ON sv.ID_Dot = dt.ID
                                            LEFT JOIN 
                                                GiaoVien gv ON sv.ID_GVHD = gv.ID_TaiKhoan
                                            WHERE 
                                                sv.TrangThai = 1
                                            ";
                                        
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute();
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr>
                                                    <td>".htmlspecialchars($row['Ten'])."</td>
                                                    <td>".htmlspecialchars($row['MSSV'])."</td>
                                                    <td>".htmlspecialchars($row['XepLoai'])."</td>
                                                    <td>".htmlspecialchars($row['DotThucTap'])."</td>
                                                    <td>".htmlspecialchars($row['TenGVHD'])."</td>
                                                </tr>";
                                        }
                                    } catch(PDOException $e) {
                                        echo "<tr><td colspan='8'>Lỗi: " . $e->getMessage() . "</td></tr>";
                                    }
                                    $conn = null;
                                 ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
    <script>
        function clearForm() {
    $('#ten-sinh-vien').val('');
    $('#mssv').val('').prop('readonly', false);
    $('#xep_loai').val('');
    $('#id_dot').val('');
    $('#id_gvhd').val('');
}

$(document).ready(function () {
    var table = $('#table-dssv').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        }
    });

    $('#table-dssv tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
            clearForm();
        } else {
            table.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');

            var data = table.row(this).data();

            $('#ten-sinh-vien').val(data[0]);
            $('#mssv').val(data[1]).prop('readonly', true);
            $('#xep_loai').val($.trim(data[2]));
            $("#id_dot option").filter(function () {
                return $(this).text() === data[3];
            }).prop('selected', true);
            $("#id_gvhd option").filter(function () {
                return $(this).text() === data[4];
            }).prop('selected', true);
        }
    });
});


    </script>
</body>
</html>