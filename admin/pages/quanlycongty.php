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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .page-header {
            color: #2c3e50;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
            border: none;
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .form-section h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
            transform: translateY(-1px);
        }
        
        .btn {
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            color: white;
        }
        
        .table-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-section .panel-heading {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 20px 25px;
            margin: 0;
            font-weight: 600;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-section .panel-body {
            padding: 25px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            border: none;
            padding: 15px;
            font-weight: 600;
            color: #2c3e50;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            padding: 15px;
            border-color: #e9ecef;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        tr.selected {
            background: linear-gradient(45deg, #007bff, #0056b3) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            font-weight: 500;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .alert-danger {
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .alert-success {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .action-buttons {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            #page-wrapper {
                padding: 15px;
            }
            
            .main-card {
                padding: 20px;
            }
            
            .form-section {
                padding: 15px;
            }
        }
        
        /* Animation cho loading */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        /* Custom scrollbar */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #0056b3;
        }
        
        /* Notification styles */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .notification {
            margin-bottom: 10px;
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            font-weight: 500;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        }
        
        .notification.success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-left: 4px solid #155724;
        }
        
        .notification.error {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
            border-left: 4px solid #721c24;
        }
        
        .notification.info {
            background: linear-gradient(45deg, #17a2b8, #20c997);
            color: white;
            border-left: 4px solid #0c5460;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification.fade-out {
            animation: slideOutRight 0.3s ease-in;
        }
    </style>
</head>
<body>
<div id="wrapper">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
    
    <!-- Notification Container -->
    <div class="notification-container" id="notification-container"></div>
    
    <div id="page-wrapper">
        <div class="container-fluid">
            <h1 class="page-header">
                <i class="fa fa-building"></i> Quản lý công ty
            </h1>
            
            <div class="main-card">
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
            
            <div class="form-section">
                <h4><i class="fa fa-plus-circle"></i> Thông tin công ty</h4>
                <form method="post" autocomplete="off" id="company-form">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="ten-cong-ty"><i class="fa fa-building"></i> Tên công ty</label>
                                    <input type="text" class="form-control" id="ten-cong-ty" name="ten_cong_ty" placeholder="Nhập tên công ty" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="sdt"><i class="fa fa-phone"></i> Số điện thoại</label>
                                    <input type="text" class="form-control" id="sdt" name="sdt" placeholder="Nhập số điện thoại" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="email-cong-ty"><i class="fa fa-envelope"></i> Email</label>
                                    <input type="email" class="form-control" id="email-cong-ty" name="email_cong_ty" placeholder="Nhập email công ty" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="ma-so-thue"><i class="fa fa-id-card"></i> Mã số thuế</label>
                            <input type="text" class="form-control" id="ma-so-thue" name="ma_so_thue" placeholder="Nhập mã số thuế" required maxlength="15" minlength="10" pattern="[0-9]{10,15}">
                        </div>
                        <div class="col-md-6">
                            <label for="dia-chi"><i class="fa fa-map-marker"></i> Địa chỉ</label>
                            <textarea class="form-control" maxlength="200" id="dia-chi" name="dia_chi" rows="4" style="resize: none;" placeholder="Nhập địa chỉ công ty" required></textarea>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <div class="row">
                            <div class="col-md-3">
                                <button type="submit" name="them_congty" class="btn btn-primary btn-block">
                                    <i class="fa fa-plus"></i> Thêm mới
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="sua_congty" class="btn btn-warning btn-block">
                                    <i class="fa fa-pencil"></i> Cập nhật
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="xoa_congty" class="btn btn-danger btn-block" onclick="return confirm('Bạn có chắc chắn muốn xóa công ty này?')">
                                    <i class="fa fa-trash"></i> Xóa
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-secondary btn-block" onclick="resetCongTyForm()">
                                    <i class="fa fa-refresh"></i> Làm mới
                                </button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="id_cong_ty" name="id_cong_ty" value="">
                </form>
            </div>
            
            <div class="table-section">
                <div class="panel-heading">
                    <i class="fa fa-list"></i> Danh sách công ty
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="table-dscongty">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Tên công ty</th>
                                    <th style="width: 130px;">Mã số thuế</th>
                                    <th>Địa chỉ</th>
                                    <th style="width: 130px;">Số điện thoại</th>
                                    <th style="width: 200px;">Email</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT ID, TenCty, MaSoThue, DiaChi, Sdt, Email FROM congty WHERE TrangThai = 1");
                            $stmt->execute();
                            $stt = 1;
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr style="cursor: pointer;">';
                                echo '<td data-id="' . $row['ID'] . '"><span class="badge badge-primary">' . $stt++ . '</span></td>';
                                echo '<td><strong>' . htmlspecialchars($row['TenCty']) . '</strong></td>';
                                echo '<td><span class="text-info">' . htmlspecialchars($row['MaSoThue']) . '</span></td>';
                                echo '<td>' . htmlspecialchars($row['DiaChi']) . '</td>';
                                echo '<td><i class="fa fa-phone text-success"></i> ' . htmlspecialchars($row['Sdt']) . '</td>';
                                echo '<td><i class="fa fa-envelope text-primary"></i> ' . htmlspecialchars($row['Email']) . '</td>';
                                echo '</tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            </div> <!-- Đóng main-card -->
        </div>
    </div>
</div>
 <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
<script>
function resetCongTyForm() {
    $('#id_cong_ty').val('');
    $('#ten-cong-ty').val('');
    $('#ma-so-thue').val('');
    $('#dia-chi').val('');
    $('#sdt').val('');
    $('#email-cong-ty').val('');
    
    // Remove selected class from table rows
    $('#table-dscongty tbody tr').removeClass('selected');
    
    // Show success message
    showNotification('Đã làm mới form thành công!', 'success');
}

function showNotification(message, type) {
    const notificationId = 'notification-' + Date.now();
    const typeClass = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info');
    
    const notification = `
        <div class="notification ${typeClass}" id="${notificationId}">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="fa ${type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle')}" style="margin-right: 8px;"></i>
                    ${message}
                </div>
                <button type="button" onclick="closeNotification('${notificationId}')" style="background: none; border: none; color: inherit; font-size: 18px; cursor: pointer; padding: 0; margin-left: 10px;">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    $('#notification-container').append(notification);
    
    // Auto remove after 4 seconds
    setTimeout(function() {
        closeNotification(notificationId);
    }, 4000);
}

function closeNotification(notificationId) {
    const notification = $('#' + notificationId);
    if (notification.length) {
        notification.addClass('fade-out');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }
}

$(document).ready(function () {
    var table = $('#table-dscongty').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        },
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: 0 }
        ]
    });

    // Enhanced row selection with animation
    $('#table-dscongty tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
            resetCongTyForm();
            return;
        }
        
        table.$('tr.selected').removeClass('selected');
        $(this).addClass('selected');
        
        var data = table.row(this).data();
        var id = $(this).find('td').eq(0).attr('data-id');
        
        // Animate form population
        $('#company-form').addClass('loading');
        
        setTimeout(function() {
            $('#id_cong_ty').val(id || '');
            $('#ten-cong-ty').val(data[1].replace(/<[^>]*>/g, ''));
            $('#ma-so-thue').val(data[2].replace(/<[^>]*>/g, ''));
            $('#dia-chi').val(data[3]);
            $('#sdt').val(data[4].replace(/<[^>]*>/g, ''));
            $('#email-cong-ty').val(data[5].replace(/<[^>]*>/g, ''));
            
            $('#company-form').removeClass('loading');
            
            // Show info notification
            showNotification('Đã chọn công ty: ' + data[1].replace(/<[^>]*>/g, ''), 'info');
        }, 200);
    });
    
    // Form validation enhancement
    $('#company-form').on('submit', function(e) {
        const phone = $('#sdt').val();
        const email = $('#email-cong-ty').val();
        const taxCode = $('#ma-so-thue').val();
        const companyName = $('#ten-cong-ty').val();
        
        if (!/^[0-9]{10,15}$/.test(phone)) {
            e.preventDefault();
            showNotification('Số điện thoại phải có 10-15 chữ số!', 'error');
            return false;
        }
        
        if (!/^[0-9]{10,15}$/.test(taxCode)) {
            e.preventDefault();
            showNotification('Mã số thuế phải có 10-15 chữ số!', 'error');
            return false;
        }
        
        // Show processing notification
        const actionType = $(this).find('button[type="submit"]:focus').attr('name') || 
                          $('button[type="submit"][name]:last').attr('name');
        
        let actionText = 'Đang xử lý...';
        if (actionType === 'them_congty') actionText = 'Đang thêm công ty...';
        else if (actionType === 'sua_congty') actionText = 'Đang cập nhật công ty...';
        else if (actionType === 'xoa_congty') actionText = 'Đang xóa công ty...';
        
        showNotification(actionText, 'info');
    });
    
    // Add hover effects to buttons
    $('.btn').hover(function() {
        $(this).addClass('btn-hover');
    }, function() {
        $(this).removeClass('btn-hover');
    });
});
</script>
</body>
</html>