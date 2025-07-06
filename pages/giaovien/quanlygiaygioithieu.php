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
                s.Ten AS TenSinhVien, s.MSSV,
                d.TenDot, d.ThoiGianBatDau, d.ThoiGianKetThuc
            FROM giaygioithieu g
            LEFT JOIN sinhvien s ON g.IdSinhVien = s.ID_TaiKhoan
            LEFT JOIN dotthuctap d ON g.id_dot = d.ID
            ORDER BY g.TrangThai ASC, g.ID DESC
        ");
        $stmt->execute();
        $allLetters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by status
        $result = [
            'pending' => [],   // TrangThai = 0
            'approved' => [],  // TrangThai = 1  
            'printed' => []    // TrangThai = 2
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
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in getLettersByStatus: " . $e->getMessage());
        return ['pending' => [], 'approved' => [], 'printed' => []];
    }
}

// Lấy dữ liệu
$letters = getLettersByStatus();
$pendingList = $letters['pending'];
$approvedList = $letters['approved'];
$printedList = $letters['printed'];
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
            body * { visibility: hidden; }
            #print-section, #print-section * { visibility: visible; }
            #print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .letter-card {
                page-break-inside: avoid;
                margin-bottom: 20px;
                box-shadow: none;
                border: 1px solid #ddd;
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
                    </div>
                    
                    <input type="text" class="search-input" id="searchInput" 
                           placeholder="🔍 Tìm kiếm theo MSSV, tên sinh viên hoặc tên công ty...">
                    
                    <div class="action-buttons">
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
                                        <button type="button" class="btn btn-sm btn-success disabled">
                                            <i class="fa fa-check"></i> Đã nhận
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
                </div>

                <!-- Print Section (Hidden) -->
                <div id="print-section" style="display: none;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h2>DANH SÁCH GIẤY GIỚI THIỆU ĐÃ DUYỆT</h2>
                        <hr style="border: 1px solid #333;">
                    </div>
                    <?php foreach ($approvedList as $letter): ?>
                        <div style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px;">
                            <h3><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($letter['DiaChi']); ?></p>
                            <p><strong>Sinh viên:</strong> <?php echo htmlspecialchars($letter['TenSinhVien']); ?> (<?php echo htmlspecialchars($letter['MSSV']); ?>)</p>
                            <?php if ($letter['TenDot']): ?>
                                <p><strong>Đợt thực tập:</strong> <?php echo htmlspecialchars($letter['TenDot']); ?>
                                <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                    - <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> đến <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <p><strong>Trạng thái:</strong> Đã duyệt</p>
                        </div>
                    <?php endforeach; ?>
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
        // Chỉ hiển thị nút in cho trạng thái đã duyệt
        printBtn.style.display = currentStatus === 'approved' ? 'inline-flex' : 'none';
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

    function printLetter(letterId) {
        // Mở trang in trong tab mới
        const printUrl = '/datn/pages/giaovien/print_letter_template.php?id=' + letterId;
        window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    }

    function handlePrintAll() {
        // Kiểm tra xem có giấy nào để in không
        const approvedCount = <?php echo count($approvedList); ?>;
        if (approvedCount === 0) {
            alert('Không có giấy giới thiệu nào để in!');
            return;
        }
        
        if (!confirm(`Bạn có muốn in tất cả ${approvedCount} giấy giới thiệu đã duyệt?`)) {
            return;
        }
        
        // Hiển thị loading
        const printBtn = document.getElementById('printAllBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang chuẩn bị...';
        printBtn.disabled = true;
        
        // Kích hoạt chức năng in sau delay ngắn
        setTimeout(() => {
            const printContents = document.getElementById('print-section').innerHTML;
            const originalContents = document.body.innerHTML;
            
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            
            // Khôi phục lại trang
            location.reload();
        }, 500);
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