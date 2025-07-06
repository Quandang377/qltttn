<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        /* === RESET & BASE === */
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        
        /* === LAYOUT === */
        #page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
            text-shadow: 0 2px 4px rgba(30, 64, 175, 0.1);
        }
        
        /* === SEARCH BAR === */
        .search-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-bar input {
            flex: 1;
            min-width: 250px;
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            transition: all 0.2s ease;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }
        
        .btn-info:hover {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 16px 20px;
            font-size: 16px;
            font-weight: 600;
            border-bottom: none;
        }
        
        .card-body {
            padding: 20px;
            min-height: 300px;
        }
        
        .card-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 12px 20px;
        }
        .company-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 20px;
            padding: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            min-height: 120px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .company-panel:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }
        
        .company-panel .font-weight-bold {
            font-size: 16px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .company-panel .company-info {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 4px;
        }
        
        .company-panel .company-info strong {
            color: #475569;
        }
        .company-list-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 15px 0;
        }
        
        .company-list-pagination button {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .company-list-pagination button:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
            color: #3b82f6;
        }
        
        .company-list-pagination button.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        
        .company-list-pagination span {
            color: #6b7280;
            font-weight: 500;
            padding: 0 8px;
        }
        /* === MODALS === */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
            padding: 16px 20px;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 16px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 12px 12px;
            padding: 12px 20px;
        }
        
        /* Notify modal đặc biệt */
        #notifyModal .modal-content {
            border-radius: 16px;
            max-width: 400px;
            margin: auto;
        }
        
        #notifyModal .modal-body {
            text-align: center;
            padding: 30px 20px 20px;
        }
        
        #notifyModal .modal-footer {
            border: none;
            background: white;
            justify-content: center;
            padding: 10px 20px 25px;
        }
        
        #notifyModalLabel {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        #notifyModalBody {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        .btn-close-modal {
            background: #f3f4f6;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #6b7280;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-close-modal:hover {
            background: #e5e7eb;
            color: #374151;
        }
        /* === FORM CONTROLS === */
        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 1199px) {
            .col-xl-8 {
                order: 1;
            }
            .col-xl-4 {
                order: 2;
            }
        }
        
        @media (max-width: 991px) {
            .page-header h1 {
                font-size: 1.75rem;
            }
            
            .search-bar {
                flex-direction: column;
                gap: 12px;
            }
            
            .search-bar input {
                min-width: 100%;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .company-panel {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .giay-panel {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .col-lg-7, .col-lg-5 {
                order: 1;
            }
            
            #company-panel-list .col-md-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        @media (max-width: 767px) {
            #company-panel-list .col-md-3,
            #company-panel-list .col-sm-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            #page-wrapper {
                padding: 15px;
            }
            
            .page-header {
                margin-bottom: 20px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .search-bar {
                padding: 15px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 14px;
            }
        }
        .giay-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 15px;
            padding: 16px;
            transition: all 0.2s ease;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .giay-panel:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);
        }
        
        .giay-panel .tencty {
            font-size: 15px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .giay-panel .trangthai {
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .giay-panel .dot-info {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .giay-list-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 10px 0;
        }
        
        .giay-list-pagination button {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 12px;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .giay-list-pagination button:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
            color: #3b82f6;
        }
        
        .giay-list-pagination button.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        
        .giay-list-pagination span {
            color: #6b7280;
            font-weight: 500;
            padding: 0 4px;
            font-size: 12px;
        }
        @media (max-width: 991px) {
            .page-header h1 {
                font-size: 1.75rem;
            }
            
            .search-bar {
                flex-direction: column;
                gap: 12px;
            }
            
            .search-bar input {
                min-width: 100%;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .company-panel {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .giay-panel {
                padding: 12px;
                margin-bottom: 12px;
            }
        }
        
        @media (max-width: 576px) {
            #page-wrapper {
                padding: 15px;
            }
            
            .page-header {
                margin-bottom: 20px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .modal-dialog {
                margin: 10px;
            }
        }

        #company-panel-list > .col-md-3:last-child .company-panel,
        #company-panel-list > .col-sm-6:last-child .company-panel {
            margin-bottom: 0 !important;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";

            $message = '';
            $messageType = 'success';

            // Lấy ID sinh viên từ session
            $idSinhVien = $_SESSION['user']['ID_TaiKhoan'] ?? 3;
            
            // Lấy thông tin đợt thực tập của sinh viên
            $stmt = $conn->prepare("SELECT ID_Dot FROM SinhVien WHERE ID_TaiKhoan = ?");
            $stmt->execute([$idSinhVien]);
            $sinhVienInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            $idDot = $sinhVienInfo['ID_Dot'] ?? null;
            
            // Lấy thông tin chi tiết đợt thực tập
            $dotThucTapInfo = null;
            if ($idDot) {
                $stmt = $conn->prepare("SELECT TenDot, ThoiGianBatDau, ThoiGianKetThuc FROM DotThucTap WHERE ID = ?");
                $stmt->execute([$idDot]);
                $dotThucTapInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_panel'])) {
                $taxCode = trim($_POST['ma_so_thue']);
                $name = trim($_POST['ten_cong_ty']);
                $address = trim($_POST['dia_chi']);
                $field = trim($_POST['linh_vuc']);
                $phone = trim($_POST['sdt']);
                $email = trim($_POST['email']);

                // Kiểm tra dữ liệu phía server
                if (!$taxCode || !$name || !$address || !$field || !$phone || !$email) {
                    $message = 'Vui lòng nhập đầy đủ tất cả các trường!';
                    $messageType = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email không hợp lệ!';
                    $messageType = 'danger';
                } elseif (!preg_match('/^[0-9\-\+\s]{8,}$/', $phone)) {
                    $message = 'Số điện thoại không hợp lệ!';
                    $messageType = 'danger';
                } else {
                    try {
                        $stmt = $conn->prepare("INSERT INTO giaygioithieu (TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, IdSinhVien, id_dot, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                        $stmt->execute([$name, $taxCode, $address, $field, $phone, $email, $idSinhVien, $idDot]);
                        $message = 'Đã gửi phiếu đăng ký thực tập, vui lòng chờ duyệt!';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Có lỗi xảy ra khi lưu dữ liệu: ' . $e->getMessage();
                        $messageType = 'danger';
                    }
                }
            }

            // Lấy danh sách công ty
            $stmt = $conn->prepare("SELECT ID, TenCty, MaSoThue, DiaChi, Sdt, Email, Linhvuc FROM congty WHERE TrangThai = 1");
            $stmt->execute();
            $companyList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lấy danh sách giấy giới thiệu của sinh viên với thông tin đợt thực tập
            $stmt = $conn->prepare("
                SELECT g.ID, g.TenCty, g.MaSoThue, g.DiaChi, g.Sdt, g.Email, g.LinhVuc, g.TrangThai,
                       d.TenDot, d.ThoiGianBatDau, d.ThoiGianKetThuc
                FROM giaygioithieu g
                LEFT JOIN DotThucTap d ON g.id_dot = d.ID
                WHERE g.IdSinhVien = ?
                ORDER BY g.ID DESC
            ");
            $stmt->execute([$idSinhVien]);
            $giayList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Đăng ký giấy giới thiệu</h1>
                
                <!-- Thông tin đợt thực tập -->
                <?php if ($dotThucTapInfo): ?>
                    <div class="alert alert-info" style="border-radius: 12px; background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); border: 1px solid #b8daff;">
                        <i class="fa fa-info-circle"></i>
                        <strong>Đợt thực tập:</strong> <?php echo htmlspecialchars($dotThucTapInfo['TenDot']); ?>
                        <?php if ($dotThucTapInfo['ThoiGianBatDau'] && $dotThucTapInfo['ThoiGianKetThuc']): ?>
                            | <strong>Thời gian:</strong> 
                            <?php echo date('d/m/Y', strtotime($dotThucTapInfo['ThoiGianBatDau'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($dotThucTapInfo['ThoiGianKetThuc'])); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" style="border-radius: 12px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 1px solid #ffeaa7;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Chưa có đợt thực tập:</strong> Bạn chưa được phân công vào đợt thực tập nào. Vui lòng liên hệ giáo viên hướng dẫn.
                    </div>
                <?php endif; ?>

                <!-- Modal thông báo -->
                <div class="modal fade" id="notifyModal" tabindex="-1" role="dialog" aria-labelledby="notifyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px;">
                        <div class="modal-content">
                            <div class="modal-body">
                                <div id="notifyIcon" class="text-center mb-3"></div>
                                <h5 class="modal-title text-center" id="notifyModalLabel"></h5>
                                <div id="notifyModalBody" class="text-center"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-close-modal mx-auto" data-dismiss="modal" aria-label="Đóng">
                                    &times;
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('.modal').modal('hide');
        setTimeout(function() {
            var modal = $('#notifyModal');
            var type = '<?= $messageType ?>';
            let iconHtml = '';
            if(type === 'success') {
                iconHtml = `<div>
                    <svg width="60" height="60" fill="none" viewBox="0 0 60 60">
                        <circle cx="30" cy="30" r="30" fill="#d1fae5"/>
                        <path d="M18 32l8 8 16-20" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>`;
            } else {
                iconHtml = `<div>
                    <svg width="60" height="60" fill="none" viewBox="0 0 60 60">
                        <circle cx="30" cy="30" r="30" fill="#fee2e2"/>
                        <path d="M20 20l20 20M40 20l-20 20" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </div>`;
            }
            $('#notifyIcon').html(iconHtml);
            $('#notifyModalLabel').text(type === 'success' ? 'Thành công' : 'Lỗi');
            $('#notifyModalLabel').css('color', type === 'success' ? '#10b981' : '#ef4444');
            $('#notifyModalBody').html('<?= htmlspecialchars($message) ?>');
            modal.modal({backdrop: 'static', keyboard: true});
            modal.modal('show');
            // Tự động đóng sau 3s
            setTimeout(function() {
                modal.modal('hide');
            }, 3000);
        }, 300);
    });
</script>
<?php endif; ?>

                <!-- Thanh tìm kiếm và nút tự điền -->
                <div class="search-bar">
                    <input type="text" id="search-company" placeholder="Tìm kiếm công ty theo tên, MST, lĩnh vực...">
                    <button type="button" class="btn btn-info" id="btn-add-manual">
                        <i class="fa fa-plus"></i> Thêm
                    </button>
                </div>
                <div class="row">
                    <!-- Card 1: Danh sách công ty thực tập -->
                    <div class="col-xl-8 col-lg-7 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-building"></i> Danh sách công ty thực tập
                            </div>
                            <div class="card-body">
                                <!-- Danh sách công ty dạng panel -->
                                <div id="company-panel-list" class="row"></div>
                            </div>
                            <div class="card-footer">
                                <div class="company-list-pagination" id="company-pagination"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 2: Trạng thái giấy đăng ký giới thiệu -->
                    <div class="col-xl-4 col-lg-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-file-text"></i> Phiếu đăng ký của bạn
                            </div>
                            <div class="card-body">
                                <div id="giay-panel-list"></div>
                            </div>
                            <div class="card-footer">
                                <div class="giay-list-pagination" id="giay-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
               

                <!-- Modal chi tiết công ty -->
                <div class="modal fade" id="companyModal" tabindex="-1" role="dialog" aria-labelledby="companyModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="companyModalLabel">Thông tin công ty</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body" id="company-modal-body">
                        <!-- Nội dung sẽ được fill bằng JS -->
                        <form method="post" id="company-approve-form" style="display:none;">
                            <input type="hidden" name="ma_so_thue" id="approve-ma-so-thue">
                            <input type="hidden" name="ten_cong_ty" id="approve-ten-cong-ty">
                            <input type="hidden" name="dia_chi" id="approve-dia-chi">
                            <input type="hidden" name="linh_vuc" id="approve-linh-vuc">
                            <input type="hidden" name="sdt" id="approve-sdt">
                            <input type="hidden" name="email" id="approve-email">
                            <input type="hidden" name="from_panel" value="1">
                        </form>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="btn-approve-company">
                            <i class="fa fa-check"></i> Gửi (Đã duyệt)
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Modal nhập thủ công -->
                <div class="modal fade" id="manualModal" tabindex="-1" role="dialog" aria-labelledby="manualModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <form method="post" id="manual-company-form" class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="manualModalLabel">Nhập thông tin công ty thủ công</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <div class="form-group">
                            <label for="manual-ma-so-thue">Mã số thuế</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="manual-ma-so-thue" name="ma_so_thue" placeholder="Mã số thuế">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" id="btn-fill-api">
                                        <i class="fa fa-sync"></i> Lấy thông tin
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="manual-ten-cong-ty">Tên công ty</label>
                            <input type="text" class="form-control" id="manual-ten-cong-ty" name="ten_cong_ty" placeholder="Tên công ty">
                        </div>
                        <div class="form-group">
                            <label for="manual-dia-chi">Địa chỉ</label>
                            <input type="text" class="form-control" id="manual-dia-chi" name="dia_chi" placeholder="Địa chỉ">
                        </div>
                        <div class="form-group">
                            <label for="manual-linh-vuc">Lĩnh vực</label>
                            <input type="text" class="form-control" id="manual-linh-vuc" name="linh_vuc" placeholder="Lĩnh vực">
                        </div>
                        <div class="form-group">
                            <label for="manual-sdt">SĐT</label>
                            <input type="text" class="form-control" id="manual-sdt" name="sdt" placeholder="Số điện thoại">
                        </div>
                        <div class="form-group">
                            <label for="manual-email">Email</label>
                            <input type="email" class="form-control" id="manual-email" name="email" placeholder="Email">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class="fa fa-paper-plane"></i> Gửi yêu cầu</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                      </div>
                    </form>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    <script>
    const companyList = <?= json_encode($companyList) ?>;
    let filteredCompanies = [...companyList];
    let currentPage = 1;
    const perPage = 8;

    function renderCompanyPanels() {
        const list = document.getElementById('company-panel-list');
        list.innerHTML = '';
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        const pageCompanies = filteredCompanies.slice(start, end);

        if (pageCompanies.length === 0) {
            list.innerHTML = `
                <div class="col-12 text-center py-4">
                    <div class="text-muted">
                        <i class="fa fa-search fa-2x mb-3"></i>
                        <p>Không tìm thấy công ty phù hợp.</p>
                    </div>
                </div>`;
            return;
        }

        pageCompanies.forEach((cty, idx) => {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-6 mb-3';
            col.innerHTML = `
                <div class="company-panel" data-index="${start + idx}">
                    <div class="font-weight-bold">${cty.TenCty}</div>
                    <div class="company-info"><strong>MST:</strong> ${cty.MaSoThue}</div>
                    <div class="company-info"><strong>Lĩnh vực:</strong> ${cty.Linhvuc}</div>
                    <div class="company-info"><strong>SĐT:</strong> ${cty.Sdt}</div>
                </div>
            `;
            list.appendChild(col);
        });
    }

    function renderPagination() {
        const pag = document.getElementById('company-pagination');
        pag.innerHTML = '';
        let total = Math.ceil(filteredCompanies.length / perPage);
        if (total < 1) total = 1;

        const maxShow = 5; // Số nút trang hiển thị gần trang hiện tại
        let pages = [];

        if (total <= maxShow + 2) {
            // Hiển thị tất cả nếu số trang ít
            for (let i = 1; i <= total; i++) pages.push(i);
        } else {
            // Luôn hiển thị trang đầu và cuối
            pages.push(1);
            let start = Math.max(2, currentPage - 2);
            let end = Math.min(total - 1, currentPage + 2);

            if (start > 2) pages.push('...');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < total - 1) pages.push('...');
            pages.push(total);
        }

        pages.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.textContent = '...';
                span.style.cssText = 'padding:0 8px;color:#007bff;font-weight:bold;font-size:18px;user-select:none;';
                pag.appendChild(span);
            } else {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = (p === currentPage) ? 'active' : '';
                btn.onclick = () => {
                    currentPage = p;
                    renderCompanyPanels();
                    renderPagination();
                };
                pag.appendChild(btn);
            }
        });
    }

    function filterCompanies(keyword) {
        keyword = keyword.trim().toLowerCase();
        if (!keyword) {
            filteredCompanies = [...companyList];
        } else {
            filteredCompanies = companyList.filter(c =>
                (c.TenCty && c.TenCty.toLowerCase().includes(keyword)) ||
                (c.MaSoThue && c.MaSoThue.toLowerCase().includes(keyword)) ||
                (c.Linhvuc && c.Linhvuc.toLowerCase().includes(keyword))
            );
        }
        currentPage = 1;
        renderCompanyPanels();
        renderPagination();
    }

    // Dữ liệu giấy giới thiệu từ PHP
    const giayList = <?= json_encode($giayList) ?>;
    let giayCurrentPage = 1;
    const giayPerPage = 3; // mỗi trang chỉ 3 panel

    function renderGiayPanels() {
        const list = document.getElementById('giay-panel-list');
        list.innerHTML = '';
        const start = (giayCurrentPage - 1) * giayPerPage;
        const end = start + giayPerPage;
        const pageGiay = giayList.slice(start, end);

        if (pageGiay.length === 0) {
            list.innerHTML = '<div class="text-center text-muted">Chưa có phiếu giới thiệu nào.</div>';
            return;
        }

        const trangThaiMap = {
            0: '<span class="badge badge-warning">Chờ duyệt</span>',
            1: '<span class="badge badge-success">Đã duyệt</span>',
            2: '<span class="badge badge-info">Đã in</span>',
            3: '<span class="badge badge-primary">Đã nhận</span>',
        };

        pageGiay.forEach(giay => {
            const div = document.createElement('div');
            div.className = 'giay-panel';
            
            // Hiển thị thông tin đợt thực tập nếu có
            let dotInfo = '';
            if (giay.TenDot) {
                dotInfo = `<div class="dot-info">
                    <i class="fa fa-calendar"></i> ${giay.TenDot}`;
                if (giay.ThoiGianBatDau && giay.ThoiGianKetThuc) {
                    const startDate = new Date(giay.ThoiGianBatDau).toLocaleDateString('vi-VN');
                    const endDate = new Date(giay.ThoiGianKetThuc).toLocaleDateString('vi-VN');
                    dotInfo += `<br><small>${startDate} - ${endDate}</small>`;
                }
                dotInfo += `</div>`;
            }
            
            div.innerHTML = `
                <div class="tencty">${giay.TenCty}</div>
                <div class="trangthai">Trạng thái: ${trangThaiMap[giay.TrangThai] || '<span class="badge badge-danger">Từ chối</span>'}</div>
                ${dotInfo}
            `;
            list.appendChild(div);
        });
    }

    function renderGiayPagination() {
        const pag = document.getElementById('giay-pagination');
        pag.innerHTML = '';
        let total = Math.ceil(giayList.length / giayPerPage);
        if (total < 1) total = 1;

        const maxShow = 3;
        let pages = [];

        if (total <= maxShow + 2) {
            for (let i = 1; i <= total; i++) pages.push(i);
        } else {
            pages.push(1);
            let start = Math.max(2, giayCurrentPage - 1);
            let end = Math.min(total - 1, giayCurrentPage + 1);

            if (start > 2) pages.push('...');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < total - 1) pages.push('...');
            pages.push(total);
        }

        pages.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.textContent = '...';
                pag.appendChild(span);
            } else {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = (p === giayCurrentPage) ? 'active' : '';
                btn.onclick = () => {
                    giayCurrentPage = p;
                    renderGiayPanels();
                    renderGiayPagination();
                };
                pag.appendChild(btn);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderCompanyPanels();
        renderPagination();
        renderGiayPanels();
        renderGiayPagination();

        document.getElementById('search-company').addEventListener('input', function() {
            filterCompanies(this.value);
        });

        // Panel click mở modal
        document.getElementById('company-panel-list').addEventListener('click', function(e) {
            let panel = e.target.closest('.company-panel');
            if (!panel) return;
            const idx = +panel.getAttribute('data-index');
            const cty = filteredCompanies[idx];
            if (!cty) return;

            // Fill modal với thông tin chi tiết hơn
            document.getElementById('company-modal-body').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tên công ty:</strong><br>${cty.TenCty}</p>
                        <p><strong>Mã số thuế:</strong><br>${cty.MaSoThue}</p>
                        <p><strong>Lĩnh vực:</strong><br>${cty.Linhvuc}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Địa chỉ:</strong><br>${cty.DiaChi}</p>
                        <p><strong>Số điện thoại:</strong><br>${cty.Sdt}</p>
                        <p><strong>Email:</strong><br>${cty.Email}</p>
                    </div>
                </div>
                <form method="post" id="company-approve-form" style="display:none;">
                    <input type="hidden" name="ma_so_thue" id="approve-ma-so-thue">
                    <input type="hidden" name="ten_cong_ty" id="approve-ten-cong-ty">
                    <input type="hidden" name="dia_chi" id="approve-dia-chi">
                    <input type="hidden" name="linh_vuc" id="approve-linh-vuc">
                    <input type="hidden" name="sdt" id="approve-sdt">
                    <input type="hidden" name="email" id="approve-email">
                    <input type="hidden" name="from_panel" value="1">
                </form>
            `;
            $('#companyModal').modal('show');

            // Đảm bảo chỉ gán sự kiện 1 lần: Xóa sự kiện cũ trước khi gán mới
            const btnApprove = document.getElementById('btn-approve-company');
            const newBtnApprove = btnApprove.cloneNode(true);
            btnApprove.parentNode.replaceChild(newBtnApprove, btnApprove);

            newBtnApprove.onclick = function() {
                // Lấy lại input hidden vừa tạo trong modal-body
                document.getElementById('approve-ten-cong-ty').value = cty.TenCty;
                document.getElementById('approve-ma-so-thue').value = cty.MaSoThue;
                document.getElementById('approve-dia-chi').value = cty.DiaChi;
                document.getElementById('approve-linh-vuc').value = cty.Linhvuc;
                document.getElementById('approve-sdt').value = cty.Sdt;
                document.getElementById('approve-email').value = cty.Email;
                // Submit form ẩn để lưu về CSDL bằng PHP thuần
                document.getElementById('company-approve-form').submit();
            };
        });

        // Nút mở modal nhập thủ công
        document.getElementById('btn-add-manual').onclick = function() {
            // Reset form
            document.getElementById('manual-company-form').reset();
            $('#manualModal').modal('show');
        };

        // Lấy thông tin công ty bằng API với loading state
        document.getElementById('btn-fill-api').onclick = async function(e) {
            e.preventDefault();
            const taxCode = document.getElementById('manual-ma-so-thue').value.trim();
            if (!taxCode) {
                alert('Vui lòng nhập mã số thuế');
                return;
            }
            
            // Hiển thị loading
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang tìm...';
            btn.disabled = true;
            
            try {
                const info = await getBusinessInfoByTaxCode(taxCode);
                if (info) {
                    document.getElementById('manual-ten-cong-ty').value = info.name || info.shortName || '';
                    document.getElementById('manual-dia-chi').value = info.address || info.diaChi || '';
                    document.getElementById('manual-linh-vuc').value = info.businessLine || info.linhVuc || '';
                    document.getElementById('manual-sdt').value = info.phone || info.soDienThoai || '';
                    document.getElementById('manual-email').value = info.email || '';
                } else {
                    alert('Không tìm thấy thông tin doanh nghiệp hoặc API bị lỗi.');
                }
            } catch (error) {
                console.error('API Error:', error);
                alert('Có lỗi xảy ra khi gọi API. Vui lòng thử lại.');
            } finally {
                // Khôi phục trạng thái button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        };

        // Gửi form nhập thủ công (submit về PHP, không gọi API, chỉ validate dữ liệu)
        document.getElementById('manual-company-form').onsubmit = function(e) {
            const taxCode = document.getElementById('manual-ma-so-thue').value.trim();
            const name = document.getElementById('manual-ten-cong-ty').value.trim();
            const address = document.getElementById('manual-dia-chi').value.trim();
            const field = document.getElementById('manual-linh-vuc').value.trim();
            const phone = document.getElementById('manual-sdt').value.trim();
            const email = document.getElementById('manual-email').value.trim();

            // Kiểm tra rỗng
            if (!taxCode || !name || !address || !field || !phone || !email) {
                alert('Vui lòng nhập đầy đủ tất cả các trường!');
                e.preventDefault();
                return false;
            }
            // Kiểm tra email hợp lệ
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Email không hợp lệ!');
                e.preventDefault();
                return false;
            }
            // Kiểm tra số điện thoại (chỉ số, tối thiểu 8 ký tự)
            const phoneRegex = /^[0-9\-\+\s]{8,}$/;
            if (!phoneRegex.test(phone)) {
                alert('Số điện thoại không hợp lệ!');
                e.preventDefault();
                return false;
            }
            // Không gọi API ở đây nữa!
            return true;
        };
        // Form nhập thủ công
        const manualForm = document.getElementById('manual-company-form');
        if (manualForm) {
            manualForm.addEventListener('submit', function() {
                $('#loadingModal').modal('show');
            });
        }
    });
    </script>
    <script src="/datn/api/getapi.js"></script>
</body>
</html>