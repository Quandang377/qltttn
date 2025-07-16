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

// Lấy tất cả giấy đã duyệt
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
        WHERE g.TrangThai = 1
        ORDER BY g.TenCty, s.MSSV
    ");
    $stmt->execute();
    $allLetters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Lỗi in giấy giới thiệu theo công ty: " . $e->getMessage());
    $allLetters = [];
}

// Gộp theo công ty
function groupLettersByCompany($letters) {
    $grouped = [];
    foreach ($letters as $letter) {
        $key = $letter['TenCty'] . '|' . $letter['id_dot'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'company_info' => $letter,
                'students' => []
            ];
        }
        $grouped[$key]['students'][] = $letter;
    }
    return $grouped;
}

$groupedLetters = groupLettersByCompany($allLetters);

if (empty($groupedLetters)) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Không có giấy giới thiệu để in</title>
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
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Không có giấy giới thiệu để in</h1>
            <p>Hiện tại không có giấy giới thiệu nào ở trạng thái "Đã duyệt".</p>
            <a href="/datn/admin/pages/quanlygiaygioithieu.php">← Quay lại danh sách</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$ngayHienTai = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giấy Giới Thiệu - In theo công ty</title>
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
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border: 1px solid #ddd;
        }
        
        .print-controls button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .print-controls button:hover {
            background: #0056b3;
        }
        
        .print-controls .btn-close {
            background: #6c757d;
        }
        
        .print-controls .btn-close:hover {
            background: #545b62;
        }
        
        .document-container {
            width: 100%;
            background: white;
            padding: 0;
            page-break-after: always;
            margin-bottom: 50px;
        }
        
        .document-container:last-child {
            page-break-after: auto;
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
        
        .content {
            text-align: justify;
            margin-bottom: 40px;
        }
        
        .content p {
            margin-bottom: 15px;
            text-indent: 30px;
        }
        
        .info-list {
            margin: 20px 0;
            padding-left: 0;
        }
        
        .info-item {
            margin-bottom: 10px;
            padding-left: 30px;
            text-indent: -30px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }
        
        .signature-block {
            text-align: center;
            width: 250px;
        }
        
        .signature-location {
            margin-bottom: 10px;
            font-style: italic;
        }
        
        .signature-title {
            margin-bottom: 60px;
            font-weight: bold;
            line-height: 1.4;
        }
        
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .student-table th,
        .student-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        
        .student-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .student-table td:nth-child(2) {
            text-align: left;
        }
        
        .multiple-students {
            color: #dc3545;
            font-weight: bold;
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
        <button onclick="window.print()">🖨️ In ngay</button>
        <button class="btn-close" onclick="window.close()">❌ Đóng</button>
    </div>

    <?php foreach ($groupedLetters as $group): ?>
        <?php 
        $companyInfo = $group['company_info'];
        $students = $group['students'];
        $studentCount = count($students);
        
        // Tạo số giấy tự động (sử dụng ID của sinh viên đầu tiên)
        $soGiay = str_pad($companyInfo['ID'], 3, '0', STR_PAD_LEFT) . '/CĐKTCT-CTCT HSSV';
        ?>
        
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
                        <em>TP.Hồ Chí Minh, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></em>
                    </div>
                </div>
            </div>

            <div class="title-section">
                <div class="title">Kính gửi: <?= strtoupper(htmlspecialchars($companyInfo['TenCty'])) ?></div>
            </div>

            <div class="content">
                <p>Để thực hiện tốt nhiệm vụ đào tạo của trường, giúp cho sinh viên học tập trong nhà trường phối hợp thực hành, sản xuất nâng cao tay nghề từ thực tiễn tại nhà máy, công ty, cơ sở sản xuất.</p>
                
                <p>Trường Cao đẳng Kỹ thuật Cao Thắng kính đề nghị Quý đơn vị:</p>
                
                <div class="info-list">
                    <div class="info-item">
                        * Tạo điều kiện cho: 
                        <strong class="<?= $studentCount > 1 ? 'multiple-students' : '' ?>">
                            <?= sprintf('%02d', $studentCount) ?> sinh viên
                        </strong> 
                        (danh sách đính kèm).
                    </div>
                    
                    <div class="info-item">* Được thực tập sản xuất tại đơn vị theo ngành, nghề đào tạo: <strong>Công nghệ Thông tin</strong></div>
                    
                    <div class="info-item">* Với giảng viên hướng dẫn là Thầy/Cô: <strong>Lý Cao Tiến</strong></div>
                    
                    <div class="info-item">
                        * Thời gian thực tập từ ngày: 
                        <strong><?= $companyInfo['NgayBatDau'] ? date('d/m/Y', strtotime($companyInfo['NgayBatDau'])) : '05/09/2023' ?></strong> 
                        đến ngày: 
                        <strong><?= $companyInfo['NgayKetThuc'] ? date('d/m/Y', strtotime($companyInfo['NgayKetThuc'])) : '09/12/2023' ?></strong>
                    </div>
                    
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
            <div style="margin-top: 50px;">
                <h3 style="text-align: center; margin-bottom: 20px; text-decoration: underline;">
                    DANH SÁCH SINH VIÊN THỰC TẬP
                </h3>
                
                <?php if ($companyInfo['TenDot']): ?>
                    <p style="text-align: center; font-weight: bold; margin-bottom: 15px;">
                        Đợt: <?= htmlspecialchars($companyInfo['TenDot']) ?>
                        <?php if ($companyInfo['NgayBatDau'] && $companyInfo['NgayKetThuc']): ?>
                            (<?= date('d/m/Y', strtotime($companyInfo['NgayBatDau'])) ?> - <?= date('d/m/Y', strtotime($companyInfo['NgayKetThuc'])) ?>)
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                
                <table class="student-table">
                    <thead>
                        <tr>
                            <th style="width: 10%">STT</th>
                            <th style="width: 35%">Họ và tên</th>
                            <th style="width: 20%">MSSV</th>
                            <th style="width: 25%">Ngành học</th>
                            <th style="width: 10%">Chữ ký</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td style="text-align: left;"><?= htmlspecialchars($student['TenSinhVien']) ?></td>
                                <td><?= htmlspecialchars($student['MSSV']) ?></td>
                                <td>Công nghệ Thông tin</td>
                                <td style="height: 30px;"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 30px; display: flex; justify-content: space-between;">
                    <div style="width: 45%;">
                        <p><strong>Người đại diện nhận giấy:</strong></p>
                        <p>Họ tên: ....................................</p>
                        <p>MSSV: ......................................</p>
                        <p>Chữ ký: ....................................</p>
                    </div>
                    <div style="width: 45%; text-align: right;">
                        <p><strong>Ngày nhận:</strong> ...................</p>
                        <p><strong>Ghi chú:</strong></p>
                        <p>................................................</p>
                        <p>................................................</p>
                    </div>
                </div>
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
        
        // Hiển thị thông tin thống kê
        console.log('Tổng số công ty: <?= count($groupedLetters) ?>');
        console.log('Tổng số sinh viên: <?= count($allLetters) ?>');
    </script>
</body>
</html>
