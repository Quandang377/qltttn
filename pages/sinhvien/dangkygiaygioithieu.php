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
                    $stmt = $conn->prepare("INSERT INTO GiayGioiThieu (TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, IdSinhVien, TrangThai) VALUES (?, ?, ?, ?, ?, ?, 3, 0)");
                    if ($stmt->execute([$companyName, $taxCode, $companyAddress, $linhVuc, $sdt, $email])) {
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
                    <div class="form-group col-md-4">
                         <label for="ma-so-thue">Nhập mã số thuế</label>
                        <div class="row">
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="ma-so-thue" name="ma_so_thue" placeholder="Mã số thuế" value="<?php echo htmlspecialchars($taxCode); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary btn-block" id="btn-cap-nhat" style="display: flex; align-items: center; justify-content: center;">Cập nhật</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script src="/datn/api/getapi.js"></script>
                <script>
                    $(document).ready(function () {
                        // Sự kiện khi nhấn nút "Cập nhật"
                        $('#btn-cap-nhat').on('click', async function () {
                            const taxCode = $('#ma-so-thue').val().trim();
                            if (!taxCode) {
                                alert('Vui lòng nhập mã số thuế');
                                return;
                            }
                            // Gọi hàm lấy thông tin doanh nghiệp từ getapi.js
                            const info = await getBusinessInfoByTaxCode(taxCode);
                            if (info) {
                                $('#ten-cong-ty').val(info.shortName || '');
                                $('#dia-chi').val(info.address || '');
                                $('#linh-vuc').val(info.field || '');
                                $('#sdt').val(info.phone || '');
                                $('#email').val(info.email || '');
                            } else {
                                $('#ten-cong-ty').val('');
                                $('#dia-chi').val('');
                                $('#linh-vuc').val('');
                                $('#sdt').val('');
                                $('#email').val('');
                                alert('Không tìm thấy thông tin doanh nghiệp');
                            }
                        });
                    });
                </script>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="ten-cong-ty">Tên công ty</label>
                        <input type="text" class="form-control" id="ten-cong-ty" name="ten_cong_ty" placeholder="Tên công ty" value="<?php echo htmlspecialchars($companyName); ?>" readonly>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="dia-chi">Địa chỉ</label>
                        <input type="text" class="form-control" id="dia-chi" name="dia_chi" placeholder="Địa chỉ" value="<?php echo htmlspecialchars($companyAddress); ?>" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="sdt">SĐT</label>
                        <input type="text" class="form-control" id="sdt" name="sdt" placeholder="Số điện thoại" value="<?php echo htmlspecialchars($sdt); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="linh-vuc">Lĩnh vực</label>
                        <input type="text" class="form-control" id="linh-vuc" name="linh_vuc" placeholder="Lĩnh vực" value="<?php echo htmlspecialchars($linhVuc); ?>">
                    </div>
                </div>
                
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <button type="submit" name="gui_yeu_cau" class="btn btn-success">Gửi yêu cầu</button>
                    </div>
                </div>
                </form>

                <br>

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
                                            <th>Địa chỉ</th>
                                            <th>Lĩnh vực</th>
                                            <th>SĐT</th>
                                            <th>Email</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($giayGioiThieuList as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['TenCty']); ?></td>
                                                <td><?php echo htmlspecialchars($row['MaSoThue']); ?></td>
                                                <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['LinhVuc']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Sdt']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
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
        </div>
    </div>
    <script> 
        $(document).ready(function () {
            $('#table-ds-giay-gioi-thieu').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                }
            });
        });
    </script>
</body>
</html>