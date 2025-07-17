<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: /datn/login.php');
    exit();
}

// Lấy danh sách ID từ query string
$letterIds = $_GET['ids'] ?? [];

if (empty($letterIds)) {
    echo '<div class="alert alert-danger">Không có giấy giới thiệu nào được chọn để in.</div>';
    exit();
}

try {
    // Chuẩn bị câu lệnh query
    $placeholders = str_repeat('?,', count($letterIds) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT 
            g.ID, g.TenCty, g.DiaChi, g.Idsinhvien, g.TrangThai, g.id_dot,
            s.Ten AS TenSinhVien, s.MSSV, s.NgaySinh, s.Lop,
            d.TenDot, d.ThoiGianBatDau AS NgayBatDau, d.ThoiGianKetThuc AS NgayKetThuc
        FROM giaygioithieu g
        LEFT JOIN sinhvien s ON g.Idsinhvien = s.ID_taikhoan
        LEFT JOIN dotthuctap d ON g.id_dot = d.ID
        WHERE g.ID IN ($placeholders)
        AND g.TrangThai IN (1, 2)
        ORDER BY g.TenCty, s.Ten
    ");
    
    $stmt->execute($letterIds);
    $letters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($letters)) {
        // Debug: Check what letters exist with any status
        $debugStmt = $conn->prepare("
            SELECT g.ID, g.TrangThai, g.TenCty, s.Ten AS TenSinhVien
            FROM giaygioithieu g
            LEFT JOIN sinhvien s ON g.Idsinhvien = s.ID_taikhoan
            WHERE g.ID IN ($placeholders)
        ");
        $debugStmt->execute($letterIds);
        $debugLetters = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<div class="alert alert-warning">Không tìm thấy giấy giới thiệu hợp lệ để in.</div>';
        echo '<div class="alert alert-info">Requested IDs: ' . htmlspecialchars(implode(', ', $letterIds)) . '</div>';
        if (!empty($debugLetters)) {
            echo '<div class="alert alert-info">Found letters with different status:</div>';
            foreach ($debugLetters as $dl) {
                $statusText = '';
                switch ($dl['TrangThai']) {
                    case 0: $statusText = 'Chưa duyệt'; break;
                    case 1: $statusText = 'Đã duyệt'; break;
                    case 2: $statusText = 'Đã in'; break;
                    case 3: $statusText = 'Đã nhận'; break;
                    case 4: $statusText = 'Chờ lấy'; break;
                    default: $statusText = 'Không xác định';
                }
                echo '<div>ID: ' . $dl['ID'] . ' - ' . htmlspecialchars($dl['TenSinhVien']) . ' (' . htmlspecialchars($dl['TenCty']) . ') - Trạng thái: ' . $statusText . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Không tìm thấy bất kỳ giấy nào với IDs đã cho!</div>';
        }
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in print_selected_letters.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu.</div>';
    echo '<div class="alert alert-info">Debug info: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="alert alert-info">Letter IDs: ' . htmlspecialchars(implode(', ', $letterIds)) . '</div>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Giấy Giới Thiệu - <?php echo count($letters); ?> giấy đã chọn</title>
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
            page-break-after: always;
            margin-bottom: 40px;
        }
        
        .document-container:last-child {
            page-break-after: avoid;
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
        
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .student-table th,
        .student-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
        }
        
        .student-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .student-table td.text-left {
            text-align: left;
        }
        
        @media print {
            .print-controls {
                display: none !important;
            }
            
            body {
                background: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 12pt;
                padding: 0;
            }
            
            .document-container {
                max-width: none;
                padding: 0;
                page-break-after: always;
                margin: 0;
            }
            
            .document-container:last-child {
                page-break-after: avoid;
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
            🖨️ In tất cả giấy
        </button>
        <a href="/datn/admin/pages/quanlygiaygioithieu.php" class="btn btn-secondary">
            ← Quay lại
        </a>
        <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
            Số lượng: <?php echo count($letters); ?> giấy | Thời gian: <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>

    <?php foreach ($letters as $index => $letter): 
        // Tạo số giấy tự động cho từng giấy
        $soGiay = str_pad($letter['ID'], 3, '0', STR_PAD_LEFT) . '/CĐKTCT-CTCT HSSV';
        $ngayHienTai = date('d/m/Y');
    ?>
    <div class="document-container">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="width: 45%; vertical-align: top; text-align: center;">
                        <div class="org-name">BỘ CÔNG THƯƠNG</div>
                        <div class="school-name">TRƯỜNG CĐ KỸ THUẬT CAO THẮNG</div>
                        <div class="document-number">Số: <?= $soGiay ?></div>
                        <div class="document-purpose"><em>V/v: Liên hệ thực tập tốt nghiệp</em></div>
                    </td>
                    <td style="width: 55%; text-align: center; vertical-align: top;">
                        <div class="country-name">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                        <div class="motto">Độc lập – Tự do – Hạnh phúc</div>
                        <div class="date-location">
                            <em>TP.Hồ Chí Minh, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></em>
                        </div>
                    </td>
                </tr>
            </table>
        <div class="title-section">
            <div class="title">Kính gửi: <?= strtoupper(htmlspecialchars($letter['TenCty'])) ?></div>
        </div>

        <div class="content">
            <p>Để thực hiện tốt nhiệm vụ đào tạo của trường, giúp cho sinh viên học tập trong nhà trường phối hợp thực hành, sản xuất nâng cao tay nghề từ thực tiễn tại nhà máy, công ty, cơ sở sản xuất.</p>
            
            <p>Trường Cao đẳng Kỹ thuật Cao Thắng kính đề nghị Quý đơn vị:</p>
            
            <div class="info-list">
                <div class="info-item">* Tạo điều kiện cho: <strong>01 sinh viên</strong> (danh sách đính kèm).</div>
                
                <div class="info-item">* Được thực tập sản xuất tại đơn vị theo ngành, nghề đào tạo: <strong>Công nghệ Thông tin</strong></div>
                
                <div class="info-item">* Với giảng viên hướng dẫn là Thầy/Cô: <strong>Lý Cao Tiến</strong></div>
                
                <div class="info-item">* Thời gian thực tập từ ngày: <strong><?= $letter['NgayBatDau'] ? date('d/m/Y', strtotime($letter['NgayBatDau'])) : '05/09/2023' ?></strong> đến ngày: <strong><?= $letter['NgayKetThuc'] ? date('d/m/Y', strtotime($letter['NgayKetThuc'])) : '09/12/2023' ?></strong></div>
                
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
            
            <table class="student-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ và tên</th>
                        <th>MSSV</th>
                        <th>Ngành học</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td class="text-left"><?= htmlspecialchars($letter['TenSinhVien']) ?></td>
                        <td><?= htmlspecialchars($letter['MSSV']) ?></td>
                        <td>Công nghệ Thông tin</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

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
            console.log('Print job completed or cancelled');
            // Có thể thêm logic đóng cửa sổ nếu mở trong tab mới
            // window.close();
        });
    </script>
</body>
</html>
