<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Kiểm tra quyền truy cập
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

if (!isset($_SESSION['user']['ID_TaiKhoan'])) {
    header('Location: /datn/login.php');
    exit();
}

$letterId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$letterData = null;

if ($letterId > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                g.ID, g.TenCty, g.DiaChi, g.IdSinhVien, g.MaSoThue, 
                g.LinhVuc, g.Sdt, g.Email, g.TrangThai, g.id_dot,
                s.Ten AS TenSinhVien, s.MSSV, s.NgaySinh,
                d.TenDot, d.ThoiGianBatDau AS NgayBatDau, d.ThoiGianKetThuc AS NgayKetThuc
            FROM giaygioithieu g
            LEFT JOIN sinhvien s ON g.IdSinhVien = s.ID_TaiKhoan
            LEFT JOIN dotthuctap d ON g.id_dot = d.ID
            WHERE g.ID = ?
        ");
        $stmt->execute([$letterId]);
        $letterData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Log thông tin query
        error_log("Print Template Debug - ID: $letterId, Found: " . ($letterData ? 'YES' : 'NO'));
        if (!$letterData) {
            // Kiểm tra xem ID có tồn tại trong bảng không
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM giaygioithieu WHERE ID = ?");
            $checkStmt->execute([$letterId]);
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Print Template Debug - ID $letterId exists in table: " . ($checkResult['count'] > 0 ? 'YES' : 'NO'));
        }
    } catch (Exception $e) {
        error_log("Lỗi in giấy giới thiệu: " . $e->getMessage());
        error_log("Query error for ID: $letterId");
    }
}

