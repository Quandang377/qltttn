<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
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
    </style>
    <style>
    #table-dscongty tbody tr {
        cursor: pointer;
    }
    #table-dscongty tbody tr:hover {
        background-color: #f5f5f5;
    }
    #table-dscongty tbody tr.selected {
        background-color: #e6f7ff;
    }
</style>
</head>
<body>
    <div id="wrapper">  
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
            $companyName = '';
            $companyAddress = '';
            $taxCode = '';
            $linhVuc = '';
            $sdt = '';
            $email = '';
            $message = '';

            // Xử lý gửi yêu cầu
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $taxCode = $_POST['ma_so_thue'] ?? '';
                $companyName = $_POST['ten_cong_ty'] ?? '';
                $companyAddress = $_POST['dia_chi'] ?? '';
                $linhVuc = $_POST['linh_vuc'] ?? '';
                $sdt = $_POST['sdt'] ?? '';
                $email = $_POST['email'] ?? '';

                if (isset($_POST['gui_yeu_cau'])) {
                    // Gán tạm thời IdSinhVien = 3
                    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM congty WHERE MaSoThue = ? AND TrangThai = 1");
                $stmtCheck->execute([$taxCode]);
                $companyExists = $stmtCheck->fetchColumn() > 0;

                // Xác định trạng thái dựa trên việc công ty có tồn tại không
                $trangThai = $companyExists ? 1 : 0; // 1: Đã duyệt, 0: Đang chờ
                
                // Thêm thông báo tùy thuộc vào trạng thái
                if ($companyExists) {
                    $message = "Gửi yêu cầu thành công! (Tự động duyệt vì công ty đã có trong hệ thống)";
                } else {
                    $message = "Gửi yêu cầu thành công! Đang chờ duyệt";
                }
                    $stmt = $conn->prepare("INSERT INTO GiayGioiThieu (TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, IdSinhVien, TrangThai) VALUES (?, ?, ?, ?, ?, ?, 3, ?)");
                    if ($stmt->execute([$companyName, $taxCode, $companyAddress, $linhVuc, $sdt, $email,$trangThai])) {
                        $message = "Gửi yêu cầu thành công!";
                        // Xóa input sau khi gửi thành công
                        $companyName = '';
                        $companyAddress = '';
                        $taxCode = '';
                        $linhVuc = '';
                        $sdt = '';
                        $email = '';
                        // Chuyển hướng để tránh gửi lại form khi reload
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    } else {
                        $message = "Gửi yêu cầu thất bại!";
                    }
                }
            }

            // Lấy danh sách giấy giới thiệu từ DB (không xét IdSinhVien)
            $stmt = $conn->prepare("SELECT TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, TrangThai FROM GiayGioiThieu ORDER BY ID DESC");
            $stmt->execute();
            $giayGioiThieuList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Đăng ký giấy giới thiệu</h1>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="post" id="form-giay-gioi-thieu">
                <div class="row">
                    <div class="form-group col-md-6">
                         <label for="ma-so-thue">Nhập mã số thuế</label>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="ma-so-thue" name="ma_so_thue" placeholder="Mã số thuế" value="<?php echo htmlspecialchars($taxCode); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary btn-block" id="btn-cap-nhat" style="padding: 10px;display: flex; align-items: center; justify-content: center;">Cập nhật</button>
                            </div>
                        </div>
                         <div class="row">
                    <div class="form-group col-md-6">
                        <label for="ten-cong-ty">Tên công ty</label>
                        <input type="text" class="form-control" id="ten-cong-ty" name="ten_cong_ty" placeholder="Tên công ty" value="<?php echo htmlspecialchars($companyName); ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="dia-chi">Địa chỉ</label>
                        <input type="text" class="form-control" id="dia-chi" name="dia_chi" placeholder="Địa chỉ" value="<?php echo htmlspecialchars($companyAddress); ?>" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="sdt">SĐT</label>
                        <input type="text" class="form-control" id="sdt" name="sdt" placeholder="Số điện thoại" value="<?php echo htmlspecialchars($sdt); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="linh-vuc">Lĩnh vực</label>
                        <input type="text" class="form-control" id="linh-vuc" name="linh_vuc" placeholder="Lĩnh vực" value="<?php echo htmlspecialchars($linhVuc); ?>">
                    </div>
                </div>
                
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-6 text-center">
                        <button type="submit" name="gui_yeu_cau" class="btn btn-success">Gửi yêu cầu</button>
                    </div>
                </div>
                    </div>
                <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách phiếu giới thiệu thực tập
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <?php if (count($giayGioiThieuList) > 0): ?>
                                <table class="table table-striped table-bordered" id="table-ds-giay-gioi-thieu">
                                    <thead>
                                        <tr>
                                            <th>Tên công ty</th>
                                            <th>Mã số thuế</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($giayGioiThieuList as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['TenCty']); ?></td>
                                                <td><?php echo htmlspecialchars($row['MaSoThue']); ?></td>
                                                <td>
                                                    <?php
                                                        if ($row['TrangThai'] == 0) echo "Đang chờ duyệt";
                                                        elseif ($row['TrangThai'] == 1) echo "Đã duyệt";
                                                        else echo "Từ chối";
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center" style="padding:20px;">Không có giấy giới thiệu</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
                
                </form>    
            </div>
            <div class="col md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Danh sách công ty thực tập
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
    
       <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    <script> 
    $(document).ready(function () {
        $('#table-dscongty').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });

        // Thêm sự kiện click vào các hàng trong bảng công ty
        $('#table-dscongty tbody').on('click', 'tr', function() {
            // Xóa class selected từ tất cả các hàng
            $('#table-dscongty tbody tr').removeClass('selected');
            // Thêm class selected vào hàng được chọn
            $(this).addClass('selected');
            
            // Lấy dữ liệu từ hàng được chọn
            const rowData = $('#table-dscongty').DataTable().row(this).data();
            
            if (rowData) {
                // Điền thông tin vào form
                $('#ma-so-thue').val(rowData[2]); // Mã số thuế
                $('#ten-cong-ty').val(rowData[1]); // Tên công ty
                $('#dia-chi').val(rowData[3]); // Địa chỉ
                $('#sdt').val(rowData[4]); // SĐT
                $('#email').val(rowData[5]); // Email
                
                // Cuộn lên đầu form để người dùng thấy thông tin đã được điền
                $('html, body').animate({
                    scrollTop: $('#form-giay-gioi-thieu').offset().top
                }, 500);
            }
        });
    });
</script>
   <script src="/datn/api/getapi.js"></script>
<script>
$(document).ready(function () {
    $('#btn-cap-nhat').on('click', async function () {
        const taxCode = $('#ma-so-thue').val().trim();

        if (!taxCode) {
            alert("Vui lòng nhập mã số thuế");
            return;
        }

        const info = await getBusinessInfoByTaxCode(taxCode);

        if (info) {
            console.log("Thông tin doanh nghiệp:", info);

            // Cập nhật vào các input
            $('#ten-cong-ty').val(info.name || info.shortName || ''); // Sử dụng cả name và tenCongTy
            $('#dia-chi').val(info.address || info.diaChi || '');
            $('#linh-vuc').val(info.businessLine || info.linhVuc || '');
            $('#sdt').val(info.phone || info.soDienThoai || '');
            $('#email').val(info.email || '');
            
            // Kiểm tra console log để xem dữ liệu nhận được
            console.log("Tên công ty:", info.name || info.tenCongTy);
        } else {
            alert("Không tìm thấy thông tin doanh nghiệp hoặc API bị lỗi.");
        }
    });
});
</script>
</body>
</html>