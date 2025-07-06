<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user']['ID_TaiKhoan'])) {
    header('Location: /datn/login.php');
    exit();
}

// Utility functions
function shortAddress($address, $max = 50) {
    $address = trim($address);
    if (mb_strlen($address, 'UTF-8') > $max) {
        return mb_substr($address, 0, $max, 'UTF-8') . '...';
    }
    return $address;
}

function getLettersByStatus() {
    global $conn;
    
    try {
        // Optimized query - lấy tất cả trong một lần và group theo status
        $stmt = $conn->prepare("
            SELECT 
                g.ID, g.TenCty, g.DiaChi, g.IdSinhVien, g.TrangThai, g.id_dot, 
                g.id_nguoinhan, g.ngay_nhan, g.ghi_chu,
                s.Ten AS TenSinhVien, s.MSSV,
                sn.Ten AS TenNguoiNhan, sn.MSSV AS MSSVNguoiNhan,
                d.TenDot, d.ThoiGianBatDau, d.ThoiGianKetThuc
            FROM giaygioithieu g
            LEFT JOIN sinhvien s ON g.IdSinhVien = s.ID_TaiKhoan
            LEFT JOIN sinhvien sn ON g.id_nguoinhan = sn.ID_TaiKhoan
            LEFT JOIN dotthuctap d ON g.id_dot = d.ID
            ORDER BY g.TrangThai ASC, g.ID DESC
        ");
        $stmt->execute();
        $allLetters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by status
        $result = [
            'pending' => [],   // TrangThai = 0
            'approved' => [],  // TrangThai = 1  
            'printed' => [],   // TrangThai = 2
            'received' => []   // TrangThai = 3
        ];
        
        foreach ($allLetters as $letter) {
            switch ($letter['TrangThai']) {
                case 0:
                    $result['pending'][] = $letter;
                    break;
                case 1:
                    $result['approved'][] = $letter;
                    break;
                case 2:
                    $result['printed'][] = $letter;
                    break;
                case 3:
                    $result['received'][] = $letter;
                    break;
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in getLettersByStatus: " . $e->getMessage());
        return ['pending' => [], 'approved' => [], 'printed' => [], 'received' => []];
    }
}

// Lấy dữ liệu
$letters = getLettersByStatus();
$pendingList = $letters['pending'];
$approvedList = $letters['approved'];
$printedList = $letters['printed'];
$receivedList = $letters['received'];

// Hàm gộp giấy theo công ty để in
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý giấy giới thiệu</title>
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
        
        /* === FILTER BAR === */
        .filter-bar {
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
        
        .status-toggle {
            display: flex;
            gap: 5px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            padding: 4px;
        }
        
        .status-btn {
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .status-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .status-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }
        
        .status-btn .badge {
            background: #6b7280;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .status-btn.active .badge {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
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
        
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* === CARDS GRID === */
        .cards-container {
            margin-bottom: 30px;
        }
        
        .status-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .letter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            opacity: 0;
            transform: translateY(10px);
        }
        
        .letter-card.fade-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .letter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
        }
        
        .letter-card.clickable:hover {
            background: #f8fafc;
            cursor: pointer;
        }
        
        .card-header {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .company-address {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .student-info {
            color: #3b82f6;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-printed {
            background: #e0e7ff;
            color: #4f46e5;
        }
        
        .status-received {
            background: #f3e8ff;
            color: #7c3aed;
        }
        
        .received-note {
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            padding: 8px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        
        /* === MODAL STYLES === */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .close {
            color: white;
            opacity: 0.8;
        }
        
        .close:hover {
            color: white;
            opacity: 1;
        }
        
        .student-filter {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            grid-column: 1 / -1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #4b5563;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            #page-wrapper { padding: 15px; }
            .filter-bar { 
                flex-direction: column; 
                align-items: stretch;
                gap: 15px;
            }
            .action-buttons { 
                margin-left: 0;
                justify-content: center;
            }
            .status-section { 
                grid-template-columns: 1fr; 
                gap: 15px;
            }
            .page-header h1 { font-size: 1.8rem; }
        }
        
        @media (max-width: 480px) {
            .status-toggle { 
                flex-direction: column; 
                gap: 5px;
            }
            .status-btn { 
                padding: 12px 16px; 
                text-align: center;
            }
            .letter-card {
                padding: 15px;
            }
        }
        
        /* === PRINT STYLES === */
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
        }
        
        /* === LOADING & ANIMATIONS === */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hidden { display: none !important; }
        .loading { opacity: 0.6; pointer-events: none; }
    </style>
</head>
<body>
    <div id="wrapper">
       <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        <i class="fa fa-file-text"></i>
                        Quản lý giấy giới thiệu
                    </h1>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="status-toggle">
                        <button class="status-btn active" data-status="approved" id="btnapp">
                            <i class="fa fa-check-circle"></i>
                            Đã duyệt
                            <span class="badge"><?php echo count($approvedList); ?></span>
                        </button>
                        <button class="status-btn" data-status="pending" id="btnpen">
                            <i class="fa fa-clock-o"></i>
                            Chưa duyệt
                            <span class="badge"><?php echo count($pendingList); ?></span>
                        </button>
                        <button class="status-btn" data-status="printed" id="btnprinted">
                            <i class="fa fa-print"></i>
                            Đã in
                            <span class="badge"><?php echo count($printedList); ?></span>
                        </button>
                        <button class="status-btn" data-status="received" id="btnreceived">
                            <i class="fa fa-check-square"></i>
                            Đã nhận
                            <span class="badge"><?php echo count($receivedList); ?></span>
                        </button>
                    </div>
                    
                    <input type="text" class="search-input" id="searchInput" 
                           placeholder="🔍 Tìm kiếm theo MSSV, tên sinh viên hoặc tên công ty...">
                    
                    <div class="action-buttons">
                        <button id="printGroupedBtn" class="btn btn-primary">
                            <i class="fa fa-print"></i>
                            In theo công ty
                        </button>
                        <button id="printAllBtn" class="btn btn-primary">
                            <i class="fa fa-print"></i>
                            In tất cả
                        </button>
                    </div>
                </div>

                <!-- Cards Container -->
                <div class="cards-container" id="cardsContainer">
                    <!-- Approved Letters -->
                    <div id="approved-cards" class="status-section">
                        <?php if (count($approvedList) > 0): ?>
                            <?php foreach ($approvedList as $letter): ?>
                                <div class="letter-card clickable fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="approved"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['TenSinhVien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>"
                                     onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                    
                                    <div class="status-badge status-approved">
                                        <i class="fa fa-check"></i> Đã duyệt
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['TenSinhVien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation(); printLetter(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-print"></i> In
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-check-circle"></i>
                                <h3>Không có giấy giới thiệu đã duyệt</h3>
                                <p>Chưa có giấy giới thiệu nào được duyệt</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Letters -->
                    <div id="pending-cards" class="status-section hidden">
                        <?php if (count($pendingList) > 0): ?>
                            <?php foreach ($pendingList as $letter): ?>
                                <div class="letter-card clickable fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="pending"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['TenSinhVien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>"
                                     onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                    
                                    <div class="status-badge status-pending">
                                        <i class="fa fa-clock-o"></i> Chưa duyệt
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['TenSinhVien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation(); approveLetter(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-check"></i> Duyệt
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-clock-o"></i>
                                <h3>Không có giấy giới thiệu chưa duyệt</h3>
                                <p>Tất cả giấy giới thiệu đã được xử lý</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Printed Letters -->
                    <div id="printed-cards" class="status-section hidden">
                        <?php if (count($printedList) > 0): ?>
                            <?php foreach ($printedList as $letter): ?>
                                <div class="letter-card fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="printed"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['TenSinhVien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>">
                                    
                                    <div class="status-badge status-printed">
                                        <i class="fa fa-print"></i> Đã in
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['TenSinhVien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="markAsReceived(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-hand-o-up"></i> Ghi nhận
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-print"></i>
                                <h3>Không có giấy giới thiệu đã in</h3>
                                <p>Chưa có giấy giới thiệu nào được in</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Received Letters -->
                    <div id="received-cards" class="status-section hidden">
                        <?php if (count($receivedList) > 0): ?>
                            <?php foreach ($receivedList as $letter): ?>
                                <div class="letter-card fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="received"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['TenSinhVien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>">
                                    
                                    <div class="status-badge status-received">
                                        <i class="fa fa-check-square"></i> Đã nhận
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['TenSinhVien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($letter['TenNguoiNhan']): ?>
                                            <br><i class="fa fa-hand-o-up"></i>
                                            <small style="color: #16a34a;">
                                                Người nhận: <?php echo htmlspecialchars($letter['TenNguoiNhan']); ?> 
                                                (<?php echo htmlspecialchars($letter['MSSVNguoiNhan']); ?>)
                                            </small>
                                        <?php endif; ?>
                                        
                                        <?php if ($letter['ngay_nhan']): ?>
                                            <br><i class="fa fa-clock-o"></i>
                                            <small style="color: #6b7280;">
                                                Ngày nhận: <?php echo date('d/m/Y H:i', strtotime($letter['ngay_nhan'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($letter['ghi_chu']): ?>
                                        <div class="received-note">
                                            <i class="fa fa-comment"></i>
                                            <small><?php echo htmlspecialchars($letter['ghi_chu']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success disabled">
                                            <i class="fa fa-check"></i> Đã hoàn thành
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-check-square"></i>
                                <h3>Không có giấy giới thiệu đã nhận</h3>
                                <p>Chưa có giấy giới thiệu nào được nhận</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Modal ghi nhận thông tin -->
                <div id="receiveModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">
                                    <i class="fa fa-hand-o-up"></i>
                                    Ghi nhận thông tin nhận giấy
                                </h4>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="receiveForm">
                                    <input type="hidden" id="receive_letter_id" name="letter_id">
                                    
                                    <!-- Thông tin công ty -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Thông tin công ty:</label>
                                        <div id="company_info" class="alert alert-info">
                                            <!-- Sẽ được load bằng JS -->
                                        </div>
                                    </div>
                                    
                                    <!-- Danh sách sinh viên cùng công ty -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Danh sách sinh viên cùng công ty:</label>
                                        <div class="student-filter mb-3">
                                            <input type="text" id="student_search" class="form-control" 
                                                   placeholder="🔍 Tìm kiếm sinh viên theo MSSV hoặc tên...">
                                        </div>
                                        <div id="students_list" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            <!-- Sẽ được load bằng JS -->
                                        </div>
                                    </div>
                                    
                                    <!-- Chọn người nhận -->
                                    <div class="form-group">
                                        <label for="receive_nguoinhan" class="font-weight-bold">Người đại diện nhận giấy: <span class="text-danger">*</span></label>
                                        <select id="receive_nguoinhan" name="nguoinhan" class="form-control" required>
                                            <option value="">-- Chọn sinh viên đại diện --</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Ghi chú -->
                                    <div class="form-group">
                                        <label for="receive_ghichu" class="font-weight-bold">Ghi chú:</label>
                                        <textarea id="receive_ghichu" name="ghichu" class="form-control" rows="3" 
                                                  placeholder="Nhập ghi chú nếu có..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fa fa-times"></i> Hủy
                                </button>
                                <button type="button" class="btn btn-success" onclick="submitReceiveInfo()">
                                    <i class="fa fa-save"></i> Lưu thông tin
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    // Global variables
    let currentStatus = 'approved';
    let searchKeyword = '';

    // DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeEventListeners();
        updateDisplay();
    });

    function initializeEventListeners() {
        // Status toggle buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const newStatus = this.getAttribute('data-status');
                if (newStatus !== currentStatus) {
                    switchStatus(newStatus);
                }
            });
        });

        // Search input
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function() {
            searchKeyword = this.value.trim().toLowerCase();
            filterCards();
        });

        // Print all button
        document.getElementById('printAllBtn').addEventListener('click', handlePrintAll);
        
        // Print grouped button  
        document.getElementById('printGroupedBtn').addEventListener('click', handlePrintGrouped);
        
        // Student search in modal
        document.getElementById('student_search').addEventListener('input', filterStudentsInModal);
    }

    function switchStatus(newStatus) {
        // Update current status
        currentStatus = newStatus;
        
        // Update button states
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-status="${newStatus}"]`).classList.add('active');
        
        // Update display
        updateDisplay();
        
        // Reset search
        document.getElementById('searchInput').value = '';
        searchKeyword = '';
        
        // Update print button visibility
        updatePrintButtonVisibility();
    }

    function updateDisplay() {
        // Hide all sections
        document.querySelectorAll('.status-section').forEach(section => {
            section.classList.add('hidden');
        });
        
        // Show current section
        const currentSection = document.getElementById(`${currentStatus}-cards`);
        if (currentSection) {
            currentSection.classList.remove('hidden');
            
            // Add fade-in animation to cards
            const cards = currentSection.querySelectorAll('.letter-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 50);
            });
        }
    }

    function filterCards() {
        const currentSection = document.getElementById(`${currentStatus}-cards`);
        if (!currentSection) return;
        
        const cards = currentSection.querySelectorAll('.letter-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const mssv = card.getAttribute('data-mssv') || '';
            const student = card.getAttribute('data-student') || '';
            const company = card.getAttribute('data-company') || '';
            
            const searchText = (mssv + ' ' + student + ' ' + company).toLowerCase();
            const isVisible = searchKeyword === '' || searchText.indexOf(searchKeyword) !== -1;
            
            if (isVisible) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide empty state
        const emptyState = currentSection.querySelector('.empty-state');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function updatePrintButtonVisibility() {
        const printBtn = document.getElementById('printAllBtn');
        const printGroupedBtn = document.getElementById('printGroupedBtn');
        
        // Hiển thị nút in cho trạng thái đã duyệt
        if (currentStatus === 'approved') {
            printBtn.style.display = 'inline-flex';
            printGroupedBtn.style.display = 'inline-flex';
        } else {
            printBtn.style.display = 'none';
            printGroupedBtn.style.display = 'none';
        }
    }

    function viewDetails(letterId) {
        // Tạo form ẩn để submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/datn/pages/giaovien/chitietgiaygioithieu';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'giay_id';
        input.value = letterId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    function approveLetter(letterId) {
        if (!confirm('Bạn có chắc chắn muốn duyệt giấy giới thiệu này?\n\nViệc duyệt sẽ thêm công ty vào hệ thống và không thể hoàn tác.')) {
            return;
        }
        
        // Hiển thị loading
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';
        btn.disabled = true;
        
        // Gửi AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/giaovien/approve_letter_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Hiển thị thông báo thành công
                        alert('✅ ' + response.message);
                        
                        // Reload trang để cập nhật dữ liệu
                        location.reload();
                    } else {
                        // Hiển thị lỗi
                        alert('❌ ' + response.message);
                        
                        // Khôi phục nút
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi xử lý phản hồi');
                    console.error('Parse error:', e);
                    
                    // Khôi phục nút
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
        };
        
        xhr.onerror = function() {
            alert('❌ Có lỗi kết nối');
            
            // Khôi phục nút
            btn.innerHTML = originalText;
            btn.disabled = false;
        };
        
        xhr.send('letter_id=' + encodeURIComponent(letterId));
    }

    function markAsReceived(letterId) {
        // Load thông tin cho modal
        loadReceiveModalData(letterId);
        
        // Hiển thị modal
        $('#receiveModal').modal('show');
    }
    
    function loadReceiveModalData(letterId) {
        // Set letter ID
        document.getElementById('receive_letter_id').value = letterId;
        
        // Load thông tin công ty và danh sách sinh viên
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/giaovien/get_company_students.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Hiển thị thông tin công ty
                        document.getElementById('company_info').innerHTML = `
                            <strong>${response.company.TenCty}</strong><br>
                            <small>Địa chỉ: ${response.company.DiaChi}</small><br>
                            <small>Đợt: ${response.company.TenDot || 'Chưa xác định'}</small>
                        `;
                        
                        // Hiển thị danh sách sinh viên
                        displayStudentsList(response.students);
                        
                        // Populate select box
                        populateStudentSelect(response.students);
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi tải dữ liệu');
                    console.error('Parse error:', e);
                }
            }
        };
        
        xhr.send('letter_id=' + encodeURIComponent(letterId));
    }
    
    function displayStudentsList(students) {
        const container = document.getElementById('students_list');
        
        if (students.length === 0) {
            container.innerHTML = '<p class="text-muted">Không có sinh viên nào.</p>';
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
        html += '<thead><tr><th>STT</th><th>MSSV</th><th>Họ tên</th><th>Đợt</th></tr></thead><tbody>';
        
        students.forEach((student, index) => {
            html += `
                <tr data-mssv="${student.MSSV}" data-name="${student.Ten}">
                    <td>${index + 1}</td>
                    <td>${student.MSSV}</td>
                    <td>${student.Ten}</td>
                    <td>${student.TenDot || 'Chưa xác định'}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
    
    function populateStudentSelect(students) {
        const select = document.getElementById('receive_nguoinhan');
        select.innerHTML = '<option value="">-- Chọn sinh viên đại diện --</option>';
        
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.ID_TaiKhoan;
            option.textContent = `${student.Ten} (${student.MSSV})`;
            select.appendChild(option);
        });
    }
    
    function filterStudentsInModal() {
        const searchValue = document.getElementById('student_search').value.toLowerCase();
        const rows = document.querySelectorAll('#students_list tbody tr');
        
        rows.forEach(row => {
            const mssv = row.getAttribute('data-mssv').toLowerCase();
            const name = row.getAttribute('data-name').toLowerCase();
            
            if (mssv.includes(searchValue) || name.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    function submitReceiveInfo() {
        const form = document.getElementById('receiveForm');
        const formData = new FormData(form);
        
        // Validate
        const nguoinhan = document.getElementById('receive_nguoinhan').value;
        if (!nguoinhan) {
            alert('Vui lòng chọn người đại diện nhận giấy!');
            return;
        }
        
        // Disable submit button
        const submitBtn = event.target;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';
        submitBtn.disabled = true;
        
        // Send request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/giaovien/save_receive_info.php', true);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ ' + response.message);
                        $('#receiveModal').modal('hide');
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi lưu thông tin');
                    console.error('Parse error:', e);
                }
                
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        };
        
        xhr.send(formData);
    }
    
    function handlePrintGrouped() {
        // Kiểm tra xem có giấy nào để in không
        const approvedCount = <?php echo count($approvedList); ?>;
        if (approvedCount === 0) {
            alert('Không có giấy giới thiệu nào để in!');
            return;
        }
        
        if (!confirm('In theo công ty sẽ gộp sinh viên cùng công ty vào một giấy.\n\nSau khi in, tất cả giấy sẽ được chuyển sang trạng thái "Đã in". Tiếp tục?')) {
            return;
        }
        
        // Hiển thị loading
        const printBtn = document.getElementById('printGroupedBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang chuẩn bị...';
        printBtn.disabled = true;
        
        // Đánh dấu tất cả là đã in
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/giaovien/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Mở file in theo công ty trong tab mới
                        const printUrl = '/datn/pages/giaovien/print_grouped_letters.php';
                        window.open(printUrl, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
                        
                        // Reload trang sau delay ngắn
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('❌ ' + response.message);
                        // Khôi phục nút
                        printBtn.innerHTML = originalText;
                        printBtn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                    // Khôi phục nút
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }
            }
        };
        
        xhr.send('action=print_grouped');
    }

    function printLetter(letterId) {
        if (!confirm('In giấy giới thiệu này?\n\nSau khi in, giấy sẽ được chuyển sang trạng thái "Đã in".')) {
            return;
        }
        
        // Đánh dấu là đã in trước
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/giaovien/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Mở trang in trong tab mới
                        const printUrl = '/datn/pages/giaovien/print_letter_template.php?id=' + letterId;
                        window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
                        
                        // Reload trang sau delay ngắn
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                }
            }
        };
        
        xhr.send('action=print_single&letter_ids[]=' + encodeURIComponent(letterId));
    }

    function handlePrintAll() {
        // Kiểm tra xem có giấy nào để in không
        const approvedCount = <?php echo count($approvedList); ?>;
        if (approvedCount === 0) {
            alert('Không có giấy giới thiệu nào để in!');
            return;
        }
        
        if (!confirm(`Bạn có muốn in tất cả ${approvedCount} giấy giới thiệu đã duyệt?\n\nMỗi sinh viên sẽ có một giấy riêng. Sau khi in, tất cả giấy sẽ được chuyển sang trạng thái "Đã in".`)) {
            return;
        }
        
        // Hiển thị loading
        const printBtn = document.getElementById('printAllBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang chuẩn bị...';
        printBtn.disabled = true;
        
        // Đánh dấu tất cả là đã in
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/giaovien/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Mở từng giấy trong tab mới với delay
                        const letterIds = <?php echo json_encode(array_column($approvedList, 'ID')); ?>;
                        
                        letterIds.forEach((id, index) => {
                            setTimeout(() => {
                                const printUrl = '/datn/pages/giaovien/print_letter_template.php?id=' + id;
                                window.open(printUrl, '_blank' + index, 'width=800,height=600,scrollbars=yes,resizable=yes');
                            }, index * 500); // Delay 500ms giữa các tab
                        });
                        
                        // Reload trang sau khi mở hết các tab
                        setTimeout(() => {
                            location.reload();
                        }, letterIds.length * 500 + 1000);
                    } else {
                        alert('❌ ' + response.message);
                        // Khôi phục nút
                        printBtn.innerHTML = originalText;
                        printBtn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                    // Khôi phục nút
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }
            }
        };
        
        xhr.send('action=print_all');
    }

    // Utility functions
    function showLoading(element) {
        element.classList.add('loading');
    }

    function hideLoading(element) {
        element.classList.remove('loading');
    }

    // Initialize display on load
    updatePrintButtonVisibility();
    </script>
    <!-- jQuery đã được include trong template head.php -->
</body>
</html>