if (!$letterData) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lỗi - Không tìm thấy giấy giới thiệu</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: #f8f9fa;
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 500px;
                margin: 0 auto;
            }
            .error-icon {
                font-size: 4rem;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-title {
                color: #dc3545;
                font-size: 1.5rem;
                margin-bottom: 15px;
            }
            .error-message {
                color: #6c757d;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 600;
                transition: background 0.2s;
            }
            .btn:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">📄❌</div>
            <h1 class="error-title">Không tìm thấy giấy giới thiệu</h1>
            <p class="error-message">
                Giấy giới thiệu với ID #<?= htmlspecialchars($letterId) ?> không tồn tại trong hệ thống 
                hoặc bạn không có quyền truy cập.
            </p>
            <a href="/datn/admin/pages/quanlygiaygioithieu.php" class="btn">
                ← Quay lại danh sách
            </a>
        </div>
        
        <script>
            // Tự động đóng tab sau 5 giây nếu được mở từ popup
            if (window.opener) {
                setTimeout(function() {
                    window.close();
                }, 5000);
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Tạo số giấy tự động
$soGiay = str_pad($letterData['ID'], 3, '0', STR_PAD_LEFT) . '/CĐKTCT-CTCT HSSV';
$ngayHienTai = date('d/m/Y');
$ngayTao = $ngayHienTai; // Sử dụng ngày hiện tại vì bảng không có trường NgayTao

// Kiểm tra trạng thái giấy
$trangThaiText = '';
$showWarning = false;
switch ($letterData['TrangThai']) {
    case 0:
        $trangThaiText = 'Chưa duyệt';
        $showWarning = true;
        break;
    case 1:
        $trangThaiText = 'Đã duyệt';
        break;
    case 2:
        $trangThaiText = 'Đã in';
        break;
    default:
        $trangThaiText = 'Không xác định';
        $showWarning = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giấy Giới Thiệu - <?= htmlspecialchars($letterData['TenSinhVien']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm 2.5cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 13pt;
            line-height: 1.6;
            color: #000;
            background: white;
            max-width: 21cm;
            margin: 0 auto;
            padding: 1cm;
        }
        
        .document-container {
            width: 100%;
            background: white;
            padding: 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
            min-height: 100px;
        }
        
        .header-left, .header-right {
            width: 45%;
            text-align: center;
        }
        
        .header-left div, .header-right div {
            margin-bottom: 5px;
        }
        
        .header-left .org-name,
        .header-right .country-name {
            font-weight: bold;
            font-size: 12pt;
            text-transform: uppercase;
        }
        
        .header-left .school-name {
            font-weight: bold;
            font-size: 12pt;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        
        .header-right .motto {
            font-weight: bold;
            font-size: 12pt;
            text-decoration: underline;
            margin-bottom: 15px;
        }
        
        .document-number {
            font-weight: bold;
            font-size: 11pt;
        }
        
        .document-purpose {
            font-style: italic;
            font-size: 11pt;
        }
        
        .date-location {
            font-style: italic;
            font-size: 11pt;
        }
        
        .title-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .title {
            font-weight: bold;
            font-size: 16pt;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
        }
        
        .content {
            text-align: justify;
            margin-bottom: 30px;
        }
        
        .content p {
            margin-bottom: 15px;
            text-indent: 30px;
        }
        
        .content .no-indent {
            text-indent: 0;
        }
        
        .info-list {
            margin: 20px 0;
        }
        
        .info-item {
            margin-bottom: 8px;
            text-indent: 30px;
        }
        
        .info-item strong {
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: flex-end;
        }
        
        .signature-block {
            text-align: center;
            width: 250px;
        }
        
        .signature-location {
            font-style: italic;
            margin-bottom: 20px;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 80px;
            line-height: 1.4;
        }
        
        .signature-name {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border: 2px solid #007bff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .warning-banner {
            background: #fff3cd;
            color: #856404;
            padding: 15px 20px;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .warning-banner i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        @media print {
            .print-controls, .warning-banner {
                display: none !important;
            }
            
            body {
                background: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 12pt;
            }
            
            .document-container {
                max-width: none;
                padding: 0;
            }
            
            @page {
                margin: 2cm;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
                font-size: 12pt;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .header-left, .header-right {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .print-controls {
                position: relative;
                width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="btn btn-primary" onclick="window.print()">
            🖨️ In giấy giới thiệu
        </button>
        <a href="/datn/admin/pages/quanlygiaygioithieu.php" class="btn btn-secondary">
            ← Quay lại
        </a>
    </div>

    <?php if ($showWarning): ?>
    <div class="warning-banner">
        ⚠️ Cảnh báo: Giấy giới thiệu này có trạng thái "<?= $trangThaiText ?>" - Vui lòng kiểm tra lại trước khi in chính thức!
    </div>
    <?php endif; ?>

    <div class="document-container">
        <div class="header">
            <div class="header-left">
                <div class="org-name">BỘ CÔNG THƯƠNG</div>
                <div class="school-name">TRƯỜNG CĐ KỸ THUẬT CAO THẮNG</div>
                <div class="document-number">Số: <?= $soGiay ?></div>
                <div class="document-purpose"><em>V/v: Liên hệ thực tập tốt nghiệp</em></div>
            </div>
            
            <div class="header-right">
                <div class="country-name">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="motto">Độc lập – Tự do – Hạnh phúc</div>
                <div class="date-location">
                    <em>TP.Hồ Chí Minh, ngày <?= date('d', strtotime($ngayTao)) ?> tháng <?= date('m', strtotime($ngayTao)) ?> năm <?= date('Y', strtotime($ngayTao)) ?></em>
                </div>
            </div>
        </div>

        <div class="title-section">
            <div class="title">Kính gửi: <?= strtoupper(htmlspecialchars($letterData['TenCty'])) ?></div>
        </div>

        <div class="content">
            <p>Để thực hiện tốt nhiệm vụ đào tạo của trường, giúp cho sinh viên học tập trong nhà trường phối hợp thực hành, sản xuất nâng cao tay nghề từ thực tiễn tại nhà máy, công ty, cơ sở sản xuất.</p>
            
            <p>Trường Cao đẳng Kỹ thuật Cao Thắng kính đề nghị Quý đơn vị:</p>
            
            <div class="info-list">
                <div class="info-item">* Tạo điều kiện cho: <strong>01 sinh viên</strong> (danh sách đính kèm).</div>
                
                <div class="info-item">* Được thực tập sản xuất tại đơn vị theo ngành, nghề đào tạo: <strong>Công nghệ Thông tin</strong></div>
                
                <div class="info-item">* Với giảng viên hướng dẫn là Thầy/Cô: <strong>Lý Cao Tiến</strong></div>
                
                <div class="info-item">* Thời gian thực tập từ ngày: <strong><?= $letterData['NgayBatDau'] ? date('d/m/Y', strtotime($letterData['NgayBatDau'])) : '05/09/2023' ?></strong> đến ngày: <strong><?= $letterData['NgayKetThuc'] ? date('d/m/Y', strtotime($letterData['NgayKetThuc'])) : '09/12/2023' ?></strong></div>
                
                <div class="info-item">* Nội dung thực tập: theo đề cương thực tập (gửi kèm).</div>
            </div>
            
            <p>Nhà trường cũng với giảng viên hướng dẫn có trách nhiệm giáo dục, nhắc nhở sinh viên tuân thủ trường chấp hành nghiêm nội quy, quy định thực tập, sản xuất tại Quý đơn vị.</p>
            
            <p>Rất mong được xem xét giải quyết.</p>
            
            <p>Trân trọng kính chào./.</p>
        </div>

        <div class="signature-section">
            <div class="signature-block">
                <div class="signature-location">
                    <em></em>
                </div>
                
                <div class="signature-title">
                    <strong>TL. HIỆU TRƯỞNG<br>
                    TRƯỞNG PHÒNG CTCT HSSV</strong>
                </div>
                
                <div class="signature-name">
                    <!-- Tên người ký sẽ được điền tại đây -->
                </div>
            </div>
        </div>

        <!-- Thông tin sinh viên đính kèm -->
        <div style="margin-top: 50px; page-break-before: always;">
            <h3 style="text-align: center; margin-bottom: 20px; text-decoration: underline;">
                DANH SÁCH SINH VIÊN THỰC TẬP
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="border: 1px solid #000; padding: 10px; text-align: center; font-weight: bold;">STT</th>
                        <th style="border: 1px solid #000; padding: 10px; text-align: center; font-weight: bold;">Họ và tên</th>
                        <th style="border: 1px solid #000; padding: 10px; text-align: center; font-weight: bold;">MSSV</th>
                        <th style="border: 1px solid #000; padding: 10px; text-align: center; font-weight: bold;">Ngành học</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #000; padding: 10px; text-align: center;">1</td>
                        <td style="border: 1px solid #000; padding: 10px;"><?= htmlspecialchars($letterData['TenSinhVien']) ?></td>
                        <td style="border: 1px solid #000; padding: 10px; text-align: center;"><?= htmlspecialchars($letterData['MSSV']) ?></td>
                        <td style="border: 1px solid #000; padding: 10px; text-align: center;">Công nghệ Thông tin</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Tự động focus vào trang để sẵn sàng in
        window.onload = function() {
            document.body.focus();
        };
        
        // Phím tắt Ctrl+P để in
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
        
        // Đóng cửa sổ sau khi in (nếu cần)
        window.addEventListener('afterprint', function() {
            // Có thể thêm logic đóng cửa sổ nếu mở trong tab mới
            // window.close();
        });
    </script>
</body>
</html>